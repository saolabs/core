<?php
namespace Saola\Core\Engines;

Class DCryptEngine{
    protected $key = null;
    protected $_k = null;
    public function __construct()
    {
        $this->key = env('APP_KEY').$_SERVER['SCRIPT_FILENAME'].$_SERVER['SCRIPT_NAME'].__FILE__;
    }

    public function enkey($str)
    {
        $r = substr(md5(uniqid().time()), 2, 8);
        // 1
        $a = base64_encode($this->_k.$str);
        // echo $a;
        $a = substr($a, 0, 5).$r.substr($a, 5);//
        $a = strrev($a); // 
        
        $a = '$'.strrev($r).base64_encode($a);// 
        
        $a = $a.substr(uniqid(), 0, 4);
        $a = base64_encode($a); // 
        $a = base64_encode(strrev(substr($a, 0, 30)).strrev(substr($a, 30)));//
        
        $a = strrev('$='.$a.$r.$this->_k);//
        $a .= strrev($_SERVER['SCRIPT_FILENAME'].$_SERVER['PHP_SELF']);

        $a = base64_encode($a); //
        $a = '$'. substr(md5(base64_encode(md5($a)).uniqid()), 0, 14).base64_encode($a.$r);
        $a = '$'. substr(md5(base64_encode(md5($a)).uniqid()), 0, 14).base64_encode(strrev($a.$r));
        // $a = '$'.strrev(base64_encode(substr($a, 0, 20).rand(1111, 9999))) . strrev(base64_encode(substr($a, 20).rand(1111, 9999)));
        return $a;
    }

    public function encode($str)
    {
        $r = substr(md5(uniqid().time()), 2, 8);
        // 1
        $a = base64_encode($this->key.$str);
        // echo $a;
        $a = substr($a, 0, 5).$r.substr($a, 5);//
        $a = strrev($a); // 
        
        $a = '$'.strrev($r).base64_encode($a);// 
        
        $a = $a.substr(uniqid(), 0, 4);
        $a = base64_encode($a); // 
        $a = base64_encode(strrev(substr($a, 0, 30)).strrev(substr($a, 30)));//
        
        $a = strrev('$='.$a.$r.$this->key);//
        $a = base64_encode($a); //
        $a = '$'. substr(md5(base64_encode(md5($a)).uniqid()), 0, 14).base64_encode($a.$r);
        $a = '$'. substr(md5(base64_encode(md5($a)).uniqid()), 0, 14).base64_encode(strrev($a.$r));
        // $a = '$'.strrev(base64_encode(substr($a, 0, 20).rand(1111, 9999))) . strrev(base64_encode(substr($a, 20).rand(1111, 9999)));
        return $a;
    }

    public function decode($str)
    {

        $a = $str;

        
        $a = substr($a, 15);
        $a = base64_decode($a);
        $a = strrev($a);
        $a = substr($a, 0, strlen($a)-8);
        
        
        $a = substr($a, 15);
        $a = base64_decode($a);
        $a = substr($a, 0, strlen($a)-8);
        $a = base64_decode($a);
        // $a = base64_decode($a);
        
        $a = strrev($a);
        
        $a = substr($a, 2, strlen($a)-10-strlen($this->key));
        
        // $a = base64_encode(strrev(substr($a, 0, 30)).strrev(substr($a, 30)));
        $a = base64_decode($a);
        // strrev(substr($a, 0, 30)).strrev(substr($a, 30))
        $a = strrev(substr($a, 0, 30)).strrev(substr($a, 30));
        // $a = base64_encode($a.substr(uniqid(), 4));

        $a = base64_decode($a);
        
        $a = substr($a, 0, strlen($a)-4);
        // $a = '$'.strrev($r).base64_encode($a);
        $a = base64_decode(substr($a, 9));
        // $a = substr($a, 0, 5).$r.substr($a, 5);
        $a = strrev($a);
        $a = substr($a, 0, 5).substr($a, 5+8);
        // echo "\n$a";
        
        // $a = base64_encode($this->key.$str);
        $a = substr(base64_decode($a), strlen($this->key));
        // die;
        
        return $a;
    }
}