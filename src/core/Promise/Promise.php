<?php

namespace Saola\Core\Promise;

use Exception;
use Saola\Core\Concerns\SerializableViaArray;

/**
 * Promise class
 * @package Saola\Core\romise
 * @author One <steak@steak.vn>
 * @version 1.0.0
 * @since 1.0.0
 * @license MIT
 * @copyright (c) 2025 One
 * 
 * @method mixed run() Dùng để chạy promise
 * @method mixed end() Kết thúc promise
 * @method mixed start() Bắt đầu promise
 * @method mixed done() Kết thúc promise
 */
interface PromiseInterface
{
    public function then(callable $onFulfilled);
    public function catch(callable $onRejected);
    public function finally(callable $onFinally);
    public function value();
}

/**
 * Promise class
 * @package Saola\Core\romise
 * @author One <steak@steak.vn>
 * @version 1.0.0
 * @since 1.0.0
 * @license MIT
 * @copyright (c) 2025 One
 * 
 * @method Promise then(callable $onFulfilled) Chạy khi promise hoàn thành
 * @method Promise catch(callable $onRejected) Chạy khi promise bị lỗi
 * @method Promise finally(callable $onFinally) Chạy khi promise hoàn thành hoặc bị lỗi, chỉ chạy 1 lần
 * @method mixed value() Lấy giá trị của promise
 * @method mixed await() Chạy promise
 * @method mixed commit() Chạy promise
 * @method mixed error() Lấy lỗi của promise
 * @property mixed $result Lấy giá trị của promise
 * @property string $state Trạng thái của promise
 * @property \Exception $error Lấy lỗi của promise
 * @property string $reason Lấy lý do của promise
 * @property array $debug Debug promise
 * @property mixed $value Lấy giá trị của promise
 * @property mixed $error Lấy lỗi của promise
 */
class Promise implements PromiseInterface
{
    use SerializableViaArray;
    const STATE_PENDING = 'pending';
    const STATE_SUCCESS = 'success';
    const STATE_FULFILLED = 'fulfilled';
    const STATE_REJECTED = 'rejected';

    protected $_state = self::STATE_PENDING;

    protected $_value = null;
    protected $_reason = null;
    protected $_thenCallbacks = [];
    protected $_catchCallback = null;
    protected $_finallyCallback = null;

    protected $_catchCallbackCalled = false;
    protected $_finallyCallbackCalled = false;
    protected $_thenCallbackCalled = false;
    /**
     * @var callable<(function(function(mixed):mixed,function(mixed):mixed):mixed)>
     */
    protected $_callback = null;
    protected $_error = null;
    protected $_errorTrace = null;

    /**
     * @param callable<(function(function(mixed):mixed,function(mixed):mixed):mixed)> $callback callback của promise
     * @param bool $autoStart Tự động chạy promise
     */
    public function __construct($callback, $autoStart = true)
    {
        if (is_callable($callback)) {
            $this->_callback = $callback;
        } elseif (is_array($callback)) {
            $this->_callback = $callback['handle'] ?? ($callback['action'] ?? ($callback['call'] ?? ($callback['callable'] ?? null)));
            if (array_key_exists('success', $callback)) {
                $this->then($callback['success']);
            }
            if (array_key_exists('fail', $callback)) {
                $this->catch($callback['fail']);
            }
            if (array_key_exists('error', $callback)) {
                $this->catch($callback['error']);
            }
            if (array_key_exists('finally', $callback)) {
                $this->finally($callback['finally']);
            }
            // Thực thi ngay lập tức giống JavaScript
            $autoStart = true;
        } else {
        }
        if ($autoStart) {
            $this->__start__();
        }
    }
    /**
     * @param callable<(function(mixed):mixed):mixed> $callback
     */
    public function then($callback)
    {
        if (!is_callable($callback)) {
            return $this;
        }
        if ($this->_state === 'pending') {
            $this->_thenCallbacks[] = $callback;
        } elseif ($this->_state === 'fulfilled') {
            try {
                $this->_value = call_user_func_array($callback, [$this->_value]);
            } catch (\Exception $e) {
                $this->_error = $e;
                $this->_state = 'rejected';
            }
        }
        return $this;
    }

