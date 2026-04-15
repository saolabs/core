<?php

namespace Saola\Core\Html;

use Countable;
use Saola\Core\Magic\Arr;

use ArrayAccess;

use ArrayIterator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;

class Form extends HtmlDom implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{
    /**
     * @var array chứa các doi tuong input
     */
    protected $_data = [];

    protected $options = [];

    protected $input_list = [];


    /**
     * @var object cac thong bao loi cua laravel
     */

    protected $errors = null;

    /**
     * @var Arr du lieu form dang nang
     */

    protected $form_data = null;

    /**
     * @var array mang input
     */

    protected $inputs = null;

    protected $actions = [];

    /**
     * @var array $inGroup
     */
    protected $inGroup = [];

    /**
     * @var array $notInGroup
     */
    protected $notInGroup = [];

    /**
     * @var array $namespace_list
     */
    protected $namespace_list = [];


    /**
     * @var array $availableForGroup
     */
    protected $availableForGroup = ['select', 'deepselect', 'checkbox', 'number', 'text'];

    /**
     * khoi tao doi tuong form
     * @param array $args
     * @param array $options cac option
     * @param array $attrs
     */
    public function __construct($args, $options = null, $attrs = [])
    {
        $form_data = isset($args['data']) ? $args['data'] : [];
        $inputs = isset($args['inputs']) ? $args['inputs'] : [];
        $errors = isset($args['errors']) && is_object($args['errors'])? $args['errors'] : (new Arr);
        $this->form_data = is_a($form_data, 'Arr') ? $form_data : (new Arr($form_data));
        $this->errors = $errors;
        $this->inputs = $inputs;

        $this->options = $options;

        $this->prepare();

        parent::__construct('form', $this->_data, $attrs);


        if ($this->has(['type' => 'file'])) {
            $this->attr("enctype", "multipart/form-data");
        }
        if ($this->hidden_id) {
            $this->inGroup[] = 'hidden_id';
        }
        $this->checkInGroup();
        $this->checkInGroupAndLock();
        $this->map('prepareCrazyInput');

        $this->attr('id', str_replace('.', '-', $this->attr('id')));
    }

    /**
     * thiết lập input
     * @return void
     */
    public function prepare()
    {
        if (!count($this->inputs)) return false;
        $inputs = $this->inputs;
        $data = $this->form_data;
        $errors = $this->errors;
        $i = 0;
        $actionType = $this->data('action-type');
        foreach ($inputs as $nsp => $inp) {
            // tao doi tuong truy cap input


            $input = new Arr($inp);

            if (($actionType == 'create' && ($input->hideOnCreate || $input->showOnUpdate)) || ($actionType == 'updadte' && ($input->HideOnUpdate || $input->showOnCreate))){
                continue;
            }
            if (!$input->name) $input->name = $nsp;
            // lay tên input
            $name = $input->name;

            // set id neu trong
            if (!$input->id) {
                $input->id = str_replace(['[', ']'], ['-', ''], $name);
            }

            // set label
            if (!$input->label) {
                if ($input->text) {
                    $input->label = $input->text;
                } else {
                    $input->label = $input->name;
                }
            }

            // set placholder
            if (!$input->placeholder && in_array($this->type, ['text', 'textarea', 'email', 'number', 'search'])) {
                $input->placeholder = $input->label;
            }

            $namespace = $this->addNamespace($input->namespace ?? $name);
            $input->namespace = $namespace;
            // old val
            $inputonly = $input->get('@inputonly');
            if (!$inputonly || $inputonly != "false") {
                $old = old($namespace);
                if($input->key){
                    $v = $data->get($input->key);
                    if(is_array($v) || is_bool($v) || strlen($v)){
                        $old = $v;
                    }
                }
                $vl = ($old !== null) ? $old : $data->get($namespace);
                if ($vl !== null) $input->value = $vl;
            }else{
                $input->value = null;
            }

            // error
            if ($errors->has($namespace)) {
                $input->error = $errors->first($namespace);
            }

            $htmlInput = new Input($input->all());
            $htmlInput->setOption($this->options);
            $htmlInput->parent = $this;
            $htmlInput->checkDefaultValueDynamic();
            $this->_data[$namespace] = $htmlInput;
            $i++;
        }
    }

    /**
     * khai báo name space
     * @param string $name
     */
    public function addNamespace($name)
    {
        if (preg_match('/(\[\]|\[\*\]|\[[^\]]\])/i', $name)) {
            $n = str_replace('[]', '[*]', $name);
            $nspace = str_replace(['[', ']'], ['.', ''], str_replace('[*]', '.*', $n));

            if (isset($this->namespace_list[$nspace])) {
                $k = $this->namespace_list[$nspace];
                $this->namespace_list[$nspace]++;
            } else {
                $this->namespace_list[$nspace] = 1;
                $k = 0;
            }
            $namespace = str_replace('.*', '.' . $k, $nspace);
        } else {
            $namespace = str_replace(['[', ']'], ['.', ''], str_replace('[]', '.*', $name));
        }
        return $namespace;
    }

    public function setArrayRootName($name)
    {
        # code...
    }

    /**
     * set thuoc tinh cho input
     * @param string|array $attr thuoc tinh
     * @param string|array|float|int|boolean $value gia tri hoặc mảng
     * @param array $condition
     */
    public function setAttributes($attr, $value = null, array $condition = [])
    {
        // duet mang input de lay phan tu
        foreach ($this->_data as $name => $input) {
            // nếu tồn tại mang dieu kien
            if (is_array($condition) && count($condition) && !$this->checkCondition($input, $condition)) break;
            $input->attr($attr, $value);
        }
        return $this;
    }

    /**
     * kiem tra dieu kien de lay input
     */
    protected function checkCondition($input, array $condition = [])
    {
        if (is_array($condition) && count($condition)) {
            // duyet mang dieu kien de lay thuoc tinh va gia tri
            foreach ($condition as $prop => $value) {
                if (is_array($value)) {
                    // neu gia tri la mot mang thi kiem tra thuoc tinh cua input co nam trong mang gia tri da cho hay ko
                    if (count($value) && !in_array($input->$prop, $value)) {
                        return false;
                    }
                } elseif ($input->$prop != $value) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * kiểm tra xem input có nằm trong group nào hay ko?
     * rồi set vào mang để danh dau
     *
     */
    public function checkInGroup($lock = false)
    {
        if ($this->_data) {
            foreach ($this->_data as $name => $input) {
                if (!isset($this->inGroup[$name])) {
                    if ($input->append_group) {
                        $group = [];
                        if (is_string($input->append_group)) $group[] = $input->append_group;
                        elseif (is_array($input->append_group)) $group = $input->append_group;
                        $input->append_group = [];
                        if ($group) {
                            $inputs = $this->get($group);
                            foreach ($inputs as $namespace => $inp) {
                                if (in_array($inp->type, $this->availableForGroup) && !in_array($namespace, $this->notInGroup)) {
                                    $this->inGroup[] = $namespace;
                                    $input->appendGroup[$namespace] = $inp;
                                    $inp->append_group = [];
                                    $inp->prepend_group = [];
                                    if (!in_array($name, $this->notInGroup) && $lock) {
                                        $this->notInGroup[] = $name;
                                    }
                                }
                            }
                        }
                    } // end if append group
                    if ($input->prepend_group) {
                        $group = [];
                        if (is_string($input->prepend_group)) $group[] = $input->prepend_group;
                        elseif (is_array($input->prepend_group)) $group = $input->prepend_group;
                        $input->prepend_group = [];
                        if ($group) {
                            $inputs = $this->get($group);
                            foreach ($inputs as $namespace => $inp) {
                                if (in_array($inp->type, $this->availableForGroup) && !in_array($namespace, $this->notInGroup)) {
                                    $this->inGroup[] = $namespace;
                                    $input->prependGroup[$namespace] = $inp;
                                    $inp->append_group = [];
                                    $inp->prepend_group = [];
                                    if (!in_array($name, $this->notInGroup) && $lock) {
                                        $this->notInGroup[] = $name;
                                    }
                                }
                            }
                        }
                    } // end if prepend group

                    if ($input->hidden_group) {
                        $group = [];
                        if (is_string($input->hidden_group)) $group[] = $input->hidden_group;
                        elseif (is_array($input->prepend_group)) $group = $input->hidden_group;
                        $input->hidden_group = [];
                        if ($group) {
                            foreach ($this->get(...$group) as $namespace => $inp) {
                                if (!in_array($namespace, $this->notInGroup)) {
                                    $this->inGroup[] = $namespace;
                                    $input->hiddenGroup[$name] = $inp;
                                    $inp->append_group = [];
                                    $inp->prepend_group = [];
                                }
                            }
                        }
                    } // emd if hidden group

                    if ($input->append_columns) {
                        $group = [];
                        if (is_array($input->append_columns)) $group = $input->append_columns;
                        $input->append_columns = [];
                        if ($group) {
                            $inputs = $this->get($group);
                            foreach ($inputs as $namespace => $inp) {
                                if (in_array($inp->type, $this->availableForGroup) && !in_array($namespace, $this->notInGroup)) {
                                    $this->inGroup[] = $namespace;
                                    $input->appendColumns[$name] = $inp;
                                    $inp->append_columns = [];
                                    $inp->prepend_columns = [];
                                    if (!in_array($name, $this->notInGroup) && $lock) {
                                        $this->notInGroup[] = $name;
                                    }
                                }
                            }
                        }
                    } // end if prepend group
                    if ($input->prepend_columns) {
                        $group = [];
                        if (is_array($input->prepend_columns)) $group = $input->prepend_columns;
                        $input->prepend_columns = [];
                        if ($group) {
                            $inputs = $this->get($group);
                            foreach ($inputs as $namespace => $inp) {
                                if (in_array($inp->type, $this->availableForGroup) && !in_array($namespace, $this->notInGroup)) {
                                    $this->inGroup[] = $namespace;
                                    $input->prependColumns[$name] = $inp;
                                    $inp->append_columns = [];
                                    $inp->prepend_columns = [];
                                    if (!in_array($name, $this->notInGroup) && $lock) {
                                        $this->notInGroup[] = $name;
                                    }
                                }
                            }
                        }
                    } // end if append group
                }
            }
        }
    }

    /**
     * kiểm tra xem input có nằm trong group nào hay ko?
     * rồi set vào mang để danh dau
     *
     */
    public function checkInGroupAndLock()
    {
        if ($this->_data) {
            foreach ($this->_data as $name => $input) {
                if (!in_array($name, $this->inGroup)) {
                    $this->notInGroup[] = $name;
                }
            }
        }
    }

    /**
     * lấy thông tin input
     * @param string $name
     * @return array|object|null
     */
    public function get(...$names)
    {
        if (is_array($names) && $t = count($names)) {
            if ($t == 1) {
                if (is_string($names[0])) return $this->{$names[0]};
            }
            if (is_array($names[0])) $list = $names[0];
            else $list = $names;
            $inputs = [];
            foreach ($list as $name) {
                if (array_key_exists($name, $this->_data)) {
                    $inputs[$name] = $this->_data[$name];
                }
            }
            return $inputs;
        }
        return $this->_data;
    }


    /**
     * lấy tất cả các inputs
     *
     * @return void
     */
    public function inputs()
    {
        return $this->_data;
    }

    /**
     * lấy thông tin input ko thuộc group nào
     * @param string
     * @return array|object|null
     */
    public function notInGroup(...$names)
    {
        $data = [];
        if ($inputs = $this->get(...$names)) {
            if (is_array($inputs)) {
                foreach ($inputs as $namespace => $inp) {
                    if (!in_array($namespace, $this->inGroup) && !$inp->hiddenData('hidden')) $data[$namespace] = $inp;
                }
            } elseif (!in_array($inputs->name, $this->inGroup)) {
                return $inputs;
            }
        }
        return $data;
    }

    /**
     * lấy input theo dieu kiện
     * @param array $condition
     * @return array
     */
    public function getByCondition(array $condition = [])
    {
        $data = [];
        // duet mang input de lay phan tu
        foreach ($this->_data as $name => $input) {
            // nếu tồn tại mang dieu kien
            if (is_array($condition) && count($condition) && !$this->checkCondition($input, $condition)) continue;
            $data[$name] = $input;
        }
        return $data;
    }


    /**
     * them hanh dong truoc khi render
     * @param \Cloure $action
     * @param array $condition
     */
    public function addAction($action, array $condition = [])
    {
        if (is_callable($action)) {
            foreach ($this->_data as $name => $input) {
                // nếu tồn tại mang dieu kien
                if (is_array($condition) && count($condition) && !$this->checkCondition($input, $condition)) continue;
                $input->addAction($action);
            }
        }
        return $this;
    }

    /**
     * gọi ham của tất cả các input
     * @param string $method
     * @param array ...$params các tham số là 1 mảng
     */
    public function map($method, ...$params)
    {
        $data = [];
        if (is_callable($method) && gettype($method) != 'string') {
            foreach ($this->_data as $name => $input) {
                $method($input);
            }
        } else {
            foreach ($this->_data as $name => $input) {

                if (method_exists($input, $method)) $data[] = call_user_func_array([$input, $method], $params);
            }
        }

        return $this;
    }

    /**
     * tạo một input list theo dieo kien da cho
     * @param array $condition
     */
    public function query(array $condition = [])
    {
        $instance = new static([], $this->form_data, $this->errors, $this->options);
        if ($list = $this->getByCondition($condition)) {
            foreach ($list as $name => $input) {
                $instance->{$name} = $input;
            }
        }
        return $instance;
    }

    /**
     * kiểm tra xem trong form có input như diều kiện đã cho ko
     * @param array $condition
     * @return boolean
     */
    public function has(array $condition = [])
    {
        return $this->getByCondition($condition) ? true : false;
    }


    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }
    public function __get($name)
    {
        $namespace = str_replace(['[', ']'], ['.', ''], str_replace('[]', '.*', $name));
        if (array_key_exists($namespace, $this->_data)) {
            return $this->_data[$namespace];
        }
        return null;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }




    public function offsetSet($offset, $value):void
    {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }

    public function offsetExists($offset):bool
    {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset):void
    {
        unset($this->_data[$offset]);
    }

    public function offsetGet($offset):mixed
    {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }


    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator():ArrayIterator
    {
        return new ArrayIterator($this->_data);
    }


    public function count():int
    {
        return count($this->_data);
    }

    public function formData($key = null){
        if($key != null) return $this->form_data->get($key);
        return $this->form_data->all();
    }


    public function toArray()
    {
        return array_merge($this->_attrs, [
            'data' => $this->form_data,
            'inputs' => $this->inputs()
        ]);
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
    public function jsonSerialize():array
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

    public function __call($method, $params)
    {
        if (in_array($mt = strtolower($method), ['enctype', 'method', 'action', 'class', 'classname'])) {
            return $this->Attr($mt, ...$params);
        }
    }
}
