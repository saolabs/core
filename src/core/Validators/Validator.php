<?php

namespace Saola\Core\Validators;

use Illuminate\Support\Facades\Validator as BaseValidator;
/**
 * @method array parseInputs(array $data = []) parse input data
 */
abstract class Validator{
    use DefaultMethods;
    /**
     * tao doi tuong validator
     * @param Request $request
     */
    function __construct($request=null, $repository = null)
    {
        $this->init($request, $repository);
        $this->addDefaultRules();
        $this->extends();
        // $this->check();
    }


    /**
     * truy cập phần tử chưa được khai báo
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->request->{$name};
    }

    /**
     * Gọi phương thức chưa được khai báo
     * 
     * Magic method để gọi các method từ request object
     *
     * @param string $method Tên method
     * @param array $params Tham số truyền vào method
     * @return mixed
     */
    public function __call($method, $params)
    {
        return call_user_func_array([$this->request, $method], $params);
    }

    /**
     * khai báo rule
     *
     * @return array
     */
    abstract public function rules();

    /**
     * thông báo lổi
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    public function addErrorMessage($attr, $message)
    {
        $this->__errors[$attr] = $message;
    }

    /**
     * kiểm tra rrquest
     * @param mixed $extraRules
     * @return bool
     */
    public function check($extraRules = null)
    {
        $rules = $this->getRules();
        if(is_array($extraRules)) $rules = array_merge($rules, $extraRules);
        $messages = array_merge($this->defaultMessages,$this->messages());
        $inputs = $this->all();
        $checker = BaseValidator::make($inputs, $rules, $messages);

        if($checker->fails()){
            $errors = [];
            $err = $checker->errors();
            $this->validateErrors = $err;
            
            foreach ($rules as $attr => $rule) {
                $attr_parts = explode('.', $attr);
                $name = $attr_parts[0];
                if($err->has($name)){
                    $errors[$name] = $err->first($name);
                }
                elseif($err->has($attr)){
                    $errors[$attr] = $err->first($attr);
                }
                // elseif(is_array($this->{$attr})){
                //     $errors = $this->checkArrayErrors($err, $attr, $this->{$attr}, $errors);
                // }
            }
            $this->__errors = array_merge($this->__errors, $errors);
        }else{
            $this->__status = true;
        }
        return $this->__status;
    }

    /**
     * lấy đối tượng errors của vaildator
     * @return object
     */
    public function getErrorObject()
    {
        return $this->validateErrors;
    }


    /**
     * lấy thông tin rule
     *
     * @return array
     */
    public function getRules()
    {
        $rules = $this->rules();
        // kiểm tra các trường thông tin dược set hoặc bỏ qua
        if($this->repository && $attrs = $this->repository->getValidateAttrs()){
            $data = [];
            foreach($rules as $attr => $rule){
                if(in_array($attr, $attrs)){
                    $data[$attr] = $rule;
                }elseif(count($a = explode($attr.'.', $attr))>1){
                    if($a[0] == ''){
                        $data[$attr] = $rule;
                    }
                }
            }
            return $data;
        }
        return $rules;
    }

    /**
     * kiểm tra lỗi
     *
     * @param mixed $errors
     * @param array $attr
     * @param array $values
     * @param array $arr
     * @return void
     */
    protected function checkArrayErrors($errors, $attr, $values, $arr = [])
    {
        if(is_array($values)){
            foreach($values as $name => $val){
                if($errors->has($attr.'.'.$name)){
                    $arr[$attr.'.'.$name] = $errors->has($attr.'.'.$name);
                }
                if(is_array($val)){
                    $arr = $this->checkArrayErrors($errors, $attr.'.'.$name, $val, $arr);
                }
            }
        }
        return $arr;
    }

    /**
     * lấy về trạng thái kiểm tra thành công
     *
     * @return bool
     */
    public function success(){
        return $this->__status;
    }

    /**
     * lấy về mảng lỗi
     *
     * @return array
     */
    public function errors()
    {
        return $this->__errors;
    }