    /**
     * @param callable<(function(mixed):mixed):mixed> $callback
     */
    public function catch(callable $callback)
    {
        if ($this->_state === 'pending') {
            $this->_catchCallback = $callback;
        } elseif ($this->_state === 'rejected' && !$this->_catchCallback) {
            $this->_catchCallback = $callback;
            try {
                $this->_value = call_user_func_array($callback, [$this->_error]);
                $this->_catchCallbackCalled = true;
            } catch (\Exception $e) {
                $this->_error = $e;
                $this->_state = 'rejected';
            }
        }
        return $this;
    }

    /**
     * @param callable<(function(mixed):mixed):mixed> $callback
     */
    public function finally(callable $callback)
    {
        if($this->_finallyCallback || $this->_finallyCallbackCalled){
            return $this;
        }
        if(!$this->_finallyCallbackCalled && is_callable($callback)){
            $this->_finallyCallback = $callback;
        }
        if($this->_state === static::STATE_FULFILLED || $this->_state === static::STATE_REJECTED){
            call_user_func_array($this->_finallyCallback, []);
            $this->_finallyCallbackCalled = true;
        }
        return $this;
    }



    protected function __start__()
    {
        if ($this->_state !== self::STATE_PENDING) {
            return $this;
        }
        $callback = $this->_callback;
        try {
            if (is_callable($callback)) {
                call_user_func_array($callback, [function ($value) {
                    if ($value instanceof Promise) {
                        if ($value->state === self::STATE_PENDING || $value->state === self::STATE_SUCCESS) {
                            $value->__start();
                        }
                        if ($value->state === self::STATE_FULFILLED) {
                            $value = $value->value();
                        } elseif ($value->state === self::STATE_REJECTED) {
                            $this->_value = $value->value();
                            $error = $value->error();
                            throw $error;
                        }
                    }
                    $this->_value = $value;
                    $this->_state = self::STATE_SUCCESS;
                }, function ($reason) {
                    $this->_state = self::STATE_REJECTED;
                    if ($reason instanceof \Exception) {
                        $this->_error = $reason;
                        $this->_reason = $reason->getMessage();
                    } elseif (is_string($reason)) {
                        $this->_error = new \Exception($reason);
                        $this->_reason = $reason;
                    } else {
                        $this->_error = new \Exception($reason);
                        $this->_reason = $reason;
                    }
                    throw $this->_error;
                }]);
                if ($this->_state === self::STATE_SUCCESS) {
                    $this->__resolve();
                }
            } else {
                $this->_state = self::STATE_REJECTED;
                throw new \Exception('Callback is not a callable');
            }
        } catch (\Exception $e) {
            $this->_error = $e;
            $this->_state = self::STATE_REJECTED;
            $this->__catch($e);
        }
        if ($this->_finallyCallback && is_callable($finallyCallback = $this->_finallyCallback)) {
            call_user_func_array($finallyCallback, []);
        }

        return $this;
    }

    protected function __resolve($value = null)
    {
        if ($value !== null) {
            $this->_value = $value;
        }
        try {
            $this->_state = 'fulfilled';
            foreach ($this->_thenCallbacks as $callback) {
                $value = call_user_func_array($callback, [$this->_value]);
                if ($value instanceof Promise) {
                    if ($value->state === 'pending' || $value->state === 'success') {
                        $value->__start();
                    }
                    if ($value->state === 'fulfilled') {
                        $value = $value->value();
                    } elseif ($value->state === 'rejected') {
                        $error = $value->error();
                        $this->_value = $value->value();
                        throw $error;
                    } else {
                        throw new \Exception('Promise is not in a valid state');
                    }
                }
                $this->_value = $value;
            }
            $this->_state = 'fulfilled';
        } catch (\Exception $e) {
            $this->_error = $e;
            $this->_state = 'rejected';
            throw $e;
        }
    }

