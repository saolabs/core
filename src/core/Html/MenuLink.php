<?php

namespace Saola\Core\Html;

use Saola\Core\Helpers\Any;

class MenuLink extends HtmlDom{

    protected $icons = [];

    public $text = null;

    public function __construct($url = '#', $text = 'Menu Item', $attrs = [])
    {
        $attrs['href'] = $url;
        $t = Html::span($text);
        $this->text = $t;
        parent::__construct('a', $t, $attrs);
        


    }

    public function addIcon($name = 'icon', $pos = 'prepend', $icon)
    {
        if(!in_array(strtolower($pos), ['append', 'prepend', 'before', 'after'])) return false;
        call_user_func_array([$this, $pos], [$icon]);
        $this->icons[$name] = $icon;
    }

    public function getIcon($name = null)
    {
        if(is_null($name)){
            return $this->icons;
        }
        return isset($this->icons[$name])?$this->icons[$name]:null;
    }
}
