<?php

namespace Saola\Core\Repositories;

trait CacheAction
{
    
    /**
     * các phương thúc lấy dữ liệu
     *
     * @var array
     */
    protected $getDataMethods = [];
    /**
     * đẩy repository vào một cache task
     *
     * @param string $key
     * @param integer $time
     * @param array $params
     * @return CacheTask
     */
    final public function cache($key = null, $time = 0, $params = [])
    {
        if($time <= 0) $time = 0;
        if($time <= 0) return $this; // nếu timw truyền vào nhỏ hơn hoặc = 0 thì ko cần làm gì cả
        $repository = clone $this;
        $this->resetActionParams();
        return (new CacheTask($repository, $key, $time, $params));
    }

    /**
     * dăng ký các phương thức lấy dữ liệu sau khi gôi phương thức cache
     *
     * @param string[] ...$methods
     * @return void
     */
    final public function registerCacheMethods(...$methods)
    {
        if(count($methods)){
            foreach ($methods as $method) {
                $this->registerCacheMethod($method);
            }
        }
    }

    /**
     * thêm phương thức cache
     *
     * @param string|array $methods
     * @return void
     */
    final public function registerCacheMethod($methods)
    {
        if(!is_array($methods)){
            $this->getDataMethods[] = $methods;
        }else{
            foreach ($methods as $alias => $method) {
                if(is_numeric($alias)){
                    $this->getDataMethods[] = $method;
                }else{
                    $this->getDataMethods[strtolower($alias)] = $method;
                }
            }
        }
    }
    
    /**
     * lấy danh sách các phương thức lấy dữ liệu đã khai báo
     *
     * @return array
     */
    final public function getCacheMethods()
    {
        return $this->getDataMethods;
    }
}
