<?php

namespace Saola\Core\Services;

use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\CacheMethods;

class ViewService extends Service
{
    use ViewMethods, CacheMethods;
    
    public function __construct()
    {
        $this->init();

    }

    







    


    public function __call($method, $params)
    {
        if(preg_match('/^view([A-Z][a-z0-9_]+)Cache$/i', $method, $matches) && method_exists($this, $name = 'view' . $matches[1])){
            return $this->cache($this->cacheKey . '-' . $name, $params, function() use($params, $name){
                return $this->{$name}(...$params);
            }, $this->cacheTime);
        }
        if(($value = $this->cacheMacro($method, $params)) !== self::NO_CACHE_VALUE_RETURN){
            return $value;
        }
        return parent::__call($method, $params);
    }
}
