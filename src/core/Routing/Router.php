<?php

namespace Saola\Core\Routing;

/**
 * Router class
 *
 * @property string $title
 * @property string $description
 * @property string $uri
 * @property string $method
 * @property string $action
 * @property string $middleware
 * @property string $permission
 * @property string $name
 * @property string $as
 * @property int $priority
 * 
 * @method $this title(string $title, string $description = '') set title and description
 * @method $this description(string $description = null) set description
 * @method $this uri(string $uri) set uri
 * @method $this method(string $method) set method
 * @method $this action(string $action) set action
 * @method $this middleware(string $middleware) set middleware
 * @method $this permission(string $permission) set permission
 * @method $this name(string $name) set name
 * @method $this as(string $as) set as (route name)
 * @method $this display(string $display) set display (display name)
 * @method $this display_name(string $display_name) set display_name (display name)
 * @method $this displayName(string $displayname) set displayname (display name)
 * @method $this where(string $key, string $value) set where
 * 
 * @method $this|Router get(string $uri, string|callable|array $action) set get method or add child router with get method
 * @method $this|Router post(string $uri, string $action) set post method or add child router with post method
 * @method $this|Router put(string $uri, string|callable|array $action) set put method or add child router with put method
 * @method $this|Router delete(string $uri, string|callable|array $action) set delete method or add child router with delete method
 * @method $this|Router patch(string $uri, string|callable|array $action) set patch method or add child router with patch method
 * @method $this|Router options(string $uri, string|callable|array $action) set options method or add child router with options method
 * @method $this|Router head(string $uri, string|callable|array $action) set head method or add child router with head method
 * 
 * @method $this|Router addSubModule(array|string $data, callable|string $callback) add sub module
 * @method $this|Router addModule(array|string $data, callable|string $callback) add channel
 * @method $this|Router addChildRouter(array $data) add child router
 * 
 * @method $this|Router group(callable|string $callback) do action in group
 * @method $this|Router sub(array|string $data, callable|string $callback) add sub module
 * @method $this|Router child(array $data) add child router
 * @method $this|Router priority(int $priority) set priority
 * 
 * @method $this|Router view(string $view) set view
 * @method $this|Router viewModule(string $viewmodule) set view module
 * @method $this|Router viewPage(string $viewpage) set view page
 * 
 */
class Router
{
    use RouteMethods;

    protected $aliasMethods = [
        'setRouteAttribute' => [
            'get',
            'post',
            'put',
            'delete',
            'patch',
            'options',
            'head',
            'any',
            'match',
            'name',
            'route_name',
            'routename',
            'nickname',
            'slug',
            'as',
            'title',
            'description',
            'uri',
            'method',
            'action',
            'middleware',
            'permission',
            'prefix',
            'controller',
            'display_name',
            'display',
            'displayname',
            'type',
            'priority',
            'view',
            'viewmodule',
            'viewpage',
        ],
    ];


    /**
     * @param array{type:string,slug:string,name:string,title:string,description:string,uri:string,method:string,action:string,prefix:string,middleware:string,permission:string} $data
     */
    public function __construct($data = [], $parent = null)
    {
        $this->init();
        $this->data = array_merge($this->data, $data);
        if ($parent) {
            $this->parent = $parent;
        }
    }



    /**
     * set method and uri or action
     *
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (($t = count($arguments)) == 0) {
            return $this;
        }
        $name = strtolower($name);
        $this->currentMethod = $name;
        if ($method = $this->getAliasMethod($name)) {
            $r = $this->{$method}(...$arguments);
            $this->currentMethod = null;
            return $r;
        }


        // check if the method is a http method or a route attribute
        if ($this->isRouteAttribute($name)) {
            $result = $this->setRouteDataValue($name, ...$arguments);
            $this->currentMethod = null;
            if (!$result) {
                throw new \Exception('Method ' . $name . ' is not allowed for ' . $this->type);
            }

            return $this;
        }
        $this->currentMethod = null;

        return $this;
    }


    public function toArray(): array
    {
        $data = $this->data;
        unset($data['parent']);
        if ($data['permission'] && !is_array($data['permission'])) {
            $data['permission'] = [$data['permission']];
        }
        if ($data['as'] !== null) {
            $data['as'] = $this->getFullRouteName();
        }
        if (in_array($this->type, $this->moduleTypes) || $this->type == 'context') {
            $data['children'] = array_map(function ($child) {
                return $child->toArray();
            }, $this->children);
        }
        return $data;
    }
}
