<?php

namespace Saola\Core\Queues;

use Carbon\Carbon;
use Closure;

class Queue
{
    protected static $_enabled = null;
    /**
     * kiểm tra sãn sàng cho chạy queue
     *
     * @return bool
     */
    public static function enabled()
    {
        if (self::$_enabled === null) {
            $enabled = config('queue.enabled');
            static::$_enabled = ($enabled == true || in_array(strtolower($enabled), ['on', 'yes', 'true', 'enabled']));
        }
        return self::$_enabled;
    }

    /**
     * thêm công việc sẽ làm
     *
     * @param Closure|array $callable
     * @param integer $seconds
     * @param array $args
     * @param Closure $failed
     * @param array $failedArgs
     * @return mixed
     */
    public static function add($callable = null, $seconds = 0, $args = [], $failed = null, $failedArgs = [])
    {
        if(is_array($callable) && (array_key_exists('handle', $callable) || array_key_exists('handler', $callable) || array_key_exists('action', $callable) || array_key_exists('call', $callable) || array_key_exists('callable', $callable))){
            $call = $callable['handle']??($callable['handler']??($callable['action']??($callable['action']??($callable['call']??($callable['callable']??null)))));
            $arg = $callable['args']??($callable['params']??$args);
            $time = $callable['delay']??($callable['timeout']??$seconds);
            $fail = $callable['failed']??($callable['fail']??($callable['onFail']??($callable['onFailed']??$failed)));
            $fag = $callable['failedArgs']??($callable['failArgs']??($callable['failed_args']??($callable['fail_args']??($callable['failedArgs']))));
            if(is_array($fag)) $failedArgs = $fag;
            if(is_callable($call)) $callable = $call;
            if(is_array($arg)) $args = $arg;
            if(is_numeric($time) && $time >= 0) $seconds = $time;
            if(is_callable($fail)) $failed = $fail;
        }
        if (!is_callable($callable)) return is_callable($failed) ? call_user_func_array($failed, $failedArgs) : false;
        if ($seconds <= 0 || !self::enabled()) {
            try {
                return call_user_func_array($callable, $args);
            } catch (\Throwable $th) {
                return is_callable($failed) ? call_user_func_array($failed, $failedArgs) : false;
            }
        }
        $job = (new QueueWork($callable, $args, $failed, $failedArgs))->delay(Carbon::now()->addSeconds($seconds));
        dispatch($job);
        return true;
    }
    /**
     * thêm công việc sẽ làm
     *
     * @param integer $seconds
     * @param Closure|array $callable
     * @param array $args
     * @param Closure $failed
     * @return mixed
     */
    public static function delay($seconds = 0, $callable = null, $args = [], $failed = null)
    {
        if (!is_callable($callable)) return is_callable($failed) ? call_user_func_array($failed, []) : false;
        if ($seconds <= 0 || !self::enabled()) {
            try {
                return call_user_func_array($callable, $args);
            } catch (\Throwable $th) {
                return is_callable($failed) ? call_user_func_array($failed, []) : false;
            }
        }
        $job = (new QueueWork($callable, $args, $failed))->delay(Carbon::now()->addSeconds($seconds));
        dispatch($job);
        return true;
    }

    /**
     * thêm công việc sẽ làm
     *
     * @param Closure|array $callable
     * @param integer $seconds
     * @param array $args
     * @param Closure $failed
     * @param array $failedArgs
     * @return mixed
     */
    public static function setTimeout($callable = null, $seconds = 0, $args = [], $failed = null, $failedArgs = [])
    {
        return self::add($callable, $args, $seconds, $failed, $failedArgs);
    }

}
