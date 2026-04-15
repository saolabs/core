<?php

namespace Saola\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Saola\Core\Promise\Promise;

/**
 * doi tuong quan li request toi api
 * @method ResponseInterface|string|array get(string $url, array $query = [], array $headers = [])
 * @method ResponseInterface|string|array post(string $url, array $data = [], array $headers = [])
 * @method ResponseInterface|string|array put(string $url, array $data = [], array $headers = [])
 * @method ResponseInterface|string|array path(string $url, array $data = [], array $headers = [])
 * @method ResponseInterface|string|array delete(string $url, array $data = [], array $headers = [])
 * @method ResponseInterface|string|array options(string $url, array $data = [], array $headers = [])
 * 
 * @method static ResponseInterface|string|array get(string $url, array $query = [], array $headers = [])
 * @method static ResponseInterface|string|array post(string $url, array $data = [], array $headers = [])
 * @method static ResponseInterface|string|array put(string $url, array $data = [], array $headers = [])
 * @method static ResponseInterface|string|array path(string $url, array $data = [], array $headers = [])
 * @method static ResponseInterface|string|array delete(string $url, array $data = [], array $headers = [])
 * @method static ResponseInterface|string|array options(string $url, array $data = [], array $headers = [])
 * 
 */
class Client extends BaseApi{
    // test
    protected static $returnType = '';
    protected static $instance;
    public function __call($name, $arguments)
    {
        $method = strtoupper($name);
        return $this->send($method, ...$arguments);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * set return type
     * @param string $type json, array, string, object, ...
     * @return void
     */
    public static function setReturnType($type)
    {
        self::$returnType = $type;
    }

    public static function __callStatic($name, $arguments)
    {
        $instance = self::getInstance();
        if(self::$returnType){
            $instance->setOutput(self::$returnType);
        }
        return $instance->$name(...$arguments);
    }

    public static function promise($promiseCallback)
    {
        $promise = new Promise(static function($resolve, $reject) use ($promiseCallback){
            $result = call_user_func_array($promiseCallback, []);
            if($result){
                $resolve($result);
            }
            else{
                $reject(new \Exception('Promise callback must return a value or an instance of \Exception'));
            }
        });
        return $promise;
    }
}