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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\AbstractPaginator;

use ReflectionClass;

abstract class MaskCollection implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable, Jsonable, Arrayable
{

    private $isLock = false;
    protected $mask = '';

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

    protected $accessAllowed = [
        'perPage', 'currentPage', 'lastPage', 'url', 'firstItem', 'linkCollection', 'nextPageUrl', 'path', 'previousPageUrl', 'lastItem'
    ];

    public function __construct($collection, $total = 0, $mask = null, $parent = null)
    {

        $this->parent = $parent;
        if ($mask && class_exists($mask)) {
            $this->mask = $mask;
        } elseif (method_exists($this, 'getMask')) {
            $this->mask = $this->getMask();
        }
        $this->total = $total;

        if ($t = count($collection)) {
            if (!$total) $this->total = $t;

            // đầu tiên phải chạy qua init để thiết lập thông sớ
            if (method_exists($this, 'init')) {
                $this->init();
            }
            
            if (is_a($collection, LengthAwarePaginator::class) || is_a($collection, AbstractPaginator::class)) {
                $this->paginator = $collection;
                $this->isPaginator = true;
                if ($tal = $collection->total()) {
                    $this->total = $tal;
                }
            }
            if ($this->mask && class_exists($this->mask)) {
                foreach ($collection as $key => $item) {
                    $rc = new ReflectionClass($this->mask);
                    $this->items[$key] = $rc->newInstanceArgs([$item, null, null, $this]);
                    $id = $item->_id??$item->id;
                    if($id) $this->itemMap[$id] = $key;
                }
            } else {
                foreach ($collection as $key => $item) {
                    $this->items[$key] = new ExampleMask($this->mask, null, null, $this);
                    $id = $item->_id??$item->id;
                    if($id) $this->itemMap[$id] = $key;
                }
            }



            // gọi hàm onloaded khi hoàn tất quá trình
            if (method_exists($this, 'onLoaded')) {
                $this->onLoaded();
            }

            array_map(function($item){$item->__lock();}, $this->items);
        }elseif (is_a($collection, LengthAwarePaginator::class) || is_a($collection, AbstractPaginator::class)) {
            $this->paginator = $collection;
            $this->isPaginator = true;
            if ($tal = $collection->total()) {
                $this->total = $tal;
            }
        }
    }
    public function __lock()
    {
        if ($this->isLock) return;
        // cuối cùng là khóa truy cập

        $this->isLock = true;
        array_map(function($item){$item->__lock();}, $this->items);
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
        if ($this->isPaginator) {
            $paginator = $this->paginator;
            if ($args) $paginator->appends($args);
            return $paginator->links($blade);
        }
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
        return new ArrayIterator($this->items);
    }


    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    public function total()
    {
        return $this->total;
    }


    /**
     * Bắt dầu hiển thị từ số
     *
     * @return int
     */
    public function from()
    {
        if ($this->isPaginator) {
            $currrent = $this->currentPage();
            if ($currrent < 1) $currrent = 1;
            $perPage = $this->perPage();
            if (!$perPage) $perPage = 10;
            return ($currrent - 1) * $perPage + 1;
        }
        return $this->total() ? 1 : 0;
    }

    /**
     * hiển thị tới số
     *
     * @return int
     */
    public function to()
    {
        $total = $this->total;
        if ($this->isPaginator) {
            $currrent = $this->currentPage();
            if ($currrent < 1) $currrent = 1;
            $perPage = $this->perPage();
            if (!$perPage) $perPage = 10;
            $t = $currrent * $perPage;
            return $t > $total ? $total : $t;
        }
        return $total;
    }


    public function getItem($attr, $value = null)
    {
        if(!is_array($attr)){
            if($value === null){
                if(array_key_exists($attr, $this->itemMap)){
                    return $this->items[$this->itemMap[$attr]]??null;
                }
                return null;
            }
            foreach ($this->items as $item) {
                if($item->{$attr} == $value) return $item;
            }

            return null;
        }
        if(count($attr)){
            foreach ($this->items as $item) {
                $s = true;
                foreach ($attr as $key => $value) {
                    if($item->{$key} != $value) $s = false;
                }
                if($s) return $item;
            }
        }
        return null;
    }

    final public function getItems()
    {
        return $this->items;
    }
    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key):mixed
    {
        return $this->items[$key];
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
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }


    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize():mixed
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
        }, $this->items);
    }



    public function toArrayData()
    {
        $data = [];
        if (count($this->items)) {
            foreach ($this->items as $key => $item) {
                $data[$key] = $item->toArray();
            }
        }
        return $data;
    }

    public function toArray()
    {
        if(!$this->isPaginator) return $this->toArrayData();
        return [
            'data' => $this->toArrayData(),
            'total' => $this->total(),
            'from' => $this->firstItem(),
            'to' => $this->lastItem(),
            'current_page' => $this->currentPage(),
            'last_page' => $this->lastPage(),
            'per_page' => $this->perPage(),
            'first_page_url' => $this->url(1),
            'last_page_url' => $this->url($this->lastPage()),
            'prev_page_url' => $this->previousPageUrl(),
            'next_page_url' => $this->nextPageUrl(),
            'links' => ($lc = $this->linkCollection())?$lc->toArray():[],
            'path' => $this->path(),
        ];
    }

    public function toDeepArray()
    {
        return array_map(function ($value) {
            if (is_a($value, static::class)) {
                return $value->toDeepArray();
            } elseif (is_object($value) && is_callable([$value, 'toDeepArray'])) {
                return $value->toArray();
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            } elseif (is_object($value) && is_callable([$value, 'toArray'])) {
                return $value->toArray();
            }

            return $value;
        }, $this->toArray());
    }


    public function toJson($options = 0)
    {
        return json_encode(
            $this->toArray(), JSON_PRETTY_PRINT
        );
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
        if(is_array($attr)) $data = $attr;
        elseif (is_string($attr) || is_numeric($attr)) {
            $data = [$attr=>$value];
        }
        array_map(function($item) use($data, $setEachModel){
            $item->set($data, null, $setEachModel);
        }, $this->items);
        return $this;
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, $this->accessAllowed) && $this->isPaginator) {
            return call_user_func_array([$this->paginator, $name], $arguments);
        }
        return null;
    }
    public function __toString()
    {
        return $this->toJson();
    }
}
