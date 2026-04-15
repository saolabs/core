<?php

namespace Saola\Core\Routing;

/**
 * Undocumented class
 *
 * @property array $data
 * @property string $title
 * @property string $description
 * @property string $uri
 * @property string $method
 * @property string $action
 * @property string $middleware
 * @property string $permission
 * @property string $name
 * @property string $as
 * 
 * @method $this title(string $title, string $description = '')
 * @method $this description(string $description = null)
 * @method $this uri(string $uri)
 * @method $this method(string $method)
 * @method $this action(string $action)
 * @method $this middleware(string $middleware)
 * @method $this permission(string $permission)
 * @method $this name(string $name)
 * @method $this as(string $as)
 * @method $this get(string $uri, string|callable|array $action)
 * @method $this post(string $uri, string $action)
 * @method $this put(string $uri, string|callable|array $action)
 * @method $this delete(string $uri, string|callable|array $action)
 * @method $this patch(string $uri, string|callable|array $action)
 * @method $this options(string $uri, string|callable|array $action)
 * @method $this head(string $uri, string|callable|array $action)
 * 
 */
class Action
{

    protected $data = [
        'type' => 'action',
    ];
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * set title and description
     *
     * @param string $title
     * @param string $description
     * @return $this
     */
    public function title($title, $description = '')
    {
        $this->data['title'] = $title;
        $this->data['description'] = $description ?? $this->data['description'];
        return $this;
    }

    /**
     * set description
     *
     * @param string $description
     * @return $this
     */
    public function description($description = null)
    {
        $this->data['description'] = $description ?? $this->data['description'];
        return $this;
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
        if(($t = count($arguments)) == 0){
            return $this;
        }
        $name = strtolower($name);
        if (in_array($name, ['get', 'post', 'put', 'delete', 'patch', 'options', 'head'])) {

            $this->data['method'] = $name;

            $params = ['uri', 'action'];
            $t = count($arguments);
            if ($t == 1) {
                if(!is_string($arguments[0])){
                    throw new \Exception('URI must be a string');
                }
                $this->data[$params[0]] = $arguments[0];

            } else if ($t >= 2) {
                if(!is_string($arguments[0]) || !(is_string($arguments[1]) || is_callable($arguments[1]) || is_array($arguments[1]))){
                    throw new \Exception('URI and Action must be a string or callable or array');
                }
                $this->data[$params[0]] = $arguments[0];
                $this->data[$params[1]] = $arguments[1];
            }
        }
        else if(in_array($name, ['name', 'slug', 'as'])){
            if(!is_string($arguments[0])){
                throw new \Exception($name . ' must be a string');
            }
            $this->data['name'] = $arguments[0];
        }
        else if(in_array($name, ['title', 'description', 'uri', 'method', 'action'])){
            if(!is_string($arguments[0])){
                throw new \Exception('Title, Description, URI, Method, Action must be a string');
            }
            $this->data[$name] = $arguments[0] ?? $this->data[$name];
        }
        else if(in_array($name, ['middleware', 'permission'])){
            if(!is_string($arguments[0]) && !is_array($arguments[0])){
                throw new \Exception($name . ' must be a string or array');
            }
            $this->data[$name] = $t == 1 ? $arguments[0] : $arguments;
        }
        return $this;
    }
    public function __get($name)
    {
        $name = strtolower($name);
        if(in_array($name, ['name', 'as'])){
            return $this->data['name'] ?? null;
        }
        return $this->data[$name] ?? null;
    }
    public function __set($name, $value)
    {
        $name = strtolower($name);
        if(in_array($name, ['name', 'as'])){
            if(!is_string($value)){
                throw new \Exception($name . ' must be a string');
            }
            $this->data['name'] = $value;
        }else if(in_array($name, ['middleware', 'permission'])){
            if(!is_string($value) && !is_array($value)){
                throw new \Exception($name . ' must be a string or array');
            }
            $this->data[$name] = $value;
        }
        elseif(in_array($name, ['type', 'data'])){
            throw new \Exception($name . ' is read only');
        }
        elseif(!is_string($value)){
            throw new \Exception($name . ' must be a string');
        }
        else{
            $this->data[$name] = $value;
        }
    }
}
