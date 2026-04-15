<?php
namespace Saola\Core\Magic;

use Countable;
use ArrayAccess;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @method static array prefix(array $array, string $prefix, bool $has_value = false, \Closure|callable $parse_value, bool $delete_prefix = false) lấy ra một mảng các giá trị có prefix như của key đã truyền vào
 * @method array prefix(string $prefix, bool $has_value = false, \Closure|callable $parse_value, bool $delete_prefix = false) lấy ra một mảng các giá trị có prefix như của key đã truyền vào
 *
 * @method array setPrefix(string $prefix) thêm prefix vào key
 * @method static array setPrefix(array $array, string $prefix) thêm prefix vào key
 *
 * @method array match(string $key, \Closure|int|string $check) Kiểm tra phần tử theo key
 * @method static array match(array $array, string $key, \Closure|int|string $check) Kiểm tra phần tử theo key
 * 
 * @method $this entities(array $array) htmlentities for all element of array
 * @method static array entities(array $array) htmlentities for all element of array
 * 
 * @method $this deepMerge(array $merge) deep merge for all element of array
 * @method static array deepMerge(array $root, array $merge) deep merge for all element of array
 */
class Arr implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable, Jsonable, Arrayable
{
    const DEFVAL = '<!-----------------s2--------2025------------->';
    /**
     * @var array $data
     */
    protected $data = [];
    protected $accessible;
    /**
     * khoi tao doi tuong
     * @param array|object $data
     */
    function __construct($data = [])
    {
        $this->newData($data);
    }

