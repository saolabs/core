<?php

namespace Saola\Core\System;

use Saola\Core\Files\Filemanager;
use Saola\Core\Magic\Arr;

class System
{
    /**
     * Undocumented variable
     *
     * @var Filemanager
     */
    protected static $filemanager;
    /**
     * package
     *
     * @var array<string, Arr>
     */
    protected static $packages = [];

    protected static $routes = [];

    protected static $menus = [];

    /**
     * app info
     *
     * @var Arr
     */
    public static $_appinfo = null;
    /**
     * get filemanager with path
     *
     * @param string $path
     * @return Filemanager
     */
    public static function fm($path = null)
    {
        if (!static::$filemanager) {
            static::$filemanager = new Filemanager();
        }
        static::$filemanager->setDir($path);
        return static::$filemanager;
    }

    /**
     * them package
     *
     * @param string $name
     * @param string|array $path
     * @param array $data
     * @return bool
     */
    public static function addPackage($name, $path, $data = []): bool
    {
        if (array_key_exists($name, static::$packages)) {
            static::$packages[$name] = static::$packages[$name]->merge(is_array($path) ? $path : $data);
            return true;
        } else {
            if (is_array($path)) {
                if (array_key_exists('path', $path) && is_dir($path['path'])) {
                    static::$packages[$name] = new Arr(array_merge([
                        'path' => $path['path'],
                        'routes' => [
                            'admin' => [],
                            'client' => [],
                            'api' => []
                        ],
                        'menus' => [
                            'admin' => [],
                            'client' => []
                        ]
                    ], is_array($path) ? $path : [], is_array($data) ? $data : [], ['path' => $path['path']]));
                    return true;
                } elseif (array_key_exists('dir', $path) && is_dir($path['dir'])) {
                    static::$packages[$name] = new Arr(array_merge([
                        'path' => $path['dir'],
                        'routes' => [
                            'admin' => [],
                            'client' => [],
                            'api' => []
                        ],
                        'menus' => [
                            'admin' => [],
                            'client' => []
                        ]
                    ], is_array($path) ? $path : [], is_array($data) ? $data : [], ['path' => $path['dir']]));
                    return true;
                }
            } elseif (is_string($path) && is_dir($path)) {
                static::$packages[$name] = new Arr(array_merge([
                    'path' => $path,
                    'routes' => [
                        'admin' => [],
                        'client' => [],
                        'api' => []
                    ],
                    'menus' => [
                        'admin' => [],
                        'client' => []
                    ]
                ], is_array($data) ? $data : [], [
                    'path' => $path
                ]));
                return true;
            }
        }
        return false;
    }

    /**
     * them package
     *
     * @param string $name
     * @param string|array $path
     * @param array $data
     * @return bool
     */
    public static function register($name, $path, $data = []): bool
    {
        return static::addPackage($name, $path, $data);
    }

    public static function getPackagePath($package)
    {
        return array_key_exists($package, static::$packages) ? static::$packages[$package]['path'] : null;
    }
    
    public static function getPackageDir($package)
    {
        return array_key_exists($package, static::$packages) ? static::$packages[$package]['path'] : null;
    }



    public static function installPackage($package)
    {
        //
    }

    public static function updatePackage($package)
    {
        # code...
    }

    public static function uninstallPackage($package)
    {
        # code...
    }


    /**
     * lấy route của các package
     *
     * @return array<string,array<string,array>>
     */
    public static function getAllRoutes()
    {
        // $routes = [];

        foreach (static::$packages as $slug => $package) {
            if (array_key_exists($slug, static::$routes)) continue;
            $path = $package->path;
            $routePath = $path . '/src/routes/';
            if ($package->routes) {
                $routes = $package->routes;
                $data = [];
                foreach ($routes as $scope => $route) {
                    if (is_array($route)) {
                        foreach ($route as $key => $file) {
                            if (is_array($file)) {
                                if (array_key_exists('file', $file) && is_file($routePath . $scope . '/' . $file['file'])) {
                                    $r = [
                                        'prefix' => is_numeric($key) ? '' : $key,
                                        'group' => $routePath . $scope . '/' . $file['file'],
                                        'middleware' => array_key_exists('middleware', $file) ? $file['middleware'] : '',
                                        'name' => array_key_exists('name', $file) ? $file['name'] : (array_key_exists('as', $file) ? $file['as'] : '')
                                    ];
                                    if (!array_key_exists($scope, $data)) $data[$scope] = [];
                                    $data[$scope][] = $r;
                                }
                            } else {
                                $f = $file;
                                $mw = '';
                                if (count($p = explode(':', $f)) == 2) {
                                    $mw = explode(',', str_replace(' ', '', $p[1]));
                                    $f = $p[0];
                                }
                                if (is_file($routePath . $scope . '/' . $f)) {

                                    $r = [
                                        'prefix' => is_numeric($key) ? '' : $key,
                                        'group' => $routePath . $scope . '/' . $f,
                                        'middleware' => $mw,
                                        'name' => is_numeric($key) ? '' : $key
                                    ];

                                    if (!array_key_exists($scope, $data)) $data[$scope] = [];
                                    $data[$scope][] = $r;
                                }
                            }
                        }
                    }
                }
                static::$routes[$slug] = $data;
            }
        }
        return static::$routes;
    }

    /**
     * lấy danh sách menu của package
     *
     * @param string $menuScope
     * @return array
     */
    public static function getMenus($menuScope = null)
    {
        if (!static::$menus) {
            $menus = [
                'admin' => [],
                'client' => []
            ];
            foreach (static::$packages as $slug => $package) {
                // if(array_key_exists($slug, static::$routes)) continue;
                $path = $package->path;
                $jsonPath = $path . '/src/json/';

                $menus = $package->menus;
                if (count($menus)) {
                    foreach ($menus as $scope => $scopeMenus) {
                        if (is_array($scopeMenus)) {
                            foreach ($scopeMenus as $key => $value) {
                                if (is_array($value)) {
                                    $menus[$scope][$key] = $value;
                                } else {
                                    if (is_file($path = $jsonPath . $scope . '/menus/' . $value) && $data = json_decode(file_get_contents($path), true)) {
                                        $menus[$scope] = $data;
                                    }
                                }
                            }
                        } elseif (is_file($path = $jsonPath . $scope . '/menus/' . $scopeMenus) && $data = json_decode(file_get_contents($path), true)) {
                            $menus[$scope] = $data;
                        }
                    }
                }
            }
            static::$menus = $menus;
        }
        if($menuScope){
            return array_key_exists($menuScope, static::$menus)?static::$menus[$menuScope]:[];
        }
        return static::$menus;
    }

    public static function appInfo()
    {
        if(!static::$_appinfo) static::$_appinfo = static::fm(base_path())->getJson('app.json', true);
        return static::$_appinfo;
    }

    public static function updateAppInfo($key, $value = __RANDOM_VALUE__)
    {
        if(is_array($key)){
            static::appInfo();
            static::$_appinfo->merge($key);
            static::fm(base_path())->saveJson('app.json', static::$_appinfo->all());
        }
        if($value != __RANDOM_VALUE__){
            static::appInfo();
            static::$_appinfo->set($key, $value);
            static::fm(base_path())->saveJson('app.json', static::$_appinfo->all());
        }
    }
}
