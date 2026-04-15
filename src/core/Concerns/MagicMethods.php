<?php

namespace Saola\Core\Concerns;

use BadMethodCallException;
use Closure;

/**
 * các phương thúc với class
 * @method static staticFunc(string $method, Closure $closure) Thêm phương thức
 * @method static func(string $method, Closure $closure) Thêm phương thức
 * @method static staticFuncExists(string $method, Closure $closure) Thêm phương thức
 * @method static funcExists(string $method, Closure $closure) Thêm phương thức
 * 
 */
trait MagicMethods
{
    protected static $methods = [
        '@global' => [
            'static' => [
    
            ],
            'nonstatic' => [
                
            ]
        ]
    ];

    /**
     * khai báo mảng chứa các method cho class
     *
     * @return void
     */
    protected static function makeMethodListByCurrentClassName()
    {
        $classname = static::class;
        if (!array_key_exists($classname, static::$methods)) {
            static::$methods[$classname] = [
                'static' => [
        
                ],
                'nonstatic' => [
                    
                ]
            ];
        }
    }

    protected static function _staticFunc($method, $closure)
    {
        static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return false;
        static::$methods[static::class]['static'][$method] = $closure;
        return true;
    }
    
    protected static function _func($method, $closure)
    {
        static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return false;
        static::$methods[static::class]['nonstatic'][$method] = $closure;
        return true;
    }
    
    protected static function _globalStaticFunc($method, $closure)
    {
        // static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return false;
        static::$methods['@global']['static'][$method] = $closure;
        return true;
    }
    
    protected static function _globalFunc($method, $closure)
    {
        // static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return false;
        static::$methods['@global']['nonstatic'][$method] = $closure;
        return true;
    }
    

    protected static function _staticFuncExists($method)
    {
        static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return false;
        return array_key_exists($method, static::$methods[static::class]['static']) || array_key_exists($method, static::$methods['@global']['static']);
    }
    
    protected static function _funcExists($method)
    {
        static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return false;
        return array_key_exists($method, static::$methods[static::class]['nonstatic']);
    }


    protected static function getStaticFunc($method)
    {
        static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return null;
        return array_key_exists($method, static::$methods[static::class]['static'])?static::$methods[static::class]['static'][$method] : (array_key_exists($method, static::$methods['@global']['static'])?static::$methods['@global']['static'][$method]:null);
    }
    protected static function getNonStaticFunc($method)
    {
        static::makeMethodListByCurrentClassName();
        if(!is_string($method)) return null;
        return array_key_exists($method, static::$methods[static::class]['nonstatic'])?static::$methods[static::class]['nonstatic'][$method] : (array_key_exists($method, static::$methods['@global']['nonstatic'])?static::$methods['@global']['nonstatic'][$method]:null);
    }


    protected static function _staticCall($method, $params)
    {
        $m = strtolower($method);
        if($m == 'staticfunc'){
            return static::_staticFunc(...$params);
        }
        elseif($m == 'func'){
            return static::_func(...$params);
        }
        elseif($m == 'globalstaticfunc'){
            return static::_globalStaticFunc(...$params);
        }
        elseif($m == 'globalfunc'){
            return static::_globalFunc(...$params);
        }
        elseif($m == 'staticfuncexists'){
            return static::_staticFuncExists(...$params);
        }
        elseif($m == 'funcexists'){
            return static::_funcExists(...$params);
        }
        elseif ($mt = static::getStaticFunc($method)) {
             
            if(is_string($mt)){
                if(is_callable('static::' . $mt)){
                    
                    return call_user_func_array('static::' . $mt, $params);
                }elseif (is_callable($mt)) {
                    return call_user_func_array($mt, $params);
                }
            }elseif (is_callable($mt)) {
                return call_user_func_array($mt, $params);
            }
        }
        else{
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }
        
    }
    
    
    protected function _nonStaticCall($method, $params)
    {
        $m = strtolower($method);
        if($m == 'staticfunc'){
            return static::_staticFunc(...$params);
        }
        elseif($m == 'func'){
            return static::_func(...$params);
        }
        elseif($m == 'staticfuncexists'){
            return static::_staticFuncExists(...$params);
        }
        elseif($m == 'funcexists'){
            return static::_funcExists(...$params);
        }
        elseif ($mt = static::getNonStaticFunc($method)) {
            if(is_string($mt)){
                if(method_exists($this, $mt)){
                    return call_user_func_array([$this, $mt], $params);
                }
                if(is_callable([$this, $mt])){
                    return call_user_func_array([$this, $mt], $params);
                }elseif (is_callable($mt)) {
                    return call_user_func_array($mt, $params);
                }
            }elseif (is_callable($mt)) {
                return call_user_func_array($mt, $params);
            }
        }
        else{
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }
        
    }
    
    
}
