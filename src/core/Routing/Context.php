<?php

namespace Saola\Core\Routing;

use Saola\Core\Routing\Module;
use Saola\Core\Routing\Router;

/**
 * Channel
 * @method Module module(string $slug, callable|string|array $callback) add module
 * @method Router router(string $slug, callable|string $callback) add router
 * @method Router child(string $slug, callable|string $callback) add child router
 * @method Router get(string $uri, callable|string $callback) add get route
 * @method Router post(string $uri, callable|string $callback) add post route
 * @method Router put(string $uri, callable|string $callback) add put route
 * @method Router delete(string $uri, callable|string $callback) add delete route
 * @method Router priority(int $priority) set priority
 * @method Context generateAdminRoute() generate admin route
 * @method Context generateRESTfulRoute() generate RESTful route
 * 
 * @property int $priority
 */
class Context extends Router
{

    protected $aliasMethods = [
        'generateAdminRoute' => ['genadminroute', 'generateAdminRoute'],
        'generateRESTfulRoute' => ['genrestfulroute', 'generateRESTfulRoute'],
        'addModule' => ['module', 'addmodule'],
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
    
    public function __construct($data = []){
        parent::__construct($data);
        $this->data['type'] = 'context';
    }
    
}