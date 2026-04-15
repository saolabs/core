<?php

namespace Saola\Core\Events;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class EventDispatcher implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable{
    protected $__type__ = null;
    protected $__data__ = [];
    protected $__status__ = true;
    protected $__context__ = null;
        /**
     * khoi tao doi tuong
     * @param array|object $data
     */
    function __construct($data = [], $type = null)
    {
        if($type){
            $this->__type__ = $type;
        }
        if(is_object($data)){
            $this->__data__ = $data;
        }
        if(is_array($data)){
            foreach ($data as $key => $value) {
                // duyệt qua mảng hoặc object để gán key, value ở level 0 cho biến data
                $this->__data__[$key] = $value;
            }
        }
    }

    public function preventDefault()
    {
        $this->__status__ = false;
    }

    public function getStatus()
    {
        return $this->__status__;
    }

    /**
     * đếm phần tử
     * @return int
     */
    public function count():int
    {
        return count($this->__data__);
    }

    
    /**
     * kiểm tra tồn tại
     *
     * @return boolean
     */
    public function  __isset($key)
    {
        if(is_object($this->__data__)){
            return isset($this->__data__->$key);
        }
        return isset($this->__data__[$key]);
    }

    /**
     * xóa phần tử
     * @param string $key
     */
    public function __unset($key)
    {
        if(is_object($this->__data__)){
            unset($this->__data__->$key);
        }else{
            unset($this->__data__[$key]);
        }
    }

    public function offsetSet($offset, $value):void {
        if (is_null($offset)) {
            if(is_object($this->__data__)){
                $this->__data__->$offset = $value;
            }else{
                $this->__data__[] = $value;
            }
        } else {
            if(is_object($this->__data__)){
                $this->__data__->$offset = $value;
            }else{
                $this->__data__[$offset] = $value;
            }
        }
    }

    public function offsetExists($offset):bool {
        if(is_object($this->__data__)){
            return isset($this->__data__->$offset);
        }
        return isset($this->__data__[$offset]);
    }

    public function offsetUnset($offset):void {
        if(is_object($this->__data__)){
            unset($this->__data__->$offset);
        }else{
            unset($this->__data__[$offset]);
        }
    }

    public function offsetGet($offset): mixed {
        if(is_object($this->__data__)){
            return isset($this->__data__->$offset) ? $this->__data__->$offset : null;
        }
        return isset($this->__data__[$offset]) ? $this->__data__[$offset] : null;
    }

    
    public function __set($offset, $value):void {
        if($offset == 'type'){
            // $this->__type__ = $value;
        }
        else if($offset == 'context'){
            // 
        }
        else{
            if(is_object($this->__data__)){
                if($offset == 'data'){
                    if(!is_array($value) && !is_object($value)){
                        $this->__data__->$offset = $value;
                    }else{
                        $this->__data__ = (object) $value;
                    }
                }else{
                    $this->__data__->$offset = $value;
                }
            }else{
                if($offset == 'data'){
                    if(!is_array($value) && !is_object($value)){
                        $this->__data__[$offset] = $value;
                    }else{
                        $this->__data__ = $value;
                    }
                }else{
                    $this->__data__[$offset] = $value;
                }
            }
        }
    }

    public function __get($name) {
        if($name == 'type'){
            return $this->__type__;
        }
        if($name == 'context'){
            return $this->__context__;
        }
        if(is_object($this->__data__)){
            if($name == 'data'){
                return $this->__data__;
            }
            return isset($this->__data__->$name) ? $this->__data__->$name : null;
        }
        return isset($this->__data__[$name]) ? $this->__data__[$name] : null;
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->__data__);
    }



    public function toArray()
    {
        return $this->__data__;
    }

    

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }


    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof static) {
                return $value->toArray();
            }

            return $value;
        }, $this->toArray());
    }


    /**
     * gọi hàm với tên thuộc tính với tham số là giá trị default
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments)
    {
        if($name == 'setContext'){
            if(!$this->__context__ && count($arguments) > 0 && is_object($arguments[0])){
                $this->__context__ = $arguments[0];
            }
            return $this;
        }
        return isset($this->__data__[$name]) ? $this->__data__[$name] : (array_key_exists('0', $arguments)?$arguments[0]:null);
    }

}