    /**
     * trả về mảng dử liệu được validate
     * @return array
     */
    protected $__rules = [];
    public function getAcceptInputs()
    {
        $rules = $this->getRules();
        $raw = $this->request->all();
        $data = [];
        $keys = [];
        $numbers = [];
        $mixed = [];
        $hasVal = [];
        $strdate = [];
        $datetimes = [];
        $boolean = [];
        $datetimes = [];
        $arrdate = [];
        if(count($rules)){
            foreach($rules as $key => $rule){
                $explode = explode('.', $key);
                if(count($explode) == 1){
                    $this->__rules = [];
                    $rs =  array_map(function($v){
                        $b = explode(':', $v);
                        $this->__rules[$b[0]] = count($b) > 1 ? $this->parseParameters(explode(',', $b[1])):[];
                        return $b[0];
                    }, is_array($rule)? $rule : explode('|', str_replace(' ', '', $rule)));
                    
                    if(in_array('strdate', $rs)){
                        $strdate[] = $key;
                        continue;
                    }
                    if(in_array('any_number', $rs)){
                        $numbers[] = $key;
                        continue;
                    }elseif(in_array('mixed', $rs)){
                        $mixed[] = $key;
                        if(!isset($raw[$key])) $data[$key] = null;
                        continue;
                    }elseif(in_array('has_value', $rs)){
                        $hasVal[] = $key;
                        continue;
                    }elseif(in_array('check_boolean', $rs)){
                        $boolean[] = $key;
                        $val = $this->request->{$key};
                        $data[$key] = $val?true:false;
                        continue;
                    }elseif(in_array('binary', $rs)){
                        $boolean[] = $key;
                        $val = $this->request->{$key};
                        $data[$key] = $val?1:0;
                        continue;
                    }elseif(in_array('datetime', $rs)){
                        $format = $this->__rules['datetime'][0]??'datetime';
                        $val = $this->request->{$key};
                        if($val){
                            try {
                                $data[$key] = carbon_datetime($val, $format);
                            } catch (\Throwable $th) {
                                $data[$key] = null;
                            }
                        }
                        continue;
                    }elseif(in_array('strdatetime', $rs)){
                        $datetimes[] = $key;
                        $data[$key] = $this->datetimes[$key]??null;
                        continue;
                    }elseif(in_array('arrdate', $rs)){
                        if( array_key_exists($key, $this->arrdate)){
                            $arrdate[] = $key;
                            $data[$key] = $this->arrdate[$key]??null;
                        }
                        continue;
                    }elseif(in_array('datetimerange', $rs)){
                        $datetimes[] = $key;
                        $data[$key] = $this->datetimes[$key]??null;
                        continue;
                    }
                }
                $keys[] = $explode[0];
                
            }
            foreach ($raw as $name => $value) {
                if(in_array($name, $keys) && !is_null($value)){
                    $data[$name] = $value;
                }elseif(in_array($name, $numbers) && is_numeric($value)){
                    $data[$name] = $value;
                }elseif(in_array($name, $mixed)){
                    $data[$name] = $value;
                }elseif(in_array($name, $hasVal) && !is_null($value) && ((is_array($value) && count($value)) || (is_string($value) && strlen($value)))){
                    $data[$name] = $value;
                }elseif(in_array($name, $strdate) && !is_null($value) && ($date = strtodate($value))){
                    $data[$name] = "$date[year]-$date[month]-$date[day]";
                }elseif(in_array($name, $boolean)){
                    $val = $this->request->{$name};
                    $data[$name] = $val?true:false;
                }
            }

        }else{
            $data = $raw;
        }
        // die(json_encode($data));
        if(method_exists($this,"parseInputs")){
            if(is_array($d = $this->parseInputs($data)) && count($d) > 0){
                $data = $d;
            }
        }

        return $data;
    }

    /**
     * lấy dữ liệu sau khi được validate
     *
     * @return array
     */
    public function inputs()
    {
        return $this->getAcceptInputs();
    }

    /**
     * validate dữ liệu
     *
     * @param array $rules
     * @param array $messages
     * @return array
     */
    public function validate($rules = [], $messages = [])
    {
        $a = $this->request->validate(array_merge($this->getRules(), $rules), array_merge($this->messages(), $messages));
        return $this->inputs();
    }

    public static function make(...$args)
    {
        return BaseValidator::make(...$args);
    }

    public static function extend(...$args)
    {
        return BaseValidator::extend(...$args);
    }
}