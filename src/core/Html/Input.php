<?php

namespace Saola\Core\Html;

use Saola\Core\Files\Filemanager;
use Saola\Core\Magic\Arr;

class Input extends HtmlDom
{
    use InputTypes;

    protected $_attrs = [
        'type' => null,
        'name' => null,
        'id'   => null,
        'className' => null,
        'placeholder' => 'Viết gì đó'
    ];
    protected $_data = [
        'namespace'           => null,
        'value'               => '<!---DEFAULT--->',
        'default'             => null,
        'action'              => '',
        'data'                => [],
        'data_docs'           => [],
        'data_value_type'     => 'key',
        'func'                => '',
        'file'                => '',
        'call'                => '',
        'params'              => [],
        'param_list'          => [],
        'text'                => '',
        'label'               => '',
        'label_class'         => '',
        'check_label'         => '',
        'error'               => null,
        'error_class'         => null,
        'comment'             => '',
        'template'            => '',
        'template_type'       => '',
        'is_free'             => false,
        'hidden_group'        => [],
        'prepend_group'       => [],
        'append_group'        => [],
        'prepend_columns'     => [],
        'append_columns'      => [],
        'append_text'         => '',
        'prepend_text'        => '',
        'append_button'       => '',
        'prepend_button'      => '',
    ];

    protected $templatePath = null;

    protected $orginalData = [];

    public $parent = null;

    public $hiddenGroup = [];

    public $prependGroup = [];

    public $appendGroup = [];

    public $prependColumns = [];

    public $appendColumns = [];

    protected $config = [];




    /**
     * create instance 
     *
     * @param array $args
     */
    public function __construct($args = [], $form = null)
    {
        if (is_array($args)) {

            foreach ($args as $key => $value) {
                if( $key != 'value' && is_string($value) && preg_match('/^@[A-z_]+[A-z0-9_]*\(.*?\)$/', $v = trim($value)) && $fnDAta = Template::parseFunctionCall($v)){
                    $args[$key] = Template::callTplFunc($fnDAta);
                }
            }

            parent::__construct(isset($args['type']) ? $args['type'] : 'input');
            $this->orginalData = $args;
            $this->config = $args;
            if($form){
                $this->parent = $form;
            }
            $this->setOption($args);
        } elseif (is_string($args)) {
            parent::__construct('input');
            $this->_data['type'] = $args;
        }
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function onSetAttribute($attr, $value)
    {
        if(count($action = explode(':', $attr)) == 2){
            if(in_array($ac = strtodate($action[1]), ['create', 'update'])){
                if($this->parent && $this->parent->data('action-type') == $ac){
                    return ['name' => $action[0], 'value' => $value];
                }
                return false;
            }
        }
    }




    public function setTemplatePath($path = null)
    {
        if ($path && is_string($path)) {
            $this->templatePath = rtrim($path, '.') . '.';
        }
    }

    public function render($action = null)
    {
        if ($this->templatePath && $this->template) {
            if (view()->exists($this->templatePath . $this->template)) {
                return view($this->templatePath . $this->template, ['input' => $this])->render();
            }
        }
        $this->tagName = 'input';
        if (is_callable($action)) {
            $action($this);
        }

        $data = $this->_data;
        $type = $this->get('type');

        switch (strtolower($type)) {
            case 'select':
                $this->attr('type', null);
                $this->tagName = 'select';

                $opts = $this->getInputData();
                if (is_array($opts) || is_object($opts)) {

                    $df = $this->defVal();
                    $slt = $this->toSelectOptions($opts, $df);



                    $this->html($slt);
                }


                return parent::render();
                break;
            case 'multilevelselect':
                $this->attr('type', null);
                $this->tagName = 'select';

                $opts = $this->getInputData();
                if (is_array($opts) || is_object($opts)) {

                    $df = $this->defVal();
                    $slt = $this->toSelecLeveltOptions($opts, $df);



                    $this->html($slt);
                }


                return parent::render();
                break;
            case 'multiselect':
                $this->attr('type', null);
                $this->tagName = 'select';

                $opts = $this->getInputData();
                if (is_array($opts) || is_object($opts)) {
                    $df = $this->defVal();
                    $slt = self::toMultipleSelectOptions($opts, $df);
                    $this->html($slt);
                }


                return parent::render();
                break;

            case 'crazyselect':
                $this->attr('type', null);
                $this->tagName = 'div';

                $def = [];
                $opts = $this->getInputData();
                if (is_array($opts) || is_object($opts)) {

                    $df = $this->defVal();
                    $slt = $this->toCubeSelectOptions($opts, $df);
                    $def = $this->getDefaultOption($opts, $df);


                    $this->html($slt);
                }
                $select_type = $this->data('select-type');
                if (!$select_type) {
                    $select_type = 'static';
                    $this->data('select-type', $select_type);
                }
                $this->data('id', $this->id);
                $this->prepend('
                <input type="hidden" name="' . $this->name . '" value="' . ($def ? htmlentities($def[0]) : '') . '" id="' . $this->id . '" class="' . $this->className . ' d-none" />
                <div class="btn-group dropdown deep-select-group ' . $select_type . '">
                    <button type="button" class="btn btn-secondary dropdown-toggle show-text-value" id="' . $this->id . '-dropdown" value="' . ($def ? htmlentities($def[0]) : '') . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    ' . ($def ? $def[1] : "Chưa chọn giá trị") . '
                    </button>
                    <div class="dropdown-menu" data-ref="' . $this->id . '">
                    
                        <div class="search-block p-2">
                            <input type="search" data-name="search_options" class="form-control m-input" placeholder="' . ($this->placeholder ? $this->placeholder : 'Tìm kiếm...') . '" />
                        </div>
                        <div class="message p-2 text-center" style="display:none;">Không có kết quả phù hợp</div>
                        <div class="buttons p-2 text-center" style="display:none;"><a href="javascript:void(0);" class="btn btn-info btn-add-item">Thêm</a></div>
                        <div class="option-list">
                ');
                $this->append('</div></div></div>');
                $this->removeClass('form-control');
                $this->addClass('crazy-select');

                $this->name = null;
                $this->id .= "-wrapper-select";


                return parent::render();
                break;

            case 'radio':
                $opts = $this->getInputData();
                $slt = '';
                $className = $this->className??($this->class_name??($this->classname??$this->class));
                $this->attr('type', null);
                $this->tagName = 'div';
                $this->className = 'inp-radio-group crazy-radio';

                $change = $this->hidden('change');
                if(!$change) $change = $this->data('on-change');
                if(!$change) $change = $this->data('change');
                if(!$change) $change = $this->attr('on-change');
                
                if ($change) {
                    $this->addClass('crazy-radio-group');
                    $this->attr('crazy-on-change', $change);
                    $this->attr('on-change', $change);
                }
                // die('');
                
                if (is_array($opts) || is_object($opts)) {
                    $properties = $this->_attrs;
                    $df = $this->defVal();
                    foreach ($opts as $k => $v) {
                        $slt .= ' <label class="inp-label checkbox-label ' . $this->label_class . ' pr-3" id="radio-label-' . $this->name . '--' . $k . '"><input type="radio" name="' . $this->name . '" value="' . htmlentities($k) . '"' . (($df == $k) ? ' checked="checked"' : "") . ' class="' . $className . '"';
                        $slt .= '> <span></span> <i>' . htmlentities($v) . '</i></label>';
                    }
                    $this->html($slt);
                }
                return parent::render();
                break;
            case 'checkbox':
                $check = $this->defVal();
                if ($check && strtolower($check) != 'off') {
                    $this->attr('checked', 'checked');
                }
                if ($this->is_free) return parent::render();
                $this->before('<label class="checkbox-label ' . $this->label_class . '">');
                $this->after(' <span class="check-spacing"></span> <i class="checkbox-label-span">' . htmlentities($this->_data['check_label']) . '</i></label>');
                return parent::render();
                break;
            case 'check':
                $this->attr('type', 'checkbox');
                $check = $this->defVal();
                if ($check && strtolower($check) != 'off') {
                    $this->attr('checked', 'checked');
                }
                if ($this->is_free) return parent::render();
                $this->after('<label class="checkbox-label ' . $this->label_class . '" for="' . $this->id . '"> <span class="check-spacing"></span> <i class="checkbox-label-span">' . $this->_data['check_label'] . '</i></label>');
                return parent::render();
                break;
            case 'checklist':
                $properties = $this->_attrs;
                unset($properties['value']);
                $tag = self::checkliat($this->name, $this->getInputData(), $this->defVal(), $properties);
                return $tag->render();
                break;
            case 'textarea':
                //
                $this->attr('type', null);
                $this->tagName = $type;
                if (!$this->attr('placeholder')) {
                    $this->attr('placeholder', ($this->_data['label'] ? $this->_data['label'] : $this->_data['text']));
                }
                $this->text($this->defVal());
                return parent::render();
                break;
            default:
                if (!$this->attr('placeholder')) {
                    $this->attr('placeholder', ($this->_data['label'] ? $this->_data['label'] : $this->_data['text']));
                }
                $this->attr('value', $this->defVal());

                return parent::render();
                break;
        }
        return null;
    }

    public function raw()
    {
        $obj = clone $this;
        $obj->is_free = true;
        return $obj;
    }



    public function toSelectOptions($opts, $default = null, $html = '')
    {
        $slt = $html;
        $df = $default;
        foreach ($opts as $k => $v) {
            if (is_array($v)) {
                $opt = $v;
                $lbl = $k;

                if ((array_key_exists('label', $v) || array_key_exists('text', $v)) && ((array_key_exists('data', $v)  && is_array($v['data'])) || (array_key_exists('list', $v) && is_array($v['list'])))) {
                    $lbl = isset($v['label']) ? $v['label'] : $v['text'];
                    $opt = isset($v['data']) ? $v['data'] : $v['list'];
                }
                $slt .= '<optgroup label="' . $lbl . '">';
                $slt = $this->toSelectOptions($opt, $default, $slt);

                $slt .= '</optgroup>';
            } else {
                $slt .= '<option id="' . $this->id . '-' . $k . '" value="' . $k . '"' . (($df == $k) ? ' selected="selected"' : "") . '>' . $v . '</option>';
            }
        }
        return $slt;
    }
    public function toSelecLeveltOptions($opts, $default = null, $html = '', $nbsp = '')
    {
        $slt = $html;
        $df = $default;
        $ld = is_string($default) ? strlen($default) : 0;
        foreach ($opts as $k => $v) {
            $isActive = ($ld == strlen($k) && $df == $k);
            if (is_array($v)) {
                $opt = $v;
                $lbl = $k;

                if ((array_key_exists('label', $v) || array_key_exists('text', $v)) && ((array_key_exists('data', $v)  && is_array($v['data'])) || (array_key_exists('list', $v) && is_array($v['list'])))) {
                    $lbl = isset($v['label']) ? $v['label'] : $v['text'];
                    $opt = isset($v['data']) ? $v['data'] : $v['list'];
                }

                $slt .= '<option id="' . $this->id . '-' . $k . '" value="' . $k . '"' . (($isActive) ? ' selected="selected"' : "") . '>' . $nbsp . $lbl . '</option>';
                $slt = $this->toSelecLeveltOptions($opt, $default, $slt, $nbsp . '&nbsp;&nbsp;&nbsp;&nbsp;');
            } else {
                $slt .= '<option id="' . $this->id . '-' . $k . '" value="' . $k . '"' . (($isActive) ? ' selected="selected"' : "") . '>' . $nbsp . $v . '</option>';
            }
        }
        return $slt;
    }

    public static function toMultipleSelectOptions($opts, $default = null, $html = '')
    {
        $slt = $html;
        $df = $default;
        if ($df) {
            if (!is_array($df)) {
                $df = [$df];
            }
        } else {
            $df = [];
        }

        foreach ($opts as $k => $v) {
            if (is_array($v)) {
                $opt = $v;
                $lbl = $k;

                if ((array_key_exists('label', $v) || array_key_exists('text', $v)) && ((array_key_exists('data', $v)  && is_array($v['data'])) || (array_key_exists('list', $v) && is_array($v['list'])))) {
                    $lbl = isset($v['label']) ? $v['label'] : $v['text'];
                    $opt = isset($v['data']) ? $v['data'] : $v['list'];
                }
                $slt .= '<optgroup label="' . $lbl . '">';
                $slt = self::toMultipleSelectOptions($opt, $default, $slt);

                $slt .= '</optgroup>';
            } else {

                $slt .= '<option value="' . $k . '"' . (in_array($k, $df) ? ' selected="selected"' : "") . '>' . $v . '</option>';
            }
        }
        return $slt;
    }

    public function toCubeSelectOptions($opts, $default = null, $slt = '', $level = 0, $template = null)
    {
        $group_header = $this->getOptionLabelType();
        if (!$template) $template = '<a href="javascript:void(0);" data-value="{$value}" data-text="{$hetext}" class="dropdown-item option-item {$class} {$active}">{$text}</a>';
        //die($group_header);
        $df = $default;

        $ld = (is_string($default) || is_numeric($default)) ? strlen($default) : 0;

        foreach ($opts as $k => $v) {
            $isActive = ($ld == strlen($k) && $df == $k);
            if (is_array($v)) {
                $opt = $v;
                $lbl = $k;

                if ((array_key_exists('label', $v) || array_key_exists('text', $v)) && ((array_key_exists('data', $v)  && is_array($v['data'])) || (array_key_exists('list', $v) && is_array($v['list'])))) {
                    $lbl = isset($v['label']) ? $v['label'] : $v['text'];
                    $opt = isset($v['data']) ? $v['data'] : $v['list'];
                }
                $slt .= '<div class="option-group">';

                if ($group_header != 'value') {
                    $slt .= "<h6 class=\"dropdown-header\">$lbl</h6>";
                } else {
                    $slt .= str_eval($template, [
                        'value' => $k,
                        'text' => $lbl,
                        'hetext' => htmlentities(strip_tags($lbl)),
                        'active' => ($isActive ? 'active' : ''),
                        'class' => 'dropdown-header option-header'
                    ]);
                }
                $slt = $this->toCubeSelectOptions($opt, $default, $slt, $level + 1, $template);

                $slt .= '</div>';
            } else {
                $isActive = ($ld == strlen($k) && $df == $k);
                $slt .= str_eval($template, [
                    'value' => $k,
                    'text' => $v,
                    'hetext' => htmlentities(strip_tags($v)),
                    'active' => ($isActive) ? 'active' : '',
                    'class' => ''
                ]);
            }
        }
        return $slt;
    }
    public function toDeepSelectOptions($opts, $default = null, $slt = '', $level = 0, $template = null)
    {
        return $this->toCubeSelectOptions($opts, $default, $slt, $level, $template);
    }

    public function toCrazySelectOptions($opts, $default = null, $slt = '', $level = 0, $template = null)
    {
        return $this->toCubeSelectOptions($opts, $default, $slt, $level, $template);
    }

    public function toClientSelectOptions($opts, $default = null, $slt = '', $level = 0, $template = null)
    {
        $group_header = $this->getOptionLabelType();
        if (!$template) $template = '<a href="javascript:void(0);" data-value="{$value}" data-text="{$hetext}" class="select-option-item option-item {$class} {$active}">{$text}</a>';
        //die($group_header);
        $df = $default;
        foreach ($opts as $k => $v) {
            if (is_array($v)) {
                $opt = $v;
                $lbl = $k;

                if ((array_key_exists('label', $v) || array_key_exists('text', $v)) && ((array_key_exists('data', $v)  && is_array($v['data'])) || (array_key_exists('list', $v) && is_array($v['list'])))) {
                    $lbl = isset($v['label']) ? $v['label'] : $v['text'];
                    $opt = isset($v['data']) ? $v['data'] : $v['list'];
                }
                $slt .= '<div class="option-group">';

                if ($group_header != 'value') {
                    $slt .= "<h6 class=\"select-option-header\">$lbl</h6>";
                } else {
                    $slt .= str_eval($template, [
                        'value' => $k,
                        'text' => $lbl,
                        'hetext' => htmlentities(strip_tags($lbl)),
                        'active' => (($df == $k) ? 'active' : ''),
                        'class' => 'select-option-header option-header'
                    ]);
                }
                $slt = $this->toClientSelectOptions($opt, $default, $slt, $level + 1, $template);

                $slt .= '</div>';
            } else {
                $slt .= str_eval($template, [
                    'value' => $k,
                    'text' => $v,
                    'hetext' => htmlentities(strip_tags($v)),
                    'active' => ($df == $k) ? 'active' : '',
                    'class' => ''
                ]);
            }
        }
        return $slt;
    }


    public function getDefaultOption($opts, $default = null, $arr = [])
    {
        $group_header = $this->getOptionLabelType();
        foreach ($opts as $k => $v) {
            if (is_array($v)) {
                $opt = $v;
                $lbl = $k;

                if ((array_key_exists('label', $v) || array_key_exists('text', $v)) && ((array_key_exists('data', $v)  && is_array($v['data'])) || (array_key_exists('list', $v) && is_array($v['list'])))) {
                    $opt = isset($v['data']) ? $v['data'] : $v['list'];
                    $lbl = isset($v['label']) ? $v['label'] : $v['text'];
                }
                if ($group_header == 'value' && $default == $k) {
                    return [$k, $lbl];
                }
                $arr = $this->getDefaultOption($opt, $default, $arr);
            } else {
                if ($default == $k || !count($arr)) {
                    $arr = [$k, $v];
                }
            }
        }
        return $arr;
    }

    protected function getOptionLabelType()
    {
        $group_label = 'header';
        $label_type = $this->option_label_type ? $this->option_label_type : $this->hiddenData('label-type');
        if ($label_type && in_array(($t = strtolower($label_type)), ['header', 'value', 'label'])) {
            $group_label = $t;
        }
        return $group_label;
    }

    /**
     * lấy data input
     */
    public function getInputData($convert_to_array = true)
    {
        $opts = [];
        if ($d = $this->get('data')) {
            if (is_callable($d)) {
                $opts = $d();
            } elseif (is_string($d)) {
                if (is_array($dr = json_decode($d, true))) {
                    $opts = $dr;
                } elseif (count(explode('=', $d)) > 1) {
                    $o = [];
                    try {
                        if (parse_str($d, $a)) {
                            if ($a) {
                                $o = $a;
                            }
                        }
                    } catch (\Exception $err) {
                    }
                    if (!$o && count($arr = explode(',', $d)) > 1) {
                        foreach ($arr as $pr) {
                            if ($s = trim($pr)) {
                                if (count($arp = explode('=', $pr)) == 2) {
                                    $o[trim($arp[0])] = trim($arp[1]);
                                }
                            }
                        }
                    }
                    if (!$o) {
                        $ar = explode('=', $d);
                        $o[trim($ar[0])] = trim($ar[1]);
                    }
                    $opts = $o;
                } else {
                    $o = [];
                    $arr = explode(',', $d);
                    if (count($arr)) {
                        foreach ($arr as $value) {
                            if ($a = trim($value)) {
                                $o[$a] = $a;
                            }
                        }
                    }
                    $opts = $o;
                }
            } else {
                $opts = $d;
            }
        } elseif ($f = $this->get('file')) {
            if ($d = (new Filemanager(base_path('json')))->getJson($f)) {
                $opts = $d;
            }
        } else {
            $cf = null;
            if ($a = $this->get('action')) {
                $cf = $a;
            } elseif ($c = $this->get('call')) {
                $cf = $c;
            } elseif ($f = $this->get('func')) {
                $cf = $f;
            }

            if (is_callable($cf)) {

                if ($p = $this->get('params')) {
                    // dd($p);
                    $params = [];
                    if (!is_array($p)) $p = [$p];


                    foreach ($p as $param) {


                        if (is_string($param)) {

                            $params[] = $this->getParamFromString($param);
                        } elseif (is_array($param)) {
                            $arg = [];
                            foreach ($param as $kn => $par) {
                                $arg[$kn] = $this->getParamFromString($par);
                            }
                            $params[] = $arg;
                        } else {
                            $params[] = $param;
                        }
                    }
                    $opts = call_user_func_array($cf, $params);
                } else {
                    $opts = call_user_func($cf);
                }
            }
        }
        if (!$convert_to_array) return $opts;
        if (!is_array($opts)) $data = Arr::parse($opts);
        else $data = $opts;
        if ($this->data_value_type == 'value') {
            $dataArr = [];
            foreach ($data as $key => $value) {
                $dataArr[$value] = $value;
            }
            return $dataArr;
        }
        return $data;
    }

    public function getParamFromString($param)
    {
        $def = null;
        if (count($a = explode('|', $param)) > 1) {
            $param = $a[0];
            $def = $a[1];
        }

        if (in_array($s = substr($param, 0, 1), ['#', ':', '@'])) {
            $nsp = substr($param, 1);
            if ($s == '#' && $this->parent) {
                $prop = null;
                $inp = $nsp;
                if (count($ip = explode(':', $nsp)) == 2) {
                    $inp = $ip[0];
                    $prop = $ip[1];
                }

                if ($this->parent->{$inp}) {
                    if ($prop) $val = $this->parent->{$inp}->{$prop};
                    else $val = $this->parent->{$inp}->defVal();
                    if ($val === null || (is_string($val) && !strlen($val))) $val = $def;
                    return $val;
                }
            } elseif ($s == ':') {
                if ($nsp == 'defval') {
                    $prop = $this->defVal();
                } else {
                    $prop = $this->{$nsp};
                }

                if ($prop === null || (is_string($prop) && !strlen($prop))) $prop = $def;
                return $prop;
            } elseif (is_callable($nsp)) {
                $val = $nsp();
                if ($val === null || (is_string($val) && !strlen($val))) $val = $def;
                return $val;
            }
        } elseif (is_array($param)) {
            $data = [];
            foreach ($param as $key => $value) {
                $val = $this->getParamFromString($value);
                if ($val === null || (is_string($val) && !strlen($val))) $val = $def;
                $data[$key] = $val;
            }
            return $data;
        }

        return $param;
    }

    // public function getInputDataFromString($raw)
    // {

    // }

    public function defVal()
    {
        $data = $this->_data;
        $v = $data['value'];
        $d = $data['default'];
        $val = ($v !== '<!---DEFAULT--->') ? $v : $d;
        return $val;
    }

    public function val($content = "<!---DEFAULT--->")
    {
        if ($content == "<!---DEFAULT--->") {
            return (($this->_data['value'] != '<!---DEFAULT--->') ? $this->_data['value'] : null);
        }
        $this->_data['value'] = $content;
        return $this;
    }



    public function get($name = null)
    {
        if (is_null($name)) {
            return $this->_data;
        }
        return (array_key_exists($name, $this->_data) ? $this->_data[$name] : (array_key_exists($name, $this->_attrs) ? $this->_attrs[$name] : null));
    }



    public function set($name, $value = null)
    {
        if (is_null($name)) {
            return false;
        }
        if(array_key_exists($name, $this->_data)){
            $this->_data[$name] = $value;
        }
        else{
            $this->_attrs[$name] = $value;
        }
        return true;
    }

    public function reset()
    {
        return $this->setOption($this->orginalData);
    }

    public static function __callStatic($method, $params)
    {
    }

    protected static function checkliat($name, $data = null, $dataCheck = null, $properties = null)
    {
        $tag = new Html('span', null, ['class' => 'checkbox-list checkbox-group']);
        if (is_callable($data)) {
            $opts = $data();
        } else {
            $opts = $data;
        }

        if (is_array($opts) || is_object($opts)) {
            if (is_array($dataCheck)) {
                $check = $dataCheck;
            } else {
                $check = [$dataCheck];
            }
            foreach ($opts as $k => $v) {

                $label = new Html('label', null, ['class' => 'check-item-label inp-label checkbox-label']);
                $checkbox = new Html('input', null, Arr::parse(['type' => 'checkbox', 'name' => $name, 'value' => $k], $properties));
                if (in_array($k, $check)) {
                    $checkbox->checked = true;
                }
                $checkbox->after((new Html('span', null, ['class' => 'checkbox-item-text'])));
                $checkbox->after((new Html('i', $v, ['class' => 'checkbox-item-text'])));
                $checkbox->appendTo($label);
                $label->appendTo($tag);
            }
        }

        return $tag;
    }

    /**
     * khai báo nme space
     * @param string $name
     */
    public function nameToNamespace()
    {
        $name = $this->name;
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

    /**
     * create input tag with text type
     * @param string $name name of input
     * @param mixed $value value of input
     * @param array $properties properties of input
     */

    // public static function text($name,$value='<!---DEFAULT--->',$properties=[])
    // {
    //     $input = new static(Arr::parse(['name'=>$name,'type'=>'text','value'=>$value],$properties));
    //     return $input;
    // }

    /**
     * create input tag with text type
     * @param string $name name of input
     * @param mixed $value value of input
     * @param array $properties properties of input
     */

    public static function number($name, $value = '<!---DEFAULT--->', $properties = [])
    {
        $input = new static(Arr::parse(['name' => $name, 'type' => 'number', 'value' => $value], $properties));
        return $input;
    }

    public function __toString()
    {
        $a = clone $this;
        return $a->render();
    }

    protected function setOpt($name, $value = null)
    {
        $n = strtolower($name);
        if (array_key_exists($n, $this->_data)) {
            $this->_data[$n] = $value;

            if ($n == 'class' || $n == 'classname') {
                return $this->addClass($value);
            }
        } elseif ($n == 'class' || $n == 'classname') {
            return $this->addClass($value);
        } else {
            return $this->attr($name, $value);
        }
    }
}
