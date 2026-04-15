<?php

namespace Saola\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Saola\Core\Promise\Promise;
/**
 * @property mixed $result Giá trị của promise
 * @property string $state Trạng thái của promise
 * @property \Exception $error Lấy lỗi của promise
 * @property string $reason Lấy lý do của promise
 * @property array $debug Debug promise
 * @property mixed $value Lấy giá trị của promise
 * @property mixed $error Lấy lỗi của promise
 */
class HttpPromise extends Promise{
    public function __construct($callback)
    {
        parent::__construct($callback, true);
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
                }, $this]);
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
        $value = $this->_value;
        if($value instanceof ResponseInterface){
            return $value->getBody()->getContents();
        }
        return $value;
    }
}