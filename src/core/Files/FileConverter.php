<?php

namespace Saola\Core\Files;
use Saola\Core\Magic\Arr;
use Saola\Core\Magic\Str;
trait FileConverter{

    public function parseCode($string)
    {
        return str_replace(["\\/", "\/"], "/", Str::jsonVi(json_encode($string, JSON_UNESCAPED_UNICODE)));
    }
    /**
     * convert array to array string
     * @param array $array
     * @param string $tab
     * @param int $tab_space
     */
    public function toArrayStrinng($array = null, string $tab = '', $tab_space = 4)
    {
        if(!is_array($array) && !is_object($array)) return $this->parseCode($array);
        if(is_object($array)) $array = Arr::parse($array);
        $nsp = '';
        $rawTab = $tab;
        for($i=0; $i < $tab_space; $i++){$nsp.=' ';}
        $tab .= $nsp;
        $string = "[\n";
        $t = count($array) - 1;
        $i = 0;
        if(Arr::isNumericKeys($array)){
            foreach($array as $value){
                if($i==0){
                    $string .= $tab;
                }
                if(is_array($value)){
                    $string .= $this->toArrayStrinng($value, $tab, $tab_space);
                    if($i<$t){
                        $string.= ",\n$tab";
                    }else{
                        $string.= "\n";
                    }
                    
                }else{
                    $string .= $this->parseCode($value);
                    if($i<$t){
                        $string.= ", ";
                    }else{
                        $string.= "\n";
                    }
                }
                
                $i++;
            }
        }
        else{
            foreach($array as $key => $value){
                $string .= $tab.json_encode($key) . ' => ';
                if(is_array($value)){
                    $string .= $this->toArrayStrinng($value, $tab, $tab_space);
                }else{
                    $string .= $this->parseCode($value);
                }
                if($i<$t){
                    $string.= ",";
                }
                $string.= "\n";
                $i++;
            }
        }
        return $string.$rawTab.']';
    }

    /**
     * convert json to php
     * @param string $json_file
     * @param string $php_file
     * @return bool
     */
    public function convertJsonToPhp(string $json_file, $php_file = null) : bool
    {
        if(!$php_file) $php_file = rtrim($json_file,'.json');
        $string = '<'.'?php'."\r\nreturn ";
        if($array = $this->getJson($json_file)){
            $string .= $this->toArrayStrinng($array);
        }else{
            $string .= '[]';
        }
        $string.=';';
        return $this->save($php_file, $string, 'php')?true:false;
    }
}