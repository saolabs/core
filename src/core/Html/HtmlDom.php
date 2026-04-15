<?php

namespace Saola\Core\Html;

use Saola\Core\Magic\Arr;
use Illuminate\Contracts\Support\Htmlable;

class HtmlDom implements Htmlable
{

    public $tagName = null;

    protected $innerContent = [];

    protected $contentType = 'html';

    protected $_data = [];

    protected $_attrs = [];

    protected $_hiddenData = [];

    protected $_before = [];

    protected $_after = [];

    protected $_children = [];

    protected $_parent = null;

    /**
     * thuc hiên cach hanh dong
     */

    protected $_actions = [];

    private static $simpleTag = ['img', 'meta', 'link', 'input', 'br', 'hr'];

    /**
     * khoi tao doi tuong html
     * @param string $tagName thẻ html
     * @param mixed nội dung
     * @param array $properties thuộc tính của thẻ
     * 
     */
    public function __construct($tag = null, $content = null, $properties = null)
    {
        $t = null;
        $tg = static::parseTag($tag);
        if($tg){
            $t = $tg['tag'];
            $properties = array_merge($tg['attrs'], (array) $properties);
        }else{
            $t = $tag;
        }
        $this->tagName = $t;
        $this->setContent($content);
        $this->attr($properties);
    }

    /**
     * biến string html thanh dom
     *
     * @param string $string
     * @return void
     */
    public static function parseTag($string = null)
    {
        if(preg_match('/^[A-z]+[A-z0-9\-]*$/i', $string)) return [];
        $square = 0;
        $c = 0;
        $detailKey = 'tag';

        $detail = [
            'tag' => '',
            'id' => '',
            'class' => '',
            'attrs' => [],
            'children' => []
        ];

        if (!is_string($string) || !strlen($string)) {
            return [];
        }
        $attrIndex = -1;
        $childrenIndex = -1;
        $children = 0;
        $max = strlen($string);
        $arrChars = str_split($string);
        foreach ($arrChars as $char) {
            if ($square) {
                if (!isset($detail[$detailKey][$attrIndex])) {
                    $detail[$detailKey][$attrIndex] = '';
                }

                if ($char == ']') {
                    $square--;
                    if ($square) {
                        $detail[$detailKey][$attrIndex] .= $char;
                    }
                } else {
                    if ($char == '[') {
                        $square++;
                    }
                    $detail[$detailKey][$attrIndex] .= $char;
                }
            } elseif ($children) {
                if (!isset($detail[$detailKey][$childrenIndex])) {
                    $detail[$detailKey][$attrIndex] = '';
                }

                if ($char == '}') {
                    $children--;
                    if ($children) {
                        $detail[$detailKey][$childrenIndex] .= $char;
                    }
                } else {
                    if ($char == '{') {
                        $children++;
                    }
                    $detail[$detailKey][$attrIndex] .= $char;
                }
            } elseif ($char == '[') {
                $detailKey = 'attrs';
                $attrIndex++;
            } elseif ($char == '{') {
                $children++;
                $detailKey = 'children';
            } elseif ($char == '#') {
                $detailKey = 'id';
            } elseif ($char == '.') {
                $detailKey = 'class';
                $detail[$detailKey][$attrIndex] .= ' ';
            } else {
                $detail[$detailKey][$attrIndex] .= $char;
            }
        }
        $detail['class'] = trim($detail['class']);
        if ($detail['class'] || $detail['id'] || $detail['children'] || $detail['attrs'] || $detail['tag']) {
            $quetes = ['"', "'"];
            if ($detail['tag']) $detail['tag'] = 'dev';
            if ($detail['attrs']) {
                $attrs = $detail['attrs'];
                $attributes = [];
                foreach ($attrs as $attr) {
                    $a = trim($attr);
                    $attrp = explode('=', $a);
                    if (count($attrp) > 1) {
                        $k = array_pop($attrp);
                        $v = implode("=", $attrp);
                        $s = in_array(substr($v, 0, 1), $quetes);
                        $e = in_array(substr($v, -1), $quetes);

                        $attributes[$k] = $s && $e ? substr($v, 1, strlen($v) - 2) : ($s && !$e ? substr($v, 1) : (
                                (!$s && $e) ? substr($v, 0, strlen($v) - 1) : $v));
                    } else {
                        $attributes[$attr] = true;
                    }
                }
                $detail['attrs'] = $attributes;
            }
            if ($detail['children']) {
                $children = $detail['children'];
                $childList = [];
                foreach ($children as $child) {
                    $childList[] = new static($child);
                }
                $detail['children'] = $childList;
            }

            return $detail;
        }

        return [];
    }

    /**
     * thiet lap noi dung
     * @param string|number|instance
     */
    protected function setContent($content = null)
    {
        if (!is_null($content)) {
            if (is_array($content)) {
                $this->innerContent = [];
                foreach ($content as $key => $cnt) {
                    if (is_a($cnt, self::class) || is_string($cnt) || is_numeric($cnt)) {
                        $this->innerContent[] = $cnt;
                    }
                }
            } else {
                $this->innerContent = [$content];
            }
        }
    }

    /**
     * them thuoc tinh 
     * @param array|string $name
     * @param mixed $value
     */
    public function setOption($name = null, $value = null)
    {
        if (is_string($name)) {
            return $this->setOpt($name, $value);
        } elseif (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->setOpt($key, $val);
            }
        }
        return $this;
    }


    protected function setOpt($name, $value = null)
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;
        } else {
            return $this->attr($name, $value);
        }
    }


    public function render($action = null)
    {
        if (is_callable($action)) {
            $action($this);
        }
        $content = (!$this->isSimpleTag()) ? $this->_renderContent() : '';
        $head = $this->_renderBefore() . $this->openTag();
        $foot = $this->closeTag() . $this->_renderAfter();
        $html = $head . $content . $foot;
        return $html;
    }

    public function toHtml()
    {
        return $this->render();
    }

    public function action($action = null)
    {
        if (is_callable($action)) {
            $this->_actions[] = $action;
        } elseif (is_array($action)) {
            foreach ($action as $act) {
                if (is_callable($act)) {
                    $this->_actions[] = $act;
                }
            }
        }
        return $this;
    }

    public function doAction()
    {
        if ($this->_actions) {
            foreach ($this->_actions as $action) {
                if (is_callable($action)) {
                    $action($this);
                }
            }
        }
    }

    public function __toString()
    {
        $a = clone $this;
        $a->doAction();
        return $a->render();
    }

    public function openTag($tag = null)
    {
        if (!$tag) $tag = $this->tagName;
        if ($tag) {
            $html = "<$tag";
            if (count($this->_attrs)) {
                foreach ($this->_attrs as $attr => $val) {
                    if (strtolower($attr) == 'classname') {
                        $attr = 'class';
                        $vv = explode(' ', $val);
                        $cl = array_unique($vv);
                        $val = implode(' ', $cl);
                        $val = trim($val);
                    }
                    if (is_string($val) || is_numeric($val) || $val) {
                        if (is_array($val)) {
                            $html = static::arrayToAttrs($html, $val, $attr);
                        } elseif (strlen($val) || is_numeric($val)) {
                            $html .= ' ' . $attr . '="' . htmlentities($val) . '"';
                        } elseif ($val === true) {
                            $html .= ' ' . $attr;
                        }
                    }
                }
            }

            if ($this->isSimpleTag()) {
                $html .= ' /';
            }

            $html .= ">";
            return $html;
        }
        return null;
    }


    public function closeTag($tag = null)
    {
        if (!$tag) $tag = $this->tagName;
        if ($tag && !$this->isSimpleTag($tag)) {
            return "</$tag>";;
        }
        return null;
    }

    public function isSimpleTag($tag = null)
    {
        if (!$tag) $tag = $this->tagName;
        if (!$tag) return null;
        return in_array($tag, self::$simpleTag);
    }

    public static function arrayToAttrs($html = '', $arr = [], $k = 'data')
    {
        if (is_array($arr)) {
            if ($k != 'style') {
                foreach ($arr as $key => $value) {
                    $kk = $k . '-' . $key;
                    if (is_array($value)) {
                        $html .= static::arrayToAttrs($html, $value, $kk);
                    } elseif ((is_string($value) && strlen($value) > 0) || is_numeric($value) || $value == true) {
                        $html .= ' ' . $kk . '="' . $value . '"';
                    }
                }
            } else {
                $st = '';
                foreach ($arr as $key => $value) {
                    if ((is_string($value) && strlen($value) > 0) || is_numeric($value) || $value == true) {
                        $st .= ' ' . $key . ': ' . $value . '; ';
                    }
                }
                $st = trim($st);
                $html .= ' ' . $k . '="' . $st . '"';
            }
        }
        return $html;
    }

    public function attrsToStr()
    {
        $html = "";
        if (count($this->_attrs)) {
            foreach ($this->_attrs as $attr => $val) {
                if (strtolower($attr) == 'classname') {
                    $attr = 'class';
                    $vv = explode(' ', $val);
                    $cl = array_unique($vv);
                    $val = implode(' ', $cl);
                    $val = trim($val);
                }
                if (is_string($val) || is_numeric($val) || $val) {
                    if (is_array($val)) {
                        $html = static::arrayToAttrs($html, $val, $attr);
                    } elseif (strlen($val) || is_numeric($val)) {
                        $html .= ' ' . $attr . '="' . htmlentities($val) . '"';
                    } elseif ($val === true) {
                        $html .= ' ' . $attr;
                    }
                }
            }
        }
        return $html;
    }


    public function getAttribute($name = null)
    {
        if (is_null($name)) return $this->_attrs;
        return $this->attr($name);
    }

    public function attr($name = null, $value = '<!---DEFAULT--->')
    {
        if (is_string($name)) {
            if ($name == 'class') $name = 'className';
            if ($value === '<!---DEFAULT--->') {
                if (array_key_exists($name, $this->_attrs)) {
                    return $this->_attrs[$name];
                }
                return null;
            }
            return $this->setAttr($name, $value);
        } elseif (is_array($name)) {
            foreach ($name as $k => $v) {
                if (!is_numeric($k)) {
                    $this->setAttr($k, $v);
                }
            }
        }
        return $this;
    }

    public function setAttr($name = null, $value = '<!---DEFAULT--->')
    {
        if ($value !== '<!---DEFAULT--->') {
            if (strtolower($name) == 'class') {
                return $this->addClass($value, true);
            }
            if (substr($name, 0, 1) == '@') {
                $this->_hiddenData[substr($name, 1)] = $value;
                return $this;
            }

            if(method_exists($this, 'onSetAttribute')){
                $status = $this->onSetAttribute($name, $value);

                if(is_array($status)){
                    if(array_key_exists('name', $status) && array_key_exists('value', $status) ){
                        extract($status);
                    }
                    
                }
                elseif($status === false) return $this;
            }

            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $this->_attrs[$name . '-' . $key] = $val;
                }
                return $this;
            }

            $this->_attrs[$name] = $value;
        }

        return $this;
    }

    /**
     * thiết lập parent
     * @param HtmlDom
     * @return HtmlDom
     */
    public function setParent($parent)
    {
        if (is_a($parent, self::class)) {
            $this->_parent = $parent;
        }
        return $this;
    }
    /**
     * parent
     * @return HtmlDom
     */

    public function parent()
    {
        if ($this->_parent) return $this->_parent;
        return (new static());
    }

    /**
     * thêm class cho doi tuong
     * @param string $class
     * 
     */

    public function addClass($class = null, $new = false)
    {
        if (!is_null($class)) {
            if (array_key_exists('className', $this->_attrs)) {
                if (!$new) {
                    $cls = explode(' ', $this->_attrs['className']);
                    $cls[] = $class;

                    $this->_attrs['className'] = implode(' ', array_unique($cls));
                } else {
                    $this->_attrs['className'] = $class;
                }
            } else {
                $this->_attrs['className'] = $class;
            }
        }
        return $this;
    }

    public function removeClass($class = null)
    {

        if (!is_null($class)) {
            if (array_key_exists('className', $this->_attrs)) {
                $cls = explode(' ', $this->_attrs['className']);
                if ($cls) {
                    $t = count($cls);
                    $cl = [];
                    if (is_string($class)) $class = array_map('trim', explode(' ', $class));
                    for ($i = 0; $i < $t; $i++) {
                        if (is_array($class)) {
                            if (!in_array($cls[$i], $class)) {
                                $cl[] = $cls[$i];
                            }
                        }
                    }
                }
                $this->_attrs['className'] = implode(' ', array_unique($cl));
            }
        } else {
            if (array_key_exists('className', $this->_attrs)) {
                $this->_attrs['className'] = '';
            }
        }
        return $this;
    }

    public function hasClass($class = null)
    {
        $cls = explode(' ', $this->_attrs['className']);
        if ($cls) {
            $t = count($cls);
            $cl = [];
            if (is_string($class)) $class = array_map('trim', explode(' ', $class));
            for ($i = 0; $i < $t; $i++) {
                if (is_array($class)) {
                    if (in_array($cls[$i], $class)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


    public function data($name = null, $value = null)
    {
        $d = [];
        if (is_string($name) && !is_null($value)) {
            if (!is_array($value)) {
                $d[$name] = $value;
            } else {
                foreach ($value as $k => $v) {
                    $d[$name . '-' . $k] = $v;
                }
            }
        } elseif (is_array($name)) {
            foreach ($name as $k => $v) {
                $d[$k] = $v;
            }
        }
        if ($d) {
            foreach ($d as $key => $val) {
                $this->_attrs['data-' . $key] = $val;
            }
            return $this;
        }
        $arr = [];
        if ($this->_attrs) {
            $a = strtolower($name);
            foreach ($this->_attrs as $key => $value) {
                if (preg_match('/^data\-/i', $key)) {
                    if ($name) {
                        if (strtolower($key) == 'data-' . $a) {
                            return $value;
                        }
                        $n = str_replace('-', '\-', $a);
                        $p = '/^data\-' . $n . '($|\-)/i';
                        if (preg_match($p, $key)) {
                            $arr[substr($key, 5)] = $value;
                        }
                    }
                }
            }
        }
        if (is_string($name)) return null;
        return $arr;
    }

    /**
     * hidden data
     * @param mixed $name
     * @param mixed $value
     * @return mixed
     */
    public function hiddenData($name = null, $value = '<!-- DEFAULT -->')
    {
        if (is_null($name)) {
            return $this->_hiddenData;
        }
        if (is_array($name)) {
            if (count($name)) {
                if (Arr::isNumericKeys($name)) {
                    $data = [];
                    foreach ($name as $key) {
                        if (array_key_exists($key, $this->_hiddenData)) {
                            $data[$key] = $this->_hiddenData[$key];
                        }
                    }
                    return $data;
                }
                foreach ($name as $key => $val) {
                    $this->_hiddenData[$key] = $val;
                }
            }
            return $this;
        }
        if ($value != '<!-- DEFAULT -->') {
            $this->_hiddenData[$name] = $value;
            return $this;
        }
        return array_key_exists($name, $this->_hiddenData) ? $this->_hiddenData[$name] : null;
    }

    public function hidden($name = null, $value = '<!-- DEFAULT -->')
    {
        return $this->hiddenData($name, $value);
    }

    public function before($content = null)
    {
        if (!is_null($content)) {
            $bf = $this->_before;
            array_unshift($bf, $content);
            $this->_before = $bf;
        }

        return $this;
    }

    public function after($content = null)
    {
        if (!is_null($content)) {
            $this->_after[] = $content;
        }
        return $this;
    }

    public function prepend($content = null)
    {
        if (!is_null($content)) {
            if (is_a($content, self::class)) {
                $content->setParent($this);
            }
            $bf = $this->innerContent;
            array_unshift($bf, $content);
            $this->innerContent = $bf;
        }
        return $this;
    }

    /**
     * thêm nội dung vào cuối
     *
     * @param string|Html|HtmlDom $content
     * @return void
     */
    public function append($content = '')
    {
        if (!is_null($content)) {
            if (is_a($content, self::class)) {
                $content->setParent($this);
            }
            $this->innerContent[] = $content;
        }
        return $this;
    }

    public function prependTo($HtmlDom = null)
    {
        if (is_object($HtmlDom) && is_a($HtmlDom, self::class)) {
            $instance = $this->copy();
            $HtmlDom->prepend($instance);
        }
        return $HtmlDom;
    }

    public function appendTo($HtmlDom = null)
    {
        if (is_object($HtmlDom) && is_a($HtmlDom, self::class)) {
            $instance = $this->copy();
            $HtmlDom->append($this);
        }
        return $HtmlDom;
    }

    public function removeBefore($content = null)
    {
        if ($content) {
            if (($key = array_search($content, $this->_before)) !== false) {
                unset($this->_before[$key]);
            }
        } else $this->_before = [];
        return $this;
    }

    public function removeAfter($content = null)
    {
        if ($content) {
            if (($key = array_search($content, $this->_after)) !== false) {
                unset($this->_after[$key]);
            }
        } else $this->_after = [];
        return $this;
    }

    public function html($content = null)
    {
        if (is_null($content)) {
            return $this->_renderArr($this->innerContent);
        }
        $this->contentType = 'html';
        $this->innerContent = [$content];
        return $this;
    }
    public function text($content = null)
    {
        if (is_null($content)) {
            $cont = $this->_renderArr($this->innerContent);
            return ($this->contentType == 'html') ? htmlentities($cont) : $cont;
        }
        $this->contentType = 'text';
        $this->innerContent = [htmlentities(is_array($content)?json_encode($content, JSON_PRETTY_PRINT):$content)];
        return $this;
    }

    public function _renderContent()
    {
        return $this->_renderArr($this->innerContent);
    }
    public function _renderBefore()
    {
        return $this->_renderArr($this->_before);
    }
    public function _renderAfter()
    {
        return $this->_renderArr($this->_after);
    }

    protected function _renderArr($arr = [])
    {
        $html = '';
        if ($arr) {
            foreach ($arr as $elem) {
                $html .= $elem . '';
            }
        }
        return $html;
    }

    public function copy()
    {
        return clone $this;
    }



    public function remove()
    {
        $this->innerContent = [];
        $this->_before = [];
        $this->_after = [];
        $this->tagName = null;
        $this->_attrs = [];
    }


    public function __set($name, $value)
    {
        $n = strtolower($name);
        if ($n == 'innertext') {
            $this->contentType = 'text';
            $this->text($value);
        } elseif ($n == 'innerhtml') {
            $this->contentType = 'html';
            $this->html($value);
        }
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = $value;
        } else {
            $this->_attrs[$name] = $value;
        }
    }
    public function __get($name)
    {
        $n = strtolower($name);
        if (in_array($n, ['innerhtml', 'innertext'])) {
            if ($n == 'innertext') {
                return $this->text();
            }
            return $this->html();
        }
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        } elseif (array_key_exists($name, $this->_attrs)) {
            return $this->_attrs[$name];
        }
        return null;
    }

    public function __call($name, $arguments)
    {
        return $this;
    }

    public function __isset($name)
    {
        return (isset($this->_attrs[$name]) || isset($this->_data[$name]));
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
        unset($this->_attrs[$name]);
    }
}
