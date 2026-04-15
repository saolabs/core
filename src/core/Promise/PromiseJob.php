<?php

namespace Saola\Core\Promise;


class PromiseJob implements \Illuminate\Contracts\Queue\ShouldQueue
{
    protected $promiseData;
    protected $callbackClass;
    protected $callbackMethod;
    protected $args;

    public function __construct($callbackClass, $callbackMethod, $args = [])
    {
        $this->callbackClass = $callbackClass;
        $this->callbackMethod = $callbackMethod;
        $this->args = $args;
    }

    public function handle()
    {
        $instance = new $this->callbackClass();
        $promise = new Promise(function($resolve, $reject) use ($instance) {
            try {
                $result = call_user_func_array([$instance, $this->callbackMethod], $this->args);
                $resolve($result);
            } catch (\Exception $e) {
                $reject($e);
            }
        });
        
        return $promise->value(); // Thực thi và trả về kết quả
    }
}