    public function newData($data = [])
    {
        $this->data = [];
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                // duyệt qua mảng hoặc object để gán key, value ở level 0 cho biến data
                $this->data[$key] = $value;
            }
        }
        return $this;
    }

    public function empty()
    {
        $this->data = [];
        return $this;
    }

    /**
     * lấy data theo mảng key theo thứ tự trong mảng
     * chỉ lấy giá trị của thằng con sau cùng
     * không hợp lệ sẽ trả về giá trị mặc định
     *
     * @param array $keys
     * @param mixed $default
     * @return mixed
     */
    public function getByKeyLevels(array $keys, $default = null)
    {
        $data = $this->data;
        if ($this->accessible && is_array($this->accessible)) {
            if (!in_array($keys[array_key_first($keys)], $this->accessible)) return $default;
        }
        foreach ($keys as $n) {
            // duyệt mảng key

            if (!is_array($data) && !is_object($data)) {
                // nếu data hiện tại không phải là mảng hoặc là không tồn tại key thứ n trong mãng thì trả về mặc định
                return $default;
            } elseif (is_array($data)) {
                if (!array_key_exists($n, $data))
                    return $default;
                // ngược lại sẽ biến mảng data hiện tại thành giá trị tương ứng với key6 hiện tại trong mảng data hiện tại
                $data = $data[$n];
            } elseif (!property_exists($data, $n))
                return $default;
            else
                $data = $data->{$n};
        }

        if ((is_string($data) && strlen($data) == 0) || is_null($data)) return is_callable($default) ? $default() : $default;
        if (is_array($data) && count($data) == 0) return is_array($default) ? $default : $data;
        return $data;
    }

    /**
     * lấy giá trị phần tử
     * @param string|int|array $key
     * @param mixed $default giá trị mặc định
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) return $this->all();
        if ($this->accessible && is_array($this->accessible)) {
            if (!in_array($key, $this->accessible)) return $default;
        }
        if (is_array($key)) {
            foreach ($key as $k) {
                if (array_key_exists($k, $this->data)) {
                    $a = $this->data[$k];
                    $b = (is_string($a) && strlen($a) == 0) || is_null($a) || (is_array($a) && !count($a) && is_array($default)) ? $default : $a;
                    return $b;
                }
                if (count($name_arr = explode('.', $k)) > 1) {
                    $a = $this->getByKeyLevels($name_arr, static::DEFVAL);
                    if ($a != static::DEFVAL) return $a;
                }
            }
            return is_callable($default) ? $default() : $default;
        }
        // nếu tồn tải key name trong mang data
        if (array_key_exists($key, $this->data)) {
            $a = $this->data[$key];
            $b = (is_string($a) && strlen($a) == 0) || is_null($a) || (is_array($a) && !count($a) && is_array($default)) ? $default : $a;

            return $b;
        }
        if (count($name_arr = explode('.', $key)) > 1) {
            return $this->getByKeyLevels($name_arr, $default);
        }
        return is_callable($default) ? $default() : $default;
    }

    /**
     * lấy ra phần tử đầu tiên trong mảng
     *
     * @param mixed $default
     * @return mixed
     */
    public function first($default = null)
    {
        $d = $this->data;
        if ($d) {
            return array_shift($d);
        }
        return $default;
    }


    /**
     * lấy ra phần tử đầu tiên trong mảng
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function firstOf($key = null, $default = null)
    {
        if ($key !== null) {
            $a = null;
            $arr = $this->get($key);
            if (is_array($arr)) return array_shift($arr);
            return $arr;
        }

        return $this->first($default);
    }

    /**
     * lấy ra phần tử cuối trong mảng
     *
     * @param mixed $default
     * @return mixed
     */
    public function last($default = null)
    {
        $d = $this->data;
        if ($d) {
            return array_pop($d);
        }
        return $default;
    }

    /**
     * gán giá trị
     * @param string|int $key
     * @param mixed
     * @return object instance
     */
    public function set($key, $value)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } elseif (count($keys = explode('.', $key)) > 1) {
            $this->data = $this->fillValue($keys, $value, $this->data);
        } elseif (is_array($value) && array_key_exists($key, $this->data) && is_array($this->data[$key])) {
            foreach ($value as $k => $v) {
                $this->data = $this->fillValue([$key, $k], $v, $this->data);
            }
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }


    /**
     * push them giá trị vào sau cung của mảng
     * @param mixed $value
     * @param string $key
     * @return $this instance
     */
    public function push($value, $key = null)
    {
        if (!is_null($key)) {
            $arr = $this->get($key);
            if (!is_array($arr)) {
                if ($arr) $arr = [$arr];
                else {
                    $arr = [];
                }
            }
            $arr[] = $value;
            $this->set($key, $arr);
        } else {
            $this->data[] = $value;
        }
        return $this;
    }

    /**
     * dien tham so vao mang
     * @param string $key
     * @param mixed $value
     *
     * @param array $array
     * @return array
     */
    protected function fillValue($keys, $value = null, $array = null)
    {
        if ($keys) {
            $k = array_shift($keys);
            if (!is_array($array)) $array = [];
            if (!count($keys)) {
                if (!is_array($value) || !array_key_exists($k, $array) || !is_array($array[$k]))
                    $array[$k] = $value;
                else{
                    $d = $array[$k]??[];
                    foreach ($value as $key => $v) {
                        if(is_array($v) && is_array($d[$key])){
                            $d[$key] = $this->fillValue([$key], $v, $d[$key]);
                        }
                        else{
                            $d[$key] = $v;
                        }
                    }
                    $array[$k] = $d;
                }
            } else {
                $array[$k] = $this->fillValue($keys, $value, $array[$k] ?? []);
            }
        }
        return $array;
    }


    /**
     * xoa theo mang key
     * @param string $key
     *
     * @param array $array
     * @return array
     */
    protected function removeTree($keys, $array = null)
    {
        if ($keys) {
            $k = array_shift($keys);
            if (!is_array($array)) return $array;
            if (!count($keys)) {
                unset($array[$k]);
            } elseif (isset($array[$k]) && is_array($array[$k])) {
                $array[$k] = $this->removeTree($keys, $array[$k]);
            }
        }
        return $array;
    }

    /**
     * xóa phần tử
     * @param string ...$keys
     * @return object instance
     */
    public function remove(...$keys)
    {
        if (count($keys)) {
            foreach ($keys as $key) {
                if (count($array_key = explode('.', $key)) > 1) {
                    $this->data = $this->removeTree($array_key, $this->data);
                } else {
                    unset($this->data[$key]);
                }
            }
        } else {
            $this->data = [];
        }
        return $this;
    }


    /**
     * copy các key value và trả về một mảng
     * @param array $keys
     * @return array
     */
    public function copy(array $keys = [])
    {
        if (count($keys)) {
            $data = [];
            foreach ($keys as $key) {
                $data[$key] = $this->get($key);
            }
            return $data;
        }
        return $this->data;
    }


    /**
     * copy các key value và trả về một mảng
     * @param array $keys
     * @return array
     */
    public function cut(array $keys = [])
    {
        if (count($keys)) {
            $data = [];
            foreach ($keys as $key) {
                $data[$key] = $this->get($key);
                $this->remove($key);
            }
            return $data;
        }
        return [];
    }


    /**
     * copy các key value và trả về một mảng
     * @param array $keys
     * @return array
     */
    public function cutWithout(array $keys = [])
    {
        if (count($keys)) {
            $data = [];
            foreach ($this->data as $key => $value) {
                if (!in_array($key, $keys)) {
                    $data[$key] = $value;
                    $this->remove($key);
                }
            }
            return $data;
        }
        return [];
    }




    /**
     * copy các key value và trả về một mảng
     * @param array $keys
     * @return array
     */
    public function copyWithout(array $keys = [])
    {
        if (count($keys)) {
            $data = [];
            foreach ($this->data as $key => $value) {
                if (!in_array($key, $keys)) {
                    $data[$key] = $value;
                }
            }
            return $data;
        }
        return [];
    }





    /**
     * tra về mag data
     * @return array
     */
    public function all()
    {
        return $this->data;
    }


    /**
     * đếm phần tử
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * trộn mảng
     * @param array $arrays
     * @return $this
     */
    public function merge(...$arrays)
    {
        $this->data = array_merge($this->data, ...$arrays);
        return $this;
    }

    /**
     * kiểm tra xem có phan tử nào giống giá trị đã cho hay ko
     * @param mixed $value giá trị cần kiểm tra
     * @param string $key key truy cap mang con
     * @return boolean
     */
    public function in($value, $key = null)
    {
        if (!is_null($key)) {
            if (is_array($parent = $this->get($key))) {
                return in_array($value, $parent);
            }
            return false;
        }
        return in_array($value, $this->data);
    }

    /**
     * kiểm tra xem có tồn tại key nào hay ko
     *
     * @param string|array $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->isset($key);
    }

    /**
     * kiểm tra xem có tồn tại bất kỳ key nào trong mảng đầu vào hay ko
     *
     * @param array $keys
     * @return boolean
     */
    public function hasAny($keys)
    {
        if (!is_array($keys)) {
            return $this->has($keys);
        }
        foreach ($keys as $key) {
            if ($this->has($key)) return true;
        }
        return false;
    }

    /**
     * kiểm tra xem key hoặc index nào đó có dc set hay ko
     * @param string $key
     * @return bool
     */
    public function isset($key): bool
    {
        // nếu key tồn tại sẽ trả về true
        if (array_key_exists($key, $this->data)) return true;
        // nếu key là chuỗi hoặc số
        elseif (is_string($key)) {
            // nếu key được phân cách bằng dấu chấm
            if (count($keys = explode('.', $key)) > 1) {
                $data = $this->data;
                foreach ($keys as $k) {
                    // nếu $data không phải mảng, hoặc key ko tồn tại trong mảng data thì trả về false
                    if (!is_array($data) || !array_key_exists($k, $data)) return false;
                    else $data = $data[$k];
                }
                return true;
            }
        }
        return false;
    }


    public function isArray($name)
    {
        return is_array($this->get($name));
    }


    /**
     * map
     *
     * @param \Closure $func
     * @return array
     */
    public function map($func = null)
    {
        if (is_callable($func)) {
            return array_map($func, $this->data);
        }
        return [];
    }

    public function filter($func = null)
    {
        if (is_callable($func)) {
            return array_filter($this->data, $func);
        }
        return [];
    }

    public function flip($set = false)
    {
        $data = array_flip($this->data);
        if ($set) {
            $this->data = $data;
            return $this;
        }
        return new static($data);
    }

    public function values()
    {
        return array_values($this->data);
    }

    public function keys(...$args)
    {
        return array_keys($this->data, ...$args);
    }

    /**
     * tao doi tuong moi bang prefix
     *
     * @param string $prefix
     * @return static
     */
    public function makeByPrefix(string $prefix = '', $has_value = false, $parse_value = null, $delete_prefix = true)
    {
        if ($data = $this->prefix($prefix, $has_value, $parse_value, $delete_prefix)) {
            return new static($data);
        }
        return new static();
    }

    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * lấy giá trị phần tụ theo tên thuộc tính
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * gan gia tri cho phan tu
     * @param string $key
     * @param mixed $value
     *
     * @return object
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * kiểm tra tồn tại
     *
     * @return boolean
     */
    public function  __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * xóa phần tử
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }


    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new ArrayIterator($this->data);
    }



    public function toArray()
    {
        if ($this->accessible && is_array($this->accessible)) {
            $data = [];
            foreach ($this->data as $key => $value) {
                if (in_array($key, $this->accessible))
                    // duyệt qua mảng hoặc object để gán key, value ở level 0 cho biến data
                    $data[$key] = $value;
            }
            $data;
        }
        return $this->data;
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
            } elseif ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            }

            return $value;
        }, $this->toArray());
    }

    /**
     * tách chuỗi thành mảng các chuỗi con
     *
     * @param string|int $key
     * @param string $delimiter
     * @param boolean $trim
     * @return array
     */
    public function split($key, $delimiter = '', $trim = true)
    {
        $results = [];

        $val = $this->get($key);
        if ($delimiter && $val && is_string($val)) {
            if (!is_array($delimiter)) $delimiter = [$delimiter];
            $t = count($delimiter);
            for ($i = 0; $i < $t; $i++) {
                $d = $delimiter[$i];
                if ($i == 0) {
                    $a = explode($d, $val);
                    $results = $a;
                } else {
                    $a = [];
                    $m = count($results);
                    for ($j = 0; $j < $m; $j++) {
                        $b = explode($d, $results[$j]);
                        foreach ($b as $v) {
                            $a[] = $v;
                        }
                    }
                    $results = $a;
                }
            }

            if ($trim) {
                $results = array_map('trim', $results);
            }
            $results = array_filter($results, function ($v) {
                return strlen($v) > 0;
            });
        } elseif (is_string($delimiter) && $val && is_string($val)) {
            $t = strlen($val);
            for ($i = 0; $i < $t; $i++) {
                $results[] = substr($val, $i, 1);
            }
        } elseif (!is_array($val)) $results = [$val];
        return $results;
    }



    protected static function __entities($array = [])
    {

        if (is_array($array)) {
            $a = $array;
            foreach ($a as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = static::__entities($value);
                } elseif (is_numeric($value)) {
                } elseif (is_string($value)) {
                    $array[$key] = htmlentities($value);
                }
            }
        } elseif (is_string($array)) {
            return htmlentities($array);
        }
        return $array;
    }

    protected static $funcs = [
        'prefix', 'setPrefix', 'match', 'entities', 'deepMerge'
    ];
    /**
     * gọi hàm với tên thuộc tính với tham số là giá trị default
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments)
    {
        if ($name == 'entities') {
            $this->data = static::__entities($this->data, ...$arguments);
            return $this;
        } elseif (in_array($name, static::$funcs)) {
            array_unshift($arguments, $this->data);
            return call_user_func_array('static::__' . $name, $arguments);
        }
        return $this->get($name, ...$arguments);
    }


    public static function __callStatic($name, $arguments)
    {
        if (in_array($name, static::$funcs)) {
            return call_user_func_array('static::__' . $name, $arguments);
        }
        if (in_array(str_replace('__', '', $name), static::$funcs)) {
            return call_user_func_array('static::' . $name, $arguments);
        }
    }

    /**
     * chuyển toản bộ object thành array
     * @param object $object
     */
    public static function parse($object)
    {
        $d = $object;
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            return array_map(__METHOD__, $d);
        } else {
            return $d;
        }
    }

    /**
     * kiểm tra xem mảng đó có phải chứa key ở level 0 toàn là số hay ko?
     * @param array $array
     * @return boolean
     */
    public static function isNumericKeys(array $array = []): Bool
    {
        return (array_values($array) === $array);
    }

    public static function __setPrefix($array = [], $prefix = null)
    {
        if ($prefix) {
            $a = [];
            foreach ($array as $key => $value) {
                $a[$prefix . $key] = $value;
            }
            return $a;
        }
        return $array;
    }
    /**
     * lấy ra các phần tử trong mảng theo prefix
     *
     * @param array $array
     * @param string $prefix
     * @param bool $has_value
     * @param \Closure|callable $parse_value
     * @param boolean $delete_prefix
     * @return array
     */
    protected static function __prefix(array $array, string $prefix = '', $has_value = false, $parse_value = null, $delete_prefix = false)
    {
        $data = [];
        // nếu có prefix
        if (strlen($prefix)) {
            // xóa vài kí tự đặc biệt
            $pre = '/' . str_replace([".", " ", "/"], ["\.", "\s", "\/"], $prefix) . '/i';

            foreach ($array as $key => $value) {
                // nếu yêu cầu kiểm tra phải có giá trị
                if ($has_value) {
                    if (is_string($value) && !strlen($value)) {
                        continue;
                    }
                }
                // nếu khớp ve17 prefix
                if (preg_match($pre, $key)) {
                    if ($delete_prefix) {
                        $k = preg_replace($pre, '', $key);
                    } else {
                        $k = $key;
                    }
                    $data[$k] = is_callable($parse_value) ? $parse_value($value) : $value;
                }
            }
        } else {
            if ($parse_value && is_callable($parse_value)) {
                foreach ($array as $key => $value) {
                    $d = $parse_value($value);

                    if ((is_array($d) && count($d)) || (is_string($d) && strlen($d)) || is_numeric($d) || $d) {
                        $data[$key] = $d;
                    }
                }
            } else {
                $data = $array;
            }
        }

        return $data;
    }

    /**
     * kiểm tra giá trị thông qua hàm callback
     *
     * @param mixed $value
     * @param \Closure $checkFunc
     * @return bool
     */
    protected static function checkValue($value, $checkFunc = null)
    {
        if (is_callable($checkFunc)) {
            return $checkFunc($value);
        }
        if (is_numeric($checkFunc)) {
            if ($checkFunc == 0) return $value >= 0;
            if ($checkFunc < 0) return $value < 0;
            return $value > 0;
        }
        if (is_array($checkFunc)) {
            return in_array($value, $checkFunc);
        }
        return false;
    }
    /**
     * kiểm tra phần tử có key truyền vào có khớp với yêu cầu hay ko
     *
     * @param array $array
     * @param string|array $key key cần kiểm tra giá trị
     * @param \Closure|string|null $check callback kiểm tra value
     * @return array
     */
    protected static function __match(array $array = [], $key = null, $check = null)
    {
        if (is_array($array) && count($array)) {
            if (is_array($key)) {
                $a = [];
                foreach ($array as $k => $v) {
                    if (isset($key[$k])) {
                        if (static::checkValue($v, $key[$k])) {
                            $a[$k] = $v;
                        }
                    } else {
                        $a[$k] = $v;
                    }
                }
                return $a;
            } elseif ((is_string($key) || is_numeric($key)) && $check) {
                if (isset($array[$key])) {
                    if (!static::checkValue($array[$key], $check)) unset($array[$key]);
                }
            }
        }
        return $array;
    }

    /**
     * Deep Merge
     *
     * @param array $root
     * @param array $data
     * @return array
     */
    protected static function __deepMerge($root, $data)
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $root) && is_array($value) && is_array($root[$key])) {
                $root[$key] = static::__deepMerge($root[$key], $value);
            } else {
                $root[$key] = $value;
            }
        }
        return $root;
    }
}
