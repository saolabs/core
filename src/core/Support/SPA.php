<?php

namespace Saola\Core\Support;

class SPA
{
    protected static $routes = [

    ];
    protected static $isActive = false;
    public static function init()
    {
        //
    }

    public static function isActive()
    {
        return self::$isActive;
    }

    public static function active()
    {
        self::$isActive = true;
    }

    public static function inactive()
    {
        self::$isActive = false;
    }

    public static function addRoute($context, $name, $path, $component = null)
    {
        if (self::$isActive) {
            $params = [];
            preg_match_all('/\{(.*?)\}/i', $path, $matches);
            if (count($matches) > 0) {
                foreach ($matches[1] as $param) {
                    $params[] = $param;
                    // Keep {param} format for JavaScript router
                    // $path = str_replace('{' . $param . '}', ':' . $param, $path);
                }
            }
            
            // Ensure path starts with leading slash
            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
            if(!array_key_exists($context, self::$routes)) {
                self::$routes[$context] = [];
            }
            self::$routes[$context][] = [
                'name' => $name,
                'path' => $path,
                'params' => $params,
                'component' => $component,
            ];
        }
    }

    public static function getRoutes($context = null)
    {
        if(!$context) {
            $allRoutes = [];
            foreach(self::$routes as $ctx => $routes) {
                $allRoutes = array_merge($allRoutes, $routes);
            }
            return $allRoutes;
        }
        return self::$routes[$context]??[];
    }

    public static function getComponentRoutes($context = null)
    {
        if(!$context) {
            $allRoutes = [];
            foreach(self::$routes as $context => $routes) {
                $allRoutes = array_merge($allRoutes, self::getComponentRoutes($context));
            }
            return $allRoutes;
        }
        return array_values(array_filter(self::$routes[$context]??[], function($route) {
            return $route['component'];
        }));
    }

    public static function resetRoutes()
    {
        self::$routes = [];
    }
}
