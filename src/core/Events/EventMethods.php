<?php

namespace Saola\Core\Events;

use Closure;

/**
 * các phương thúc với event
 * @method $this on(string $event, Closure $closure) lắng nghe sự kiện
 * @method $this addEventListener(string $event, Closure $closure) lắng nghe sự kiện
 * @method $this trigger(string $event, ...$params) Kích hoạt sự kiện
 * @method $this fire(string $event, ...$params) Kích hoạt sự kiện
 * @method $this emit(string $event, ...$params) Kích hoạt sự kiện
 * @method $this hasEvent(string $event) kiểm tra sự kiện có tồn tại hay không
 * @method $this eventExists(string $event) kiểm tra sự kiện có tồn tại hay không
 * @method $this hasEventListener(string $event) kiểm tra sự kiện có tồn tại hay không
 * @method $this removeEvent(string $event, Closure $closure) xóa sự kiện
 * @method $this off(string $event, Closure $closure) xóa sự kiện
 * @method $this removeEventListener(string $event, Closure $closure) xóa sự kiện
 * 
 * @method static boolean on(string $event, Closure $closure) lắng nghe sự kiện
 * @method static boolean addEventListener(string $event, Closure $closure) lắng nghe sự kiện
 * @method static boolean trigger(string $event, ...$params) Kích hoạt sự kiện
 * @method static boolean fire(string $event, ...$params) Kích hoạt sự kiện
 * @method static boolean emit(string $event, ...$params) Kích hoạt sự kiện
 * @method static boolean hasEvent(string $event) kiểm tra sự kiện có tồn tại hay không
 * @method static boolean eventExists(string $event) kiểm tra sự kiện có tồn tại hay không
 * @method static boolean hasEventListener(string $event) kiểm tra sự kiện có tồn tại hay không
 * @method static void removeEvent(string $event, Closure $closure) xóa sự kiện
 * @method static void off(string $event, Closure $closure) xóa sự kiện
 * @method static void removeEventListener(string $event, Closure $closure) xóa sự kiện
 * 
 * 
 */
trait EventMethods
{
    protected static $events = [];

    protected static $eventMethods = [
        'on',
        'addEventListener',
        'trigger',
        'fire',
        'emit',
        'hasEvent',
        'eventExists',
        'hasEventListener',
        'removeEventListener',
        'off',
        'removeEvent',
    ];

    /**
     * khai báo mảng chứa các event cho class
     *
     * @return void
     */
    public static function makeEventContainerByCurrentClassName()
    {
        $classname = static::class;
        if (!array_key_exists($classname, static::$events)) {
            static::$events[$classname] = [];
        }
    }

    /**
     * lắng nghe sự kiện
     *
     * @param string $event
     * @param \Closure $closure
     * @return bool
     */
    protected static function _addEventListener($event, $closure)
    {
        if (is_string($event) && is_callable($closure)) {
            $event = strtolower($event);
            static::makeEventContainerByCurrentClassName();
            if (!array_key_exists($event, static::$events[static::class])) {
                static::$events[static::class][$event] = [];
            }
            static::$events[static::class][$event][] = $closure;
            return true;
        }
        return false;
    }

    /**
     * gọi sự kiện
     *
     * @param string $event
     * @param mixed ...$params
     * @return mixed
     */
    public static function _dispatchEvent($event, ...$params)
    {
        if (is_string($event)) {
            $event = strtolower($event);
            static::makeEventContainerByCurrentClassName();
            if (array_key_exists($event, static::$events[static::class]) && count(static::$events[static::class][$event])) {
                $arr = [];
                foreach (static::$events[static::class][$event] as $closure) {
                    $arr[] = $closure(...$params);
                }
                return $arr;
            }
        }
        return null;
    }

    public static function _removeEvent($event = null, $closure = null)
    {
        if (is_string($event)) {
            $event = strtolower($event);
            static::makeEventContainerByCurrentClassName();
            if (array_key_exists($event, static::$events[static::class]) && count(static::$events[static::class][$event])) {
                if($closure){
                    static::$events[static::class][$event] = array_filter(static::$events[static::class][$event], function($item) use ($closure){
                        return $item !== $closure;
                    });
                }else{
                    static::$events[static::class][$event] = [];
                }
            }
        }
        elseif(is_array($event)){
            foreach($event as $e => $c){
                static::_removeEvent($e, $c);
            }
        }
        elseif(!$event){
            static::$events[static::class] = [];
        }
    }

    /**
     * Kiểm tra event có tồn tại hay chưa
     *
     * @param string $event
     * @return bool
     */
    public static function _eventExists($event)
    {
        if (is_string($event)) {
            $event = strtolower($event);
            static::makeEventContainerByCurrentClassName();
            if (array_key_exists($event, static::$events[static::class]) && count(static::$events[static::class][$event])) {
                return true;
            }
        }
        return false;
    }

    public static function callEventMethod($fn, $params = [])
    {
        static::makeEventContainerByCurrentClassName();
        if (is_string($fn) && is_array($params) && in_array($fn, static::$eventMethods)) {
            // $fn = strtolower($fn);

            switch ($fn) {
                case 'on':
                case 'addEventListener':
                    return static::_addEventListener(...$params);
                case 'emit':
                case 'fire':
                case 'trigger':
                case 'dispatchEvent':
                    return static::_dispatchEvent(...$params);
                case 'hasEvent':
                case 'eventExists':
                case 'hasEventListener':
                    return static::_eventExists(...$params);
                case 'removeEventListener':
                case 'off':
                case 'removeEvent':
                    return static::_removeEvent(...$params);
            }
        }
        return null;
    }


}
