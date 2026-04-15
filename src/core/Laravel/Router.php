<?php

/**
 * @author DoanLN
 * @date 2019-03-01
 * cho phép lay Thông tin cac route cua laravel
 * 
 */

namespace Saola\Core\Laravel;

use Illuminate\Support\Facades\Route;

class Router{
    //
    protected static $routes = [];
    protected static $route_methods = [];
    protected static $route_names = [];
    protected static $route_prefixes = [];
    
    public static function init()
    {
        if(count(static::$routes) > 0) return true;
        $routes = Route::getRoutes();
        foreach($routes as  $route){
            $routeArr = static::getRouteInfo($route);
            static::addRoute($routeArr);
        }
    }

    /**
     * lay Thông tin route
     * @param Route
     * @return array
     */
    public static function getRouteInfo($route)
    {
        $routeArr = ['uri'=>'','methods'=>null,'name'=>null, 'prefix'=>null, 'controller' => null, 'uses' => null];
        $inarr = ['uri','methods'];
        foreach($route as $k => $v){
            if (in_array($k,$inarr)) {
                $routeArr[$k] = $v;
            }elseif($k=='action'){
                foreach ($v as $_k => $_v) {
                    if($_k == 'as'){
                        $routeArr['name'] = $_v;
                    }elseif(array_key_exists($_k, $routeArr)){
                        $routeArr[$_k] = $_v;
                    }
                }
            }
        }
        return($routeArr);
    }


    /**
     * láy ra tất cả các route của laravel đã được khai báo tại thời diểm gọi hàm
     *
     * @return array
     */
    public static function all(){
        //
        if(count(static::$routes)> 0) return static::$routes;
        static::init();
        return static::$routes;
    }

    /**
     * thêm route vào danh sách
     *
     * @param array $route
     * @return void
     */
    protected static function addRoute($route)
    {
        foreach($route['methods'] as $method){
            if (array_key_exists($method, static::$route_methods)) {
                static::$route_methods[$method] = [];
            }
            static::$route_methods[$method][$route['uri']] = $route;
        }
        static::$routes[$route['uri']] = $route;
        if($route['name']){
            static::$route_names[$route['name']] = $route['uri'];
        }
        if($route['prefix']){
            static::$route_prefixes[$route['prefix']] = $route['prefix'];
        }
    }

    /**
     * lấy ra tát cả các route đã được convert
     *
     * @param string|array $method
     * @return void
     */
    public static function getRoutes($method=null)
    {
        static::init();
        if($method){
            if (array_key_exists($method, static::$route_methods)) {
                return static::$route_methods[$method];
            }
            return null;
        }
        return static::all();
    }


    /**
     * lấy ra route theo name
     *
     * @param string $name
     * @return array|null
     */
    public static function getByName($name=null){
        if(!$name) return null;
        static::init();
        if (array_key_exists($name, static::$route_names)) {
            $route_uri = static::$route_names[$name];
            return static::$routes[$route_uri];
        }
        return null;
    }

    
    /**
     * lấy ra các route dạng select option theo uri
     *
     * @return array
     */
    public static function getSelectUri(){
        $arr = [];
        if($routes = static::all()){
            foreach($routes as $route){
                $arr[$route['uri']] = $route['uri'];
            }
        }
        return $arr;
    }


    /**
     * lấy ra các route dạng select option theo name
     *
     * @return array
     */
    public static function getSelectName(){
        $arr = [];
        static::init();
        $routes = static::$route_names;
        if($routes){
            foreach($routes as $name => $uri){
                $arr[$name] = $name;
            }
        }
        return $arr;
    }

    /**
     * lấy ra các route dạng select option theo prefix
     *
     * @return array
     */
    
    public static function getSelectPrefix(){
        return static::$route_prefixes;
    }

    /**
     * lấy ra các route dạng select option name => uri
     *
     * @return array
     */
    
    public static function getSelectNameAndUri(){
        $arr = [];
        static::init();
        $routes = static::$route_names;
        if($routes){
            foreach($routes as $name => $uri){
                $arr[$name] = $uri;
            }
        }
        return $arr;
    }
    /**
     * lấy ra các route dạng select option cho menu
     *
     * @return array
     */
    
    public static function getSelectNameAndUriMenu(){
        $arr = [];
        static::init();
        $routes = static::$route_names;
        if($routes){
            foreach($routes as $name => $uri){
                if(!preg_match('/^(admin\.|user\.|api\.)/i', $name))
                $arr[$name] = $uri;
            }
        }
        return $arr;
    }

    /**
     * lấy thông timn route theo uri
     *
     * @param string $uri
     * @return array|null
     */
    public static function getByUri($uri=null){
        if(!$uri) return null;
        static::init();
        if (array_key_exists($uri, static::$routes)) {
            return static::$routes[$uri];
        }
        return null;
    }

    /**
     * kiểm tra uri có tồn tại hay ko
     *
     * @param string $uri
     * @return bool
     */
    public static function checkUri($uri=null){
        if(!$uri) return null;
        static::init();
        if (array_key_exists($uri, static::$routes)) {
            return true;;
        }
        return false;
    }

    /**
     * kiểm tra tên route có tồn tại hay ko
     *
     * @param string $name
     * @return bool
     */
    public static function checkName($name=null){
        if(!$name) return null;
        static::init();
        if (array_key_exists($name, static::$route_names)) {
            return true;;
        }
        return false;
    }
    /**
     * kiểm tra prefix
     *
     * @param string $prefix
     * @return bool
     */
    public static function checkPrefix($prefix=null){
        if(!$prefix) return null;
        static::init();
        if (array_key_exists($prefix, static::$route_prefixes)) {
            return true;;
        }
        return false;
    }
    
}