    protected function __catch(\Exception $error)
    {
        $this->_state = self::STATE_REJECTED;
        if ($this->_catchCallbackCalled) {
            return $this;
        }
        // Lưu stack trace để dễ dàng gỡ lỗi
        $this->_errorTrace = $error->getTraceAsString();

        $catchCallback = $this->_catchCallback;
        if ($catchCallback && is_callable($catchCallback)) {
            try {
                $value = call_user_func_array($catchCallback, [$error]);
                $this->_catchCallbackCalled = true;
                if ($value instanceof Promise) {
                    $value->__start();
                    if ($value->state === self::STATE_FULFILLED) {
                        $value = $value->value();
                    } elseif ($value->state === self::STATE_REJECTED) {
                        $this->_value = $value->value();
                        return $this;
                    }
                    $this->_value = $value;
                } else {
                    $this->_value = $value;
                }
            } catch (\Exception $e) {
                // Lỗi trong catch callback
                $this->_error = $e;
                return $this;
            }
        } else {
            if ($this->_finallyCallback && is_callable($finallyCallback = $this->_finallyCallback)) {
                call_user_func_array($finallyCallback, [$error]);
            }
            throw $error;
        }

        return $this;
    }

    protected function __finally()
    {
        if($this->_finallyCallback && is_callable($finallyCallback = $this->_finallyCallback) && !$this->_finallyCallbackCalled){
            call_user_func_array($finallyCallback, []);
            $this->_finallyCallbackCalled = true;
        }
        return $this;
    }

    /**
     * await promise
     * @return mixed
     */
    public function await()
    {
        if ($this->_state === self::STATE_PENDING) {
            $this->__start__();
        } 
        if ($this->_state === self::STATE_REJECTED && !$this->_catchCallbackCalled) {
            $this->__catch($this->_error);
        } 
        return $this->_value;
    }

    public function value()
    {
        return $this->await();
    }

    public function commit()
    {
        return $this->await();
    }

    /**
     * lấy exception
     *
     * @return \Exception
     */
    public function error()
    {
        $this->__start__();
        return $this->_error;
    }

    public function __get($name)
    {
        if ($name === 'value' || $name === 'result' || $name === 'data') {
            return $this->value();
        }
        if ($name === 'state' || $name === 'status') {
            return $this->_state;
        }
        if ($name === 'error') {
            return $this->_error;
        }
        if ($name === 'reason') {
            return $this->_reason;
        }
        return null;
    }

    public function __call($name, $arguments)
    {
        if ($name === 'run' || $name === 'end' || $name === 'start' || $name === 'done' || $name === 'result' || $name === 'val') {
            return $this->await();
        }
        if ($name === '__start') {
            return $this->__start__();
        }
        return $this;
    }

    public function __invoke()
    {
        return $this->await();
    }

    public static function all(array $promises)
    {
        return new Promise(function ($resolve, $reject) use ($promises) {
            $values = [];
            try {
                $r = function ($err) {
                    throw $err;
                };
                foreach ($promises as $promise) {
                    $promise->then(function ($value) use (&$values) {
                        $values[] = $value;
                    })->catch(function ($error) use ($r) {
                        $r($error);
                    });
                }
                $resolve($values);
            } catch (\Exception $e) {
                $reject($e);
            }
        });
    }

    public static function resolve($value)
    {
        return new Promise(function ($resolve, $reject) use ($value) {
            $resolve($value);
        });
    }

    public static function reject($reason)
    {
        return new Promise(function ($resolve, $reject) use ($reason) {
            $reject($reason);
        });
    }

    public static function race(array $promises)
    {
        return new Promise(function ($resolve, $reject) use ($promises) {
            foreach ($promises as $promise) {
                $promise->then(function ($value) use ($resolve) {
                    $resolve($value);
                })->catch(function ($error) use ($reject) {
                    $reject($error);
                });
            }
        });
    }

