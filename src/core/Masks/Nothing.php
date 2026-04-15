<?php
namespace Saola\Core\Masks;

// biến đổi model thành một object để tránh bị crack

use Countable;
use ArrayAccess;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use ReflectionClass;

abstract class Nothing implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable, Jsonable, Arrayable {
    protected $data = [];
    
    /**
     * hàm khởi tạo
     * @param Saola\Core\Models\Model
     * @return void
     */
    public function __construct($model = null, $collectionClass = null)
    {
        
    }

    
    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator():ArrayIterator
    {
        return new ArrayIterator($this->data);
    }


    /**
     * set thông tin
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * lấy thông tin
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if(array_key_exists($name, $this->data)){
            return $this->data[$name];
        }
        
        return null;
    }

    
    
    /**
     * kiểm tra tồn tại
     * 
     * @return boolean
     */
    public function  __isset($key)
    {
        return isset($this->data[$key]) ? true : false;


    }

    /**
     * đếm phần tử
     * @return int
     */
    public function count():int
    {
        return count($this->data);
    }

     
    public function offsetSet($offset, $value):void {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset):void {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function toArray()
    {
        
        return $this->data;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    public function __toString()
    {
        return "";
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
            } elseif ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            }

            return $value;
        }, $this->toArray());
    }

    

    /**
     * gọi hàm từ model nếu dc set
     * @param string $name tên phương thức
     * @param array $arguments mảng tham số
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return array_key_exists($name, $this->data) ? $this->data[$name] : (isset($arguments[0])?$arguments[0]:null);
    }

    public static function __callStatic($name, $arguments)
    {
        return (isset($arguments[0])?$arguments[0]:null);
    }
}