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

        if (count($params)) {
            if (in_array($method, static::$eventMethods)) {
                static::callEventMethod($method, $params);
            } elseif ($this->_funcExists($method)) {
                $this->_nonStaticCall($method, $params);
            } elseif (substr($method, 0, 2) == 'on' && strlen($event = substr($method, 2)) > 0 && ctype_upper(substr($event, 0, 1)) && count($params) && (is_callable($params[0]) || is_callable([$this, $params[0]]))) {
                $this->_addEventListener($event, $params[0]);
            } elseif (substr($method, 0, 4) == 'emit') {
                if (strlen($event = substr($method, 4)) > 0 && ctype_upper(substr($event, 0, 1))) {
                    return static::_dispatchEvent($event, ...$params);
                } else {
                    return static::_dispatchEvent(array_shift($params), ...$params);
                }
            }
            // elseif(($result = $this->callMacro($method, $params, static::MACRO_TYPE_NAME)) !== static::NO_MACRO_EXIST) {
            //     return $result;
            // }
        } elseif ($this->_funcExists($method)) {
            $this->_nonStaticCall($method, $params);
        } elseif (substr($method, 0, 4) == 'emit' && strlen($event = substr($method, 4)) > 0 && (ctype_upper(substr($event, 0, 1)))) {
            return static::_dispatchEvent($event, ...$params);
        } 
        // elseif(($result = $this->callMacro($method, $params, static::MACRO_TYPE_NAME)) !== static::NO_MACRO_EXIST) {
        //     return $result;
        // }
        return $this;
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
