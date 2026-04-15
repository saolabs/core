<?php

namespace Saola\Core\Html;

use Saola\Core\Helpers\Any;

class Html extends HtmlDom{

    
    /**
     * render
     */
    public function render($Action = null)
    {
        return parent::render($Action);
    }
    
    public function __toString()
    {
        $a = clone $this;
        return $a->render();
    }

    /**
     * tao nhanh doi tuong
     * @param string $tagName thẻ html
     * @param array $arguments
     */
    
    public static function make($tagName, $arguments = [])
    {
        // danh savh the don
        $simpleTag = ['link','img','input','meta','br','hr','base','param','source'];
        if(!is_array($arguments)) $arguments = [];
        // thu tu thuoc tinh cua the
        $tag_properties = [
            'a' => ['href', 'innercontent', 'properties'],
            'img' => ['src', 'properties'],
            'link' => ['href', 'rel',  'properties'],
            'input' => ['type', 'name', 'value', 'properties'],
            'textarea' => ['name', 'innercontent', 'properties'],
            'meta' => ['name', 'content', 'properties'],
        ];
        

        $n = strtolower($tagName);
        $tag = null;
        if(isset($tag_properties[$n])){
            $prt = [];
            $prop = [];
            $inc = null;
            
            foreach($arguments as $ind => $val){
                if(array_key_exists($ind,$tag_properties[$n])){
                    $na = $tag_properties[$n][$ind];
                    if($na == 'innercontent'){
                        $inc = $val;
                    }elseif($na == 'properties'){
                        $prop = $val;
                    }
                    else{
                        $prt[$na] = $val;
                    }
                }
            }
            $tag = new static($n,$inc,array_merge($prop, $prt));
        }else{
            $tag = new static($n,...$arguments);
        }
        return $tag;

    
    }

    
    /**
     * yao nhanh doi tuong
     * @param string $name ther html
     * @param array $arguments
     */
    
    public static function __callStatic($name, $arguments)
    {
        return static::make($name, $arguments);
    }
}
