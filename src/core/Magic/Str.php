<?php

/**
 * @author DoanLN
 * @date 2019-04-28
 * @description 
 * 
 */

namespace Saola\Core\Magic;

use Saola\Core\Files\Filemanager;

define('CRAZY_LANGUAGE_PATH', dirname(dirname(dirname(dirname(__FILE__)))).'/language');

class Str {
    protected static $lang = 'vi';
    protected static $langData = null;
    /**
     * set language
     * @param string $lang
     */
    public static function setLang($lang = null){
        if(($lang && $lang != static::$lang) || !static::$langData){
            static::$lang = $lang;
            $file = new Filemanager(CRAZY_LANGUAGE_PATH);
            if($data = $file->getJson(static::$lang, true)){
                static::$langData = $data;
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * kiểm ta xem xem dc set hay chua
     */
    public static function checkLang()
    {
        if(!static::$langData) return static::setLang(static::$lang);
        return true;
    }

    /**
     * xoa dau tieng viec
     * @param string $string
     * @return string
     */
    public static function clearVi(string $string)
    {
        if(static::checkLang()){
            return str_replace(static::$langData->vi, static::$langData->en, $string);
        }
        return $string;
    }

    /**
     * xoa dau tieng viec
     * @param string $string
     * @return string
     */
    public static function jsonVi(string $string)
    {
        if(static::checkLang()){
            return str_replace(static::$langData->jsc, static::$langData->vi, $string);
        }
        return $string;
    }

    /**
     * viet thuong chu 
     * @param string $string
     * @return string
     */
    public static function vnToLower(string $string)
    {
        if(static::checkLang()){
            return str_replace(static::$langData->upper, static::$langData->lower, $string);
        }
        return $string;
    }

    /**
     * viet hoa chu 
     * @param string $string
     * @return string
     */
    public static function vnToUpper(string $string)
    {
        if(static::checkLang()){
            return str_replace(static::$langData->lower, static::$langData->upper, $string);
        }
        return $string;
    }

    /**
     * chuan so
     * @param string $value
     */
    public static function toNumber($value)
    {
        if (is_numeric($value)) {
            $number = (((Int) $value) == $value) ? ((Int) $value) : ((float) $value);
            return $number;
        }
        return 0;
    }

}