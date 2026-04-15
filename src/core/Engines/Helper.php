<?php
namespace Saola\Core\Engines;
use Illuminate\Support\Str;
use Mobile_Detect;
define('CFWDRV', '<----------------------oOo--------0945786960-------oOo---------------------->');
define('__BASEPATH__', dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))))));
// root/vendor/Onetech/core/src/app/engines/Helper.php
Class Helper{
    protected static $device = null;
    public static function _device(){
        if(!static::$device) static::$device = app(Mobile_Detect::class);
        return static::$device;
    }

    public static function __callFuncWithDefaultReturnValue($name, $arguments = [], $default = CFWDRV)
    {
        $n = strtolower($name);
        $is = substr($n, 0, 2);
        if($is == 'is' && in_array(substr($n, 2), ['mobile', 'tablet', 'desktop']) ){
            return static::_device()->{$name}(...$arguments);
        }
        elseif(in_array($n, ['device', 'getDevice'])) return static::_device();
        if(is_callable($name)){
            return $name(...$arguments);
        }
        if(is_callable($fun = Str::snake($name))){
            return $fun(...$arguments);
        }
        if(is_callable($func = Str::camel($name))){
            return $func(...$arguments);
        }
        if($n == 'base_path' || $n == 'basepath'){
            return __BASEPATH__ . (isset($arguments[0])?'/'.ltrim($arguments[0], '/') : '');
        }
        if($n == 'public_path' || $n == 'publicpath'){
            return __BASEPATH__ .'/public' . (isset($arguments[0])?'/'.ltrim($arguments[0], '/') : '');
        }
        
        if($default != CFWDRV) return $default;
        return null;
    }

    public function __call($name, $arguments)
    {
        return static::__callFuncWithDefaultReturnValue($name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        return static::__callFuncWithDefaultReturnValue($name, $arguments);
    }

    public function __get($name)
    {
        return static::__callFuncWithDefaultReturnValue($name, []);
    }

    public function __set($name, $value)
    {
        
    }
}