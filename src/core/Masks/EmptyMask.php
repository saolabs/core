<?php

namespace Saola\Core\Masks;

// biến đổi model thành một object để tránh bị crack

use Countable;
use ArrayAccess;

use ArrayIterator;
use Saola\Core\Models\Model;
use IteratorAggregate;
use JsonSerializable;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

use ReflectionClass;

/**
 * Mat na dai dien cho model
 * @method void init() chay cac thiet lap truoc khi set data
 * @method void onLoaded() thuc hien cac hanh dong hay them du lieu sau khi set data va load cac relation
 * @method void onCompleted() thuc hien cac hanh dong sau khi hoan tat qua trinh thiet lap data va load cac relation
 * @method void onSetupCompleted() thuc hien cac hanh dong sau khi hoan tat qua trinh thiet lap data va load cac relation
 * @method void onBeforeLoadRelations() thuc hirm hanh dong truoc khi load relation
 * 
 */
abstract class EmptyMask implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable, Jsonable, Arrayable
{
    private $isLock = false;
    protected $model = null;
    protected $data = [];
    protected $collectionClass = null;
    protected $accessAllowed = [
        'getShortDesc', 'getJsonFields', 'dateFormat', 'updateTimeFormat', 'toFormData',
        'shortContent', 'calculator_time', 'calculatorTime', 'timeAgo', 'getTimeAgo', 'timeFormat'
    ];

    /**
     * poarent
     *
     * @var Mask
     */
    protected $parent = null;

    protected $alias = [];

    /**
     * kiểu key của relation khi to array
     *
     * @var string
     */
    protected $relationKeyTransformType = 'snake';

    /**
     * các quan hệ dữ liệu đã dược load
     *
     * @var array
     */
    protected $relations = [];
    /**
     * tham chiếu các quan hệ vs mask
     * relate => mask
     *
     * @var array
     */
    protected $relationMap = [];

    protected $hidden = [];

    /**
     * hàm khởi tạo
     * @param Saola\Core\Models\Model
     * @return void
     */
    public function __construct()
    {
        $this->__lock();
    }

    /**
     * Thiết lập thông số
     * @param Saola\Core\Models\Model
     * @return Mask
     */
    final protected function setup($model, $collectionClass = null)
    {
        if ($this->isLock) return $this;
        // vòng đời được bắt đầu khi gán model
        $this->model = $model;
        if (method_exists($this->model, 'rewriteDataIfHasMLC')) {
            $this->model->rewriteDataIfHasMLC();
        }

        $this->collectionClass = $collectionClass;

        // đầu tiên phải chạy qua init để thiết lập thông sớ
        if (method_exists($this, 'init')) {
            $this->init();
        }
        // gọi các phương thức lấy dữ liễu để đưa vào mảng data
        if (method_exists($this, 'toMask')) {
            $this->data = $this->toMask($model);
        } elseif (method_exists($model, 'getAttrData')) {
            $this->data = $model->getAttrData();
        } elseif (method_exists($model, 'toArray')) {
            $this->data = $model->toArray();
        } elseif (is_object($model)) {
            $this->data = object_to_array($model);
        } elseif (is_array($model)) {
            $this->data = $model;
        }

        $this->checkDataIfHasMLC();

        // gọi hàm onloaded khi hoàn tất quá trình
        if (method_exists($this, 'onBeforeLoadRelations')) {
            $this->onBeforeLoadRelations();
        }



        // kiểm tra các quan hệ dữ liệu. nếu được thiết lập map thì sẽ tạo ra các mặt  tương ứng
        $this->checkRelationLoaded();
        // gọi hàm onloaded khi hoàn tất quá trình
        if (method_exists($this, 'onLoaded')) {
            $this->onLoaded();
        }

        return $this;
    }


    final public function __lock()
    {
        if ($this->isLock) return;
        // cuối cùng là khóa truy cập

        $this->isLock = true;
        // đầu tiên phải chạy qua init để thiết lập thông sớ
        $this->lockChildren();
        if (method_exists($this, 'onSetupCompleted')) {
            $this->onSetupCompleted();
        }
        // đầu tiên phải chạy qua init để thiết lập thông sớ
        if (method_exists($this, 'onCompleted')) {
            $this->onCompleted();
        }
    }


    public function checkDataIfHasMLC()
    {
        if ($this->model->multilang && ($localeContent = $this->model->localeContent)) {
            $this->model->rewriteDataIfHasMLC();
            // dd($localeContent);
            if ($localeContent->title && $this->model->fillable && in_array('title', $this->model->fillable))
                $this->title = $localeContent->title;
            if ($localeContent->keywords && $this->model->fillable && in_array('keywords', $this->model->fillable))
                $this->keywors = $localeContent->title;
            if (is_array($data = $localeContent->contents)) {
                foreach ($data as $key => $value) {
                    if ($value !== null && $value != "")
                        $this->{$key} = $value;
                }
            }
        }
    }



    /**
     * thêm danh sách cho phép truy cập vào model
     *
     * @param string[] ...$methods
     * @return Mask
     */
    final protected function allow(...$methods)
    {
        if ($this->isLock) return $this;
        if (count($methods)) {
            foreach ($methods as $method) {
                if (is_array($method)) {
                    foreach ($method as $key => $mt) {
                        if (is_numeric($key)) {
                            if (!in_array($mt, $this->accessAllowed)) {
                                $this->accessAllowed[] = $mt;
                            }
                        } else {
                            if (!in_array($key, $this->accessAllowed)) {
                                $this->accessAllowed[] = $key;
                            }
                            $this->alias($key, $mt);
                        }
                    }
                } else {
                    if (!in_array($method, $this->accessAllowed)) {
                        $this->accessAllowed[] = $method;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * khai báo các thuộc tính hoặc phương thức có thể được truy cập bằng tên khác
     *
     * @param string|array $name
     * @param string $alias
     * @return Mask
     */
    final protected function alias($name, $alias = null)
    {
        if ($this->isLock) return $this;
        if (is_array($name)) {
            foreach ($name as $n => $a) {
                $this->alias[$a] = $n;
            }
        } elseif (is_string($name)) {
            if ($name && is_string($alias)) {
                $this->alias[$alias] = $name;
            }
        }
        return $this;
    }

    /**
     * thêm quan hệ
     *
     * @param string|array $relation
     * @param null|string $mask
     * @return Mask
     */
    final protected function map($relation, $mask = null)
    {
        if ($this->isLock) return $this;
        if (is_array($relation)) {
            foreach ($relation as $rela => $m) {
                if (is_numeric($rela) && is_string($m)) {
                    $this->relationMap[$m] = null;
                } elseif (class_exists($m)) {
                    $this->relationMap[$rela] = $m;
                } elseif (is_string($rela)) {
                    $this->relationMap[$rela] = null;
                }
            }
        } elseif (is_string($relation)) {
            if ($mask && (is_string($mask) && class_exists($mask))) {
                $this->relationMap[$relation] = $mask;
            } else {
                $this->relationMap[$relation] = null;
            }
        }
        return $this;
    }

    /**
     * kiểm tra và set các quan hệ đã được load
     *
     * @return void
     */
    final protected function checkRelationLoaded()
    {
        if (!$this->model || !is_object($this->model) || !method_exists($this->model, 'getRelations')) return [];
        $relations = $this->model->getRelations();
        if ($relations && count($relations)) {
            foreach ($relations as $key => $relation) {
                if ($relation) {
                    $this->relations[$key] = $this->parseRelation(
                        $relation,
                        array_key_exists($key, $this->relationMap) ? $this->relationMap[$key] : null
                    );
                }
            }
        }
    }
    public function lockChildren()
    {
        foreach ($this->relations as $key => $relation) {
            if (method_exists($relation, '__lock')) $relation->__lock();
        }
    }

    /**
     * chuẩn hóa dữ liệu quan hệ
     *
     * @param \Illuminate\Support\Collection|\Saola\Core\Models\Model $relation
     * @param string $mask
     * @return Mask|MaskCollection|\Illuminate\Support\Collection|\Saola\Core\Models\Model
     */
    protected function parseRelation($relation, $mask = null)
    {
        if (!$mask || !$relation || !is_string($mask) || !class_exists($mask)) {
            return $relation;
        }

        $rc = new ReflectionClass($mask);
        return $rc->newInstanceArgs([$relation, null, null, $this]);
    }


    /**
     * lấy dữ liệu qua hê đã được map
     *
     * @param string $key
     * @param bool $loadIfNotExists load dữ liệu nếu chưa tồn tại
     * @return Mask|MaskCollection|\Illuminate\Support\Collection|\Saola\Core\odels\Model|null
     */
    final protected function relation($key, $loadIfNotExists = false)
    {
        if (array_key_exists($key, $this->relationMap)) {
            // đã được load
            if (array_key_exists($key, $this->relations)) {
                return $this->relations[$key];
            }
            // chưa dược load
            elseif ($loadIfNotExists) {
                $relation = $this->parseRelation(
                    $this->model->{$key},
                    $this->relationMap[$key]
                );
                $this->relations[$key] = $relation;
                return $relation;
            }
        }
        return [];
    }

    /**
     * lấy ra tất cả các quan hệ đã được load và được khái báo
     *
     * @return array
     */
    final public function getRelationsLoaded()
    {
        $data = [];
        if ($this->relations) {
            if ($this->relationKeyTransformType == 'snake')
                foreach ($this->relations as $key => $relation) {
                    if (array_key_exists($key, $this->relationMap)) {
                        $data[Str::snake($key)] = $relation;
                    }
                }
            else
                foreach ($this->relations as $key => $relation) {
                    if (array_key_exists($key, $this->relationMap)) {
                        $data[$key] = $relation;
                    }
                }
        }
        return $data;
    }

    /**
     * lấy dữ liệu qua hê đã được map
     *
     * @param string $key
     * @return bool
     */
    final protected function checkRelation($key)
    {
        return array_key_exists($key, $this->relationMap) && array_key_exists($key, $this->relations);
    }

    /**
     * lấy dữ liệu qua hê đã được map
     *
     * @param string $key
     * @return bool
     */
    final protected function hasRelation($key)
    {
        return array_key_exists($key, $this->relations);
    }

    public function sub($column = null, $length = 0, $after = '')
    {
        if (is_string($column) && is_string($a = $this->{$column})) {
            $a = strip_tags($a);
            if (!$length || $length >= strlen($a)) return $a;
            $b = substr($a, 0, $length);
            $c = explode(' ', $b);
            $d = array_pop($c);
            $e = implode(' ', $c);
            $f = $e . $after;
        } else {
            $f = null;
        }
        return $f;
    }

    // public function toFormData()
    // {
    //     if($this->model){
    //         return $this->toFormData();
    //     }
    //     return [];
    // }



    /**
     * set thuoc tinh cho toan bo item
     *
     * @param string|array $attr
     * @param mixed $value
     * @return $this
     */
    public function set($attr, $value = null, $setInModel = false)
    {
        if (is_array($attr)) $data = $attr;
        elseif (is_string($attr) || is_numeric($attr)) {
            $data = [$attr => $value];
        }
        if ($setInModel) {
            foreach ($data as $key => $value) {
                $this->data[$key] = $value;
                $this->model->{$key} = $value;
            }
            return $this;
        }
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
        return $this;
    }



    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): ArrayIterator
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
        if (array_key_exists($name, $this->alias)) {
            $name = $this->alias[$name];
        }
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        if (!$this->isLock && array_key_exists($name, $this->relationMap) && $this->hasRelation($name)) {
            return $this->relation($name);
        }
        if (!$this->isLock || in_array($name, $this->accessAllowed) && is_a($this->model, Model::class)) {
            return $this->model->{$name};
        }
        if (array_key_exists($name, $this->relationMap)) {
            return $this->relation($name, !$this->isLock);
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
        return isset($this->data[$key]) ? true : (
            (!$this->isLock || in_array($key, $this->accessAllowed)) ? isset($this->model->{$key}) : (
                (array_key_exists($key, $this->relationMap) && array_key_exists($key, $this->relations)) ? true : false
            )
        );
    }
    /**
     * đếm phần tử
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
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



    public function toArray()
    {
        if (!$this->isLock) {
            return $this->model->toArray();
        }
        $related = $this->getRelationsLoaded();
        $relations = [];
        $data = [];

        $hidden = $this->hidden;
        if ($hidden && is_array($hidden)) {
            foreach ($related as $key => $value) {
                if (!in_array($key, $hidden)) {
                    if (method_exists($value, 'toArrayData'))
                        $relations[$key] = $value->toArrayData();
                    else
                        $relations[$key] = $value->toArray();
                }
            }
            $raw = [];
            foreach ($this->data as $key => $value) {
                if (!in_array($key, $hidden)) {
                    $raw[$key] = $value;
                }
            }
            $data = array_merge($raw, $relations);
        } else {
            foreach ($related as $key => $value) {
                if (method_exists($value, 'toArrayData'))
                    $relations[$key] = $value->toArrayData();
                else
                    $relations[$key] = $value->toArray();
            }
            $raw = array_merge($this->data, $relations);
            $data = $raw;
        }
        return array_map(function ($value) {

            if (is_a($value, static::class)) {
                return $value->toArray();
            } elseif (is_object($value)) {
                if (method_exists($value, 'toArrayData')) {
                    return $value->toArrayData();
                } elseif (method_exists($value, 'toDeepArray')) {
                    return $value->toDeepArray();
                } elseif ($value instanceof Arrayable) {
                    return $value->toArray();
                } elseif (is_callable([$value, 'toArray'])) {
                    return $value->toArray();
                }
            }

            return $value;
        }, $data);
    }

    public function toDeepArray()
    {
        return array_map(function ($value) {

            if (is_a($value, static::class)) {
                return $value->toDeepArray();
            } elseif (is_object($value)) {
                if (is_callable([$value, 'toDeepArray'])) {
                    return $value->toDeepArray();
                } elseif (is_object($value) && is_callable([$value, 'toArrayData'])) {
                    return $value->toArrayData();
                } elseif ($value instanceof Arrayable) {
                    return $value->toArray();
                } elseif (is_callable([$value, 'toArray'])) {
                    return $value->toArray();
                }
            }

            return $value;
        }, $this->toArray());
    }


    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }



    public function entityArray($array = [])
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->entityArray($value);
                } elseif (is_numeric($value)) {
                } elseif (is_string($value)) {
                    $array[$key] = htmlentities($value);
                }
            }
        }
        return $array;
    }

    public function toColumnData()
    {
        $data = $this->toArray();
        $data = $this->entityArray($data);
        return $data;
    }

    public function __toString()
    {
        return $this->toJson();
    }



    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
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
        if (array_key_exists($name, $this->alias)) {
            $name = $this->alias[$name];
        }
        if ((!$this->isLock || in_array($name, $this->accessAllowed)) && method_exists($this->model, $name)) {
            if (!in_array($name, ['init', 'onLoaded'])) {
                return call_user_func_array([$this->model, $name], $arguments);
            }
            return $this;
        }
        return array_key_exists($name, $this->data) ? $this->data[$name] : (isset($arguments[0]) ? $arguments[0] : null);
    }

    public static function __callStatic($name, $arguments)
    {
        # code...
    }
}