    public static function allSettled(array $promises)
    {
        return new Promise(function ($resolve, $reject) use ($promises) {
            $results = [];
            $remaining = count($promises);

            if ($remaining === 0) {
                $resolve([]);
                return;
            }

            foreach ($promises as $key => $promise) {
                $promise->then(
                    function ($value) use ($key, &$results, &$remaining, $resolve) {
                        $results[$key] = ['status' => 'fulfilled', 'value' => $value];
                        if (--$remaining === 0) {
                            $resolve($results);
                        }
                    },
                    function ($reason) use ($key, &$results, &$remaining, $resolve) {
                        $results[$key] = ['status' => 'rejected', 'reason' => $reason];
                        if (--$remaining === 0) {
                            $resolve($results);
                        }
                    }
                );
            }
        });
    }

    public static function any(array $promises)
    {
        return new Promise(function ($resolve, $reject) use ($promises) {
            $errors = [];
            $remaining = count($promises);

            if ($remaining === 0) {
                $reject(new \Exception('No promises provided'));
                return;
            }

            foreach ($promises as $key => $promise) {
                $promise->then(
                    function ($value) use ($resolve) {
                        $resolve($value);
                    },
                    function ($reason) use ($key, &$errors, &$remaining, $reject) {
                        $errors[$key] = $reason;
                        if (--$remaining === 0) {
                            $reject(new \Exception('All promises were rejected: ' . json_encode($errors)));
                        }
                    }
                );
            }
        });
    }

    public function __toString()
    {
        return $this->value();
    }

    // Thêm phương thức debug để kiểm tra promise
    public function debug()
    {
        return [
            'state' => $this->_state,
            'value' => $this->_value,
            'error' => $this->_error ? [
                'message' => $this->_error->getMessage(),
                'trace' => $this->_error->getTraceAsString()
            ] : null,
            'reason' => $this->_reason,
        ];
    }

    // Thêm phương thức để hỗ trợ async/await pattern
    public static function coroutine(\Generator $generator)
    {
        return new Promise(function ($resolve, $reject) use ($generator) {
            $step = function ($gen, $value = null, $exception = null) use (&$step) {
                try {
                    if ($exception) {
                        $result = $gen->throw($exception);
                    } else {
                        $result = $gen->send($value);
                    }

                    if ($result->done) {
                        return Promise::resolve($result->value);
                    }

                    if ($result->value instanceof Promise) {
                        return $result->value->then(
                            function ($value) use ($gen, $step) {
                                return $step($gen, $value);
                            },
                            function ($error) use ($gen, $step) {
                                return $step($gen, null, $error);
                            }
                        );
                    }

                    return $step($gen, $result->value);
                } catch (\Exception $e) {
                    return Promise::reject($e);
                }
            };

            $step($generator)->then($resolve, $reject);
        });
    }

    public static function map(array $items, callable $callback)
    {
        $promises = [];
        foreach ($items as $key => $item) {
            $promises[$key] = Promise::resolve($callback($item, $key));
        }
        return Promise::all($promises);
    }

    public static function reduce(array $items, callable $callback, $initialValue = null)
    {
        $accumulator = Promise::resolve($initialValue);

        foreach ($items as $key => $item) {
            $accumulator = $accumulator->then(function ($carry) use ($callback, $item, $key) {
                return $callback($carry, $item, $key);
            });
        }

        return $accumulator;
    }

    protected function __cleanup()
    {
        // Dọn dẹp callback sau khi promise hoàn thành
        if ($this->_state === self::STATE_FULFILLED || $this->_state === self::STATE_REJECTED) {
            $this->_thenCallbacks = [];
            $this->_catchCallback = null;
            $this->_callback = null;

            // Giữ _finallyCallback cho đến khi gọi xong
            if ($this->_finallyCallbackCalled) {
                $this->_finallyCallback = null;
            }
        }
    }

    public function __serialize()
    {
        return [
            'state' => $this->_state,
            'value' => $this->_value, // Cần đảm bảo có thể serialize
            'reason' => $this->_reason,
            // Không thể serialize callbacks
        ];
    }

    public function __unserialize(array $data)
    {
        $this->_state = $data['state'];
        $this->_value = $data['value'];
        $this->_reason = $data['reason'];
    }
}
