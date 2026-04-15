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

abstract class EmptyCollection implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable, Jsonable, Arrayable
{

    private $isLock = false;
    
    /**
     * poarent
     *
     * @var Mask
     */
    protected $parent = null;

    /**
     * danh sach item
     *
     * @var Mask[]
     */
    protected $items = [];

    protected $itemMap = [];
    protected $total = 0;

    protected $paginator = null;

    protected $isPaginator = false;

    
    public function __construct()
    {

    }
    /**
     * lấy link phân trang
     *
     * @param string $blade
     * @param array $args
     * @return View
     */
    public function links(string $blade, array $args = [])
    {
        return null;
    }

    public function getPagination(string $blade, array $args = [])
    {
        return $this->links($blade, $args);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator([]);
    }


    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return 0;
    }

    public function total()
    {
        return 0;
    }


    /**
     * Bắt dầu hiển thị từ số
     *
     * @return int
     */
    public function from()
    {
        return 0;
    }

    /**
     * hiển thị tới số
     *
     * @return int
     */
    public function to()
    {
        return 0;
    }


    public function getItem($attr, $value = null)
    {
        return null;
    }

    final public function getItems()
    {
        return [];
    }
    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return false;
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key):mixed
    {
        return null;
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        
    }


    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize():mixed
    {
        return [];
    }



    public function toArrayData()
    {
        return [];
    }

    public function toArray()
    {
        return [];
    }

    public function toDeepArray()
    {
        return [];
    }


    public function toJson($options = 0)
    {
        return json_encode([]);
    }


    /**
     * set thuoc tinh cho toan bo item
     *
     * @param string|array $attr
     * @param mixed $value
     * @return $this
     */
    protected function set($attr, $value = null, $setEachModel = false)
    {
        
        return $this;
    }

    public function __call($name, $arguments)
    {
        return null;
    }
    public function __toString()
    {
        return json_encode([]);
    }
}
