<?php

namespace Saola\Core\Routing;

/**
 * Module
 * @method Module module(string $slug, callable|string $callback) add module
 * @method Router router(string $slug, callable|string $callback) add router
 * @method Router child(string $slug, callable|string $callback) add child router
 * @method Router get(string $uri, callable|string $callback) add get route
 * @method Router post(string $uri, callable|string $callback) add post route
 * @method Router put(string $uri, callable|string $callback) add put route
 * @method Router delete(string $uri, callable|string $callback) add delete route
 * @method Router priority(int $priority) set priority
 * @method Module group(callable(Module $module) $callback) do action in group
 * @method Module controller(string $controller) set controller class 
 * @method Module as(string $name) set name
 * @method Module generateAdminRoute() generate admin route
 * @method Module generateRESTfulRoute() generate RESTful route
 * @property int $priority
 */
class Module extends Router
{

    protected $aliasMethods = [
        'generateAdminRoute' => ['genadminroute', 'generateAdminRoute'],
        'generateRESTfulRoute' => ['genrestfulroute', 'generateRESTfulRoute'],
        'addSubModule' => ['module', 'submodule', 'sub'],
        'doGroupAction' => ['group'],
        'addChild' => ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'any', 'match'],
        'addChildRouter' => ['router', 'route', 'child', 'children'],
        'setRouteAttribute' => [
            'name',
            'slug',
            'as',
            'title',
            'description',
            'middleware',
            'permission',
            'prefix',
            'controller',
            'display_name',
            'display',
            'displayname',
            'type',
            'priority'  // Thêm priority
        ],
    ];
    
    public function __construct($data = [], $parent = null)
    {
        parent::__construct($data, $parent);
        $this->data['type'] = 'module';
        
        // Set default priority nếu không có
        if (!isset($this->data['priority'])) {
            $this->data['priority'] = 100; // Priority mặc định
        }
    }
    
}
