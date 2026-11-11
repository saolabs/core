<?php

namespace Saola\Core\Services;

use Saola\Core\Events\EventMethods;
use Saola\Core\Concerns\MagicMethods;
use Saola\Core\Support\Methods\SmartInit;
use Saola\Core\Support\Methods\ModuleMethods;
use Saola\Core\Support\Methods\OneMacro;
use Saola\Core\Support\Methods\AttributeMethods;

class Service
{
    use EventMethods, MagicMethods, SmartInit;//, OneMacro, AttributeMethods;



    public function __construct()
    {
        $this->init();
    }


    public function initService(){
        

    }

    /**
     * gọi hàm không dược khai báo từ trước
     *
     * @param string $method
     * @param array $params
     * @return static
     */
    public function __call($method, $params)
    {
        if (in_array($method, static::$eventMethods)) {
            static::callEventMethod($method, $params);
            return $this;
        }
        if ($this->_funcExists($method)) {
            $this->_nonStaticCall($method, $params);
            return $this;
        }
        if (count($params) && substr($method, 0, 2) == 'on' && strlen($event = substr($method, 2)) > 0 && ctype_upper(substr($event, 0, 1)) && (is_callable($params[0]) || is_callable([$this, $params[0]]))) {
            $this->_addEventListener($event, $params[0]);
            return $this;
        }
        if (substr($method, 0, 4) == 'emit') {
            if (strlen($event = substr($method, 4)) > 0 && ctype_upper(substr($event, 0, 1))) {
                return static::_dispatchEvent($event, ...$params);
            }
            if (count($params)) {
                return static::_dispatchEvent(array_shift($params), ...$params);
            }
        }
        // A misspelt method used to return $this silently and carry on; fail like any PHP object instead.
        throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
    }
    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        if (in_array($method, static::$eventMethods)) {
            return static::_staticCall($method, $parameters);
        }
        return static::_staticCall($method, $parameters);
    }
}

Service::globalStaticFunc('on', '_addEventListener');
Service::globalFunc('on', 'addEventListener');
