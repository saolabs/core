<?php

namespace Saola\Core\Html;

use Exception;

class Template
{
    protected static function getType($obj)
    {
        if (is_null($obj)) {
            return 'null';
        } elseif (is_array($obj)) {
            return 'array';
        } elseif (is_object($obj)) {
            return 'object';
        } else {
            return gettype($obj);
        }
    }

    protected static function isNumber($variable)
    {
        return is_numeric($variable);
    }
    protected static function isJsonValid($jsonString)
    {
        $json = json_decode($jsonString);
        if (json_last_error() === JSON_ERROR_NONE) {
            return true;
        } else {
            return false;
        }
    }

    protected static function parseStrFnParams($str, $level = 0)
    {
        $containers = [
            [
                "type" => "string",
                "str" => "",
                "params" => ""
            ]
        ];
        $inFunction = 0;
        $inString = false;
        $strChar = '';
        $currentIndex = 0;
        $needContinue = false;
        $isFreeze = false;
        $strArr = str_split($str);
        $t = count($strArr);
        foreach ($strArr as $i => $c) {
            if ($c == '"' || $c == "'") {
                if ($c == $strChar) {
                    $inString = false;
                    $strChar = '';
                    if ($inFunction) {
                        $containers[$currentIndex]["params"] .= $c;
                    }
                } elseif ($inFunction) {
                    $containers[$currentIndex]["params"] .= $c;
                    if (!$inString) {
                        $inString = true;
                        $strChar = $c;
                    }
                } elseif (!$inString) {
                    $inString = true;
                    $strChar = $c;
                    if (!$inFunction) $containers[$currentIndex]["type"] = 'string';
                } else {
                    $containers[$currentIndex]["str"] .= $c;
                }
            } elseif ($c == " ") {
                if ($inFunction) {
                    $containers[$currentIndex]["params"] .= $c;
                } elseif ($isFreeze) {
                } elseif ($inString) {
                    $containers[$currentIndex]["str"] .= $c;
                }
            } elseif ($c == ',') {
                if ($inFunction) {
                    $containers[$currentIndex]["params"] .= $c;
                } elseif (!$inString) {
                    $val = $containers[$currentIndex]["str"];
                    $type = 'string';
                    if ($val == 'true') {
                        $type = 'boolean';
                        $val = true;
                    } elseif ($val == 'true') {
                        $type = 'boolean';
                        $val = true;
                    } elseif (is_numeric($val)) {
                        $type = 'number';
                        $val = to_number($val);
                    } elseif (static::isJsonValid($val)) {
                        $type = 'array';
                        $val = json_decode($val, true);
                    }
                    $containers[$currentIndex]["str"] = $val;
                    $containers[$currentIndex]["type"] = $type;
                    $currentIndex++;
                    array_push($containers, [
                        "type" => "string",
                        "str" => "",
                        "params" => ""
                    ]);
                    $isFreeze = false;
                } else {
                    if (!$isFreeze)
                        $containers[$currentIndex]["str"] .= $c;
                }
            } elseif ($c == ')') {
                if ($inFunction) {
                    if (!$inString) {
                        $inFunction--;
                        if ($inFunction == 0) {
                            $containers[$currentIndex]["args"] = static::parseStrFnParams($containers[$currentIndex]["params"], $level + 1);
                            $containers[$currentIndex]["str"] = trim($containers[$currentIndex]["str"]);
                            if (is_numeric($containers[$currentIndex]["str"])) {
                                $containers[$currentIndex]["str"] = $containers[$currentIndex]["str"] + 0;
                            }
                            if ($i < $t - 1 && !$level) {
                                $isFreeze = true;
                            } else {

                                $currentIndex++;
                                array_push($containers, [
                                    "type" => "variable",
                                    "str" => "",
                                    "params" => ""
                                ]);
                            }
                        } else {
                            $containers[$currentIndex]["params"] .= $c;
                        }
                    } else {
                        $containers[$currentIndex]["params"] .= $c;
                    }
                } else {
                    $containers[$currentIndex]["str"] .= $c;
                }
            } elseif ($c == '(') {
                if (!$inFunction) {
                    if ($containers[$currentIndex]["str"] != '') {
                        $inFunction++;
                        $containers[$currentIndex]["type"] = 'function';
                    } else {
                        throw new Exception("Invalid syntax at position: " . $i);
                    }
                } else {
                    if (!$inString) $inFunction++;
                    $containers[$currentIndex]["params"] .= $c;
                }
            } elseif ($inFunction) {
                $containers[$currentIndex]["params"] .= $c;
            } else {
                $containers[$currentIndex]["str"] .= $c;
            }
        }

        if ($containers[count($containers) - 1]["str"] == '' && $containers[count($containers) - 1]["type"] == 'variable') {
            array_pop($containers);
        }
        $z = count($containers) - 1;
        if ($containers[$z]['type'] != 'function') {

            $val = $containers[$z]["str"];
            $containers[$z]["str"] = $val == 'true' ? true : ($val == 'false' ? false : (is_numeric($val) ? to_number($val) : (static::isJsonValid($val) ? json_decode($val, true) : $val)));
            // $containers[$z]["type"] = gettype($val);
        }

        return $containers;
    }

    protected static function stringAnalysis($str)
    {
        return static::parseStrFnParams($str);
    }

    public static function parseFunctionCall($str){
        $str = trim($str);
        if(preg_match('/^@[A-z_]+[A-z0-9_]*\(.*?\)$/', $str)){
            $a = static::stringAnalysis(ltrim($str, '@'));
            if(count($a) && $a[0]['type'] == 'function'){
                return ['function' => $a[0]['str'] , 'args' => $a[0]['args']];
            }
        }

        return [];
    }

    protected static function callFn($data){
        if(array_key_exists('type', $data) || array_key_exists('str', $data)){
            if($data['type'] == 'function'){
                return is_callable($data['str'])? call_user_func_array($data['str'], static::parseArgs($data['args']??[])): null;
            }
        }
        return null;
    }

    protected static function parseArgs($data){
        $args = [];
        if(!is_array($data)) return [];
        foreach ($data as $i => $a) {
            if(is_array($a)){
                if(array_key_exists('type', $a) || array_key_exists('str', $a)){
                    if($a['type'] == 'function'){
                        $args[] = static::callFn($a);
                    }else{
                        $args[] = $a['str'];
                    }
                }else{
                    $args[] = $a;
                }
            }else{
                $args[] = $a;
            }
        }
        return $args;
    }

    public static function callTplFunc($fnData){
        if(function_exists($fnData['function']) ||  is_callable($fnData['function'])){
            return call_user_func_array($fnData['function'], static::parseArgs($fnData['args']));
        }
        return null;
    }
}
