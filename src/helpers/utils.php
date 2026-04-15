<?php

use Saola\Core\Http\Client;
use Saola\Core\Promise\Promise;

if (!function_exists('onePipe')) {
    /**
     * Pipe function
     * @param object<T> $object
     * @param callable<T, T> $callback
     * @return mixed
     */
    function onePipe($object, $callback, $default = null)
    {
        $isCallable = is_callable($callback);
        if ($isCallable) {
            if ($object) {
                return $callback($object);
            }
        }
        return $default;
    }

    function onePipeChain($object, $callbacks, $default = null)
    {
        $result = $object;
        foreach ($callbacks as $callback) {
            $result = onePipe($result, $callback, $default);
        }
        return $result;
    }
}
if (!function_exists('promise')) {
    /**
     * @param callable<(function(function(mixed):void,function(mixed):void):void)> $callback
     * @return Promise
     */
    function promise($callback)
    {
        return new Promise($callback);
    }
}

if (!function_exists('promiseAll')) {
    /**
     * @param array<Promise> $promises
     * @return Promise
     */
    function promiseAll(array $promises)
    {
        return Promise::all($promises);
    }
}

if (!function_exists('one')) {
    /**
     * Thực thi điều gì đó
     *
     * @param string<class-string>|Promise|mixed $t
     * @return Promise|mixed
     */
    function one($t)
    {
        if (($t instanceof Promise) || is_a($t, Promise::class)) {
            return $t->value();
        }
        return $t;
    }
}


if (!function_exists('await')) {
    /**
     * @param Promise|callable $promise Promise hoặc callable để thực thi
     * @param callable|null $action Action được gọi sau khi promise hoàn thành
     * @param array $options Các tùy chọn bổ sung (timeout, debug, v.v.)
     * @return mixed Kết quả của promise
     * @throws \Exception Nếu promise bị từ chối hoặc timeout
     */
    function await($promise, $action = null, $options = [])
    {
        // Xử lý options
        $timeout = $options['timeout'] ?? null;
        $debug = $options['debug'] ?? false;
        
        try {
            // Chuyển đổi callable thành Promise nếu cần
            if (is_callable($promise) && !($promise instanceof Promise)) {
                $origPromise = $promise;
                $promise = new Promise(function($resolve, $reject) use ($origPromise) {
                    try {
                        $result = call_user_func($origPromise);
                        $resolve($result);
                    } catch (\Exception $e) {
                        $reject($e);
                    }
                });
                
                if ($debug) {
                    error_log("Debug: Đã chuyển đổi callable thành Promise");
                }
            }
            
            // Kiểm tra nếu không phải Promise
            if (!($promise instanceof Promise)) {
                if ($debug) {
                    error_log("Debug: Đối tượng không phải Promise, trả về nguyên bản");
                }
                return $promise;
            }
            
            // Xử lý timeout nếu được chỉ định
            if ($timeout !== null && $timeout > 0) {
                if ($debug) {
                    error_log("Debug: Đang áp dụng timeout {$timeout}s");
                }
                
                $timeoutPromise = new Promise(function($resolve, $reject) use ($timeout) {
                    sleep($timeout);
                    $reject(new \Exception("Promise timeout sau {$timeout} giây"));
                });
                
                $promise = Promise::race([$promise, $timeoutPromise]);
            }
            
            // Thực thi action nếu được cung cấp
            if (is_callable($action)) {
                if ($debug) {
                    error_log("Debug: Đang thực thi action");
                }
                call_user_func($action, $promise);
                
            }
            
            // Trả về giá trị của promise
            if ($debug) {
                error_log("Debug: Đang lấy giá trị của Promise");
            }
            
            $result = $promise->value();
            
            if ($debug) {
                error_log("Debug: Promise hoàn thành với giá trị: " . json_encode($result));
            }
            
            return $result;
        } catch (\Exception $e) {
            if ($debug) {
                error_log("Debug: Lỗi xảy ra trong await: " . $e->getMessage());
                error_log("Debug: Stack trace: " . $e->getTraceAsString());
            }
            
            throw $e;
        }
    }
}

/**
 * Đợi một mảng promises hoàn thành và trả về mảng kết quả
 * 
 * @param array $promises Mảng Promise hoặc callable
 * @param array $options Các tùy chọn (timeout, debug, v.v.)
 * @return array Mảng kết quả
 * @throws \Exception Nếu bất kỳ promise nào bị từ chối
 */
function awaitAll(array $promises, $options = [])
{
    return await(Promise::all($promises), null, $options);
}

/**
 * Đợi promise đầu tiên hoàn thành và trả về kết quả của nó
 * 
 * @param array $promises Mảng Promise hoặc callable
 * @param array $options Các tùy chọn (timeout, debug, v.v.)
 * @return mixed Kết quả của promise đầu tiên hoàn thành
 * @throws \Exception Nếu tất cả promises đều bị từ chối
 */
function awaitRace(array $promises, $options = [])
{
    return await(Promise::race($promises), null, $options);
}

/**
 * Đợi bất kỳ promise nào hoàn thành thành công và trả về kết quả của nó
 * 
 * @param array $promises Mảng Promise hoặc callable
 * @param array $options Các tùy chọn (timeout, debug, v.v.)
 * @return mixed Kết quả của promise đầu tiên hoàn thành thành công
 * @throws \Exception Nếu tất cả promises đều bị từ chối
 */
function awaitAny(array $promises, $options = [])
{
    return await(Promise::any($promises), null, $options);
}

/**
 * Đợi tất cả promises hoàn thành (thành công hoặc thất bại) và trả về trạng thái của chúng
 * 
 * @param array $promises Mảng Promise hoặc callable
 * @param array $options Các tùy chọn (timeout, debug, v.v.)
 * @return array Mảng kết quả với trạng thái (fulfilled/rejected)
 */
function awaitAllSettled(array $promises, $options = [])
{
    return await(Promise::allSettled($promises), null, $options);
}

