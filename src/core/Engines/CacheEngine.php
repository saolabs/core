<?php
namespace Saola\Core\Engines;

use Illuminate\Http\Request;

Class CacheEngine{
    protected static $domain = null;
    /**
     * get domain
     * @return string
     */
    public static function getDomain()
    {
        if(!static::$domain){
            static::$domain = get_domain();
        }
        return static::$domain;
    }
    

    /**
     * Lấy thông tin key cache
     * 
     * Hàm này tạo một key cache duy nhất dựa trên:
     * - Domain hiện tại
     * - Key chính
     * - Các tham số (params)
     * 
     * Đặc biệt:
     * - Nếu params chứa Request object, sẽ tự động lấy RequestUri để thêm vào
     * - Nếu params chứa object khác, sẽ được normalize thành string/array
     *
     * @param string $key Key chính
     * @param array|mixed $params Tham số bổ sung (có thể chứa object, Request, etc.)
     * @return string Key cache đã được hash
     */
    public static function getKey($key, $params = [])
    {
        $accessKey = static::getDomain() . '-'. str_slug($key);
        
        if($params){
            // Normalize params để xử lý object, Request, etc.
            $normalizedParams = static::normalizeParams($params);
            
            if(is_array($normalizedParams)){
                // Sắp xếp array để đảm bảo thứ tự không ảnh hưởng đến key
                ksort($normalizedParams);
                $accessKey .= '-' . md5(json_encode($normalizedParams, JSON_UNESCAPED_UNICODE));
            }else{
                $accessKey .= '-' . md5((string)$normalizedParams);
            }
        }
        
        return md5($accessKey);
    }
    
    /**
     * Normalize tham số để xử lý object, Request, etc.
     * 
     * @param mixed $params Tham số cần normalize
     * @return array|string Tham số đã được normalize
     */
    protected static function normalizeParams($params)
    {
        // Nếu không phải array, xử lý đơn lẻ
        if(!is_array($params)){
            return static::normalizeValue($params);
        }
        
        $normalized = [];
        
        foreach($params as $key => $value){
            // Normalize key
            $normalizedKey = is_string($key) ? $key : (string)$key;
            
            // Normalize value
            $normalized[$normalizedKey] = static::normalizeValue($value);
        }
        
        return $normalized;
    }
    
    /**
     * Normalize một giá trị đơn lẻ
     * 
     * @param mixed $value Giá trị cần normalize
     * @return mixed Giá trị đã được normalize
     */
    protected static function normalizeValue($value)
    {
        // Xử lý Request object đặc biệt
        if($value instanceof Request){
            // Lấy RequestUri và các thông tin quan trọng khác
            $uri = $value->getRequestUri();
            
            $data = $value->all();
            $data['__uri__'] = $uri;

            return $data;
        }
        
        // Xử lý object khác
        if(is_object($value)){
            // Nếu object có method toArray(), sử dụng nó
            if(method_exists($value, 'toArray')){
                return static::normalizeParams($value->toArray());
            }
            
            // Nếu object có method __toString(), sử dụng nó
            if(method_exists($value, '__toString')){
                return (string)$value;
            }
            
            // Nếu là Model (Eloquent), lấy key và class
            if(method_exists($value, 'getKey')){
                return [
                    'class' => get_class($value),
                    'key' => $value->getKey(),
                ];
            }
            
            // Fallback: serialize object (có thể không stable với complex object)
            // Tốt hơn là throw exception hoặc log warning
            return [
                'class' => get_class($value),
                'hash' => spl_object_hash($value),
            ];
        }
        
        // Xử lý array (recursive)
        if(is_array($value)){
            return static::normalizeParams($value);
        }
        
        // Giá trị nguyên thủy (string, int, float, bool, null)
        return $value;
    }

    /**
     * get cache
     * @param string $key
     * @param array $params
     * @return mixed
     */
    public static function get($key, $params = [])
    {
        return cache(static::getKey($key, $params));
    }

    /**
     * set cache
     * @param string $key
     * @param int $time (minute)
     * @param array $params
     * @return mixed
     */
    
    public static function set($key, $value = null, $time = 0, $params = [])
    {
        $key = static::getKey($key, $params);
        if($time){
            cache([$key => $value], $time * 60);
        }
    }

    /**
     * lấy và lư chu cache
     *
     * @param string $key
     * @param integer $time
     * @param \Cloure $callback
     * @param array $params
     * @return mixed
     */
    public static function remember($key, $time = 0, $callback = null, $params = [])
    {
        $kay = static::getKey($key, $params);
        if(!($data = cache($key))){
            if(is_callable($callback)){
                $data = $callback();
                if($time){
                    cache([$kay=>$data], $time * 60);
                }
            }else{
                $data = null;
            }
        }
        return $data;
    }
    public static function cache($key, $params = [], $callback = null, $time = 0)
    {
        if(is_callable($callback)){
            return static::remember($key, $time, $callback, $params);
        }
        return static::get($key, $params);
    }
}