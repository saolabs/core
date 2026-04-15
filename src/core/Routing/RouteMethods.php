<?php

namespace Saola\Core\Routing;

use Saola\Core\Support\SPA;
use Illuminate\Support\Facades\Route;
use Saola\Core\Routing\System;

trait RouteMethods
{
    /**
     * @var array{type:string,slug:string,name:string,title:string,description:string,uri:string,method:string,action:string,prefix:string,middleware:string,permission:string,context:string,module:string,parent_module:string} $data
     */
    protected $data = [
        'type' => 'router',
        'slug' => null,
        'name' => null,
        'as' => null,
        'controller' => null,
        'title' => null,
        'description' => null,
        'uri' => null,
        'method' => null,
        'action' => null,
        'prefix' => null,
        'middleware' => null,
        'permission' => null,
        'display_name' => null,
        'is_match' => false,
        'match' => [],
        'priority' => 0,
        'view' => null,
        'context' => null,
        'module' => null,
        'parent_module' => null,
    ];
    /**
     * @var Router|null $parent
     */
    protected $parent = null;
    /**
     * @var array<Router|Module> $children
     */
    protected $children = [];

    protected $moduleTypes = ['module', 'group', 'submodule', 'sub'];
    /**
     * @var array<string> $routeMethods
     */
    protected $routeMethods = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'any'];
    /**
     * @var array<string> $routeAttributes
     */
    protected $routeAttributes = [
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
        'viewmodule'
    ];


    protected $currentMethod = null;

    protected $methodMap = [];

    public function init()
    {
        if ($this->aliasMethods && is_array($this->aliasMethods) && count($this->aliasMethods) > 0) {
            foreach ($this->aliasMethods as $method => $alias) {
                if (is_array($alias)) {
                    foreach ($alias as $key) {
                        $t = strtolower($key);
                        $this->methodMap[$t] = $method;
                    }
                } else {
                    $t = strtolower($alias);
                    $this->methodMap[$t] = $method;
                }
            }
        }
    }


    /**
     * push laravel route
     *
     * @return \Illuminate\Routing\Route
     */
    public function pushLaravelRoute()
    {
        $context = $this->data['context'] ?? System::WEB;
        $as = $this->data['as'];
        $uri = $this->data['uri'] ?? null;
        $method = $this->data['method'] ?? null;
        $action = $this->data['action'] ?? null;
        $middleware = $this->data['middleware'] ?? 'next';
        $prefix = $this->data['prefix'] ?? null;
        $controller = $this->data['controller'] ?? null;
        $type = $this->data['type'] ?? 'router';
        $is_match = $this->data['is_match'] ?? false;
        $match = $this->data['match'] ?? [];
        $prefixRouteNAme = $this->getParentName();
        $isModuleOrIsContextOrIsChannel = $this->isModule() || $this->isContext() || $this->isChannel();
        $children = $this->children;

        if ($isModuleOrIsContextOrIsChannel) {
            if (!is_array($children) || count($children) == 0) {
                return;
            }
        } else {

            // Xử lý các loại route đặc biệt
            if (isset($this->data['redirect'])) {
                extract($this->data['redirect']);
                // Đăng ký route chuyển hướng
                return Route::redirect($uri, $destination, $status);
            }

            if (isset($this->data['resource'])) {
                extract($this->data['resource']);
                // Đăng ký resource controller
                return Route::resource($name, $controller, $options);
            }
            if (isset($this->data['apiResource'])) {
                extract($this->data['apiResource']);
                // Đăng ký resource controller cho API
                return Route::apiResource($name, $controller, $options);
            }
            if (isset($this->data['fallback'])) {
                // Đăng ký route fallback
                return Route::fallback($this->data['fallback']);
            }
        }

        $laravelRoute = null;
        if ($isModuleOrIsContextOrIsChannel) {
            // Khởi tạo builder với middleware
            $laravelRoute = Route::middleware($middleware);
            // Thêm prefix nếu có
            if ($prefix) {
                $laravelRoute = $laravelRoute->prefix($prefix);
            }
            // Thêm controller nếu có
            if ($controller) {
                $laravelRoute = $laravelRoute->controller($controller);
            }
            // Thêm tên route nếu có
            if ($as !== null) {
                $laravelRoute = $laravelRoute->as(($prefixRouteNAme && $as ? '.' : '') . $as);
            }

            // Sắp xếp children theo priority trước khi đăng ký
            $sortedChildren = $this->sortChildrenByPriority($children);

            // Đăng ký group các route con
            $laravelRoute->group(function () use ($sortedChildren) {
                foreach ($sortedChildren as $child) {
                    $child->pushLaravelRoute();
                }
            });
            return $laravelRoute;
        }
        if ($is_match) {
            // Đăng ký route với nhiều HTTP method
            $laravelRoute = Route::match($match, $uri, $action);
        } else if ($method === 'any') {
            // Đăng ký route cho tất cả các HTTP methods
            $laravelRoute = Route::any($uri, $action);
        } else {
            // Đăng ký route thông thường
            try {
                //code...
                $laravelRoute = call_user_func_array([Route::class, $method], [$uri, $action]);
            } catch (\Throwable $th) {
                //throw $th;
                throw new \Exception("Method " . $method . " is not allowed for " . $this->type);
            }
        }
        // Thêm tên route nếu có
        if ($as !== null) {
            $laravelRoute = $laravelRoute->name(($prefixRouteNAme && $as ? '.' : '') . $as);
        }

        // Thêm middleware nếu có
        if ($middleware) {
            $laravelRoute = $laravelRoute->middleware($middleware);
        }

        // Thêm domain nếu có
        if (isset($this->data['domain'])) {
            $laravelRoute = $laravelRoute->domain($this->data['domain']);
        }
        // Thêm namespace nếu có
        if (isset($this->data['namespace'])) {
            $laravelRoute = $laravelRoute->namespace($this->data['namespace']);
        }
        // Thêm điều kiện where cho tham số route
        if (isset($this->data['wheres'])) {
            $laravelRoute = $laravelRoute->where($this->data['wheres']);
        }
        // Thêm giá trị mặc định cho tham số
        if (isset($this->data['defaults'])) {
            $laravelRoute = $laravelRoute->defaults($this->data['defaults']);
        }
        // Loại bỏ middleware nếu có
        if (isset($this->data['withoutMiddleware'])) {
            $laravelRoute = $laravelRoute->withoutMiddleware($this->data['withoutMiddleware']);
        }
        // Sử dụng scopeBindings nếu có
        if (!empty($this->data['scopeBindings'])) {
            $laravelRoute = $laravelRoute->scopeBindings();
        }
        // Xử lý khi model không tìm thấy
        if (isset($this->data['missing'])) {
            $laravelRoute = $laravelRoute->missing($this->data['missing']);
        }
        
        // Chỉ lấy URI và name khi có HTTP context (tránh lỗi khi chạy artisan commands)
        // Kiểm tra xem app có đang chạy trong HTTP context không
        $hasHttpContext = app()->runningInConsole() === false || 
                         (app()->bound('request') && app('request') !== null);
        
        if ($hasHttpContext) {
            try {
                $routeUri = $laravelRoute->uri();
                $routeName = $laravelRoute->getName();
                if($routeName) {
                    SPA::addRoute($context, $routeName, $routeUri, $this->getComponentPath());
                }
            } catch (\Throwable $e) {
                // Bỏ qua lỗi nếu không thể lấy URI (ví dụ: khi chạy artisan commands)
                // Routes vẫn được đăng ký, chỉ không thêm vào SPA routes
            }
        } else {
            // Khi không có HTTP context, vẫn cố gắng lấy route name từ data nếu có
            $routeName = $this->getFullRouteName();
            if($routeName && $this->data['uri']) {
                // Sử dụng URI từ data thay vì từ Laravel route object
                SPA::addRoute($context, $routeName, $this->data['uri'], $this->getComponentPath());
            }
        }
        
        return $laravelRoute;
    }




    public function generateAdminRoute(){
        if(!($this->isModule() || $this->isContext() || $this->isChannel())){
            throw new \Exception('Generate admin route only for module, context or channel');
        }
        $this->get('/', 'viewIndexPage')->name('');
        $this->get('list.html', 'viewListPage')->name('list');
        $this->get('trash.html', 'viewTrashPage')->name('trash');
        $this->get('detail/{id}.html', 'viewDetailPage')->name('detail');
        $this->get('create.html', 'viewCreatePage')->name('create');
        $this->get('edit/{id}.html', 'viewEditPage')->name('edit');
        // business route
        $this->post('create', 'create')->name('create');
        $this->post('edit/{id}', 'edit')->name('edit');
        $this->post('delete', 'delete')->name('delete');
        $this->post('restore', 'restore')->name('restore');
        $this->post('move-to-trash', 'moveToTrash')->name('move-to-trash');

    }

    /**
     * Generate RESTful API routes theo chuẩn REST
     * 
     * Routes được tạo (theo chuẩn RESTful API):
     * - GET    /              → index()   - List all resources
     * - POST   /              → store()   - Create new resource
     * - GET    /{id}          → show()    - Get single resource
     * - PUT    /{id}          → update()  - Update resource (full update)
     * - PATCH  /{id}          → update()  - Update resource (partial update)
     * - DELETE /{id}          → destroy() - Delete resource
     * 
     * Lưu ý: RESTful API không có create/edit routes (GET /create, GET /{id}/edit).
     * Các routes đó chỉ dùng cho web UI, sử dụng generateAdminRoute() thay thế.
     * 
     * Route names sẽ tự động được prefix với parent route name để tránh trùng lặp.
     * Ví dụ: parent route name = 'admin.users' → route names sẽ là 'admin.users.index', 'admin.users.show', etc.
     * 
     * @param array $options Các tùy chọn:
     *                      - only: array - Chỉ tạo các routes được chỉ định (ví dụ: ['index', 'show'])
     *                      - except: array - Bỏ qua các routes được chỉ định
     *                      - idConstraint: string - Constraint cho {id} parameter (mặc định: '[0-9]+')
     *                      - namePrefix: string|null - Prefix cho route names (mặc định: null - tự động từ parent)
     *                      - withoutNames: bool - Không set route names (mặc định: false)
     * @return $this
     */
    public function generateRESTfulRoute(array $options = [])
    {
        if(!($this->isModule() || $this->isContext() || $this->isChannel())){
            throw new \Exception('Generate RESTful route only for module, context or channel');
        }
        $only = $options['only'] ?? null;
        $except = $options['except'] ?? [];
        $idConstraint = $options['idConstraint'] ?? '[0-9]+';
        $namePrefix = $options['namePrefix'] ?? null;
        $withoutNames = $options['withoutNames'] ?? false;

        // Helper function để kiểm tra route có được tạo không
        $shouldInclude = function($routeName) use ($only, $except) {
            if ($only !== null && !in_array($routeName, $only)) {
                return false;
            }
            return !in_array($routeName, $except);
        };

        // Helper function để set route name (nếu cần)
        $setRouteName = function($name) use ($namePrefix, $withoutNames) {
            if ($withoutNames) {
                return ''; // Không set name
            }
            
            // Nếu có custom prefix, sử dụng nó
            if ($namePrefix !== null) {
                return $namePrefix . '.' . $name;
            }
            
            // Nếu không có prefix, route name sẽ tự động được prefix bởi parent route
            // (xử lý trong pushLaravelRoute method - line 194)
            return $name;
        };

        // GET / - Index: List all resources
        if ($shouldInclude('index')) {
            $routeName = $setRouteName('index');
            $route = $this->get('/', 'index');
            if ($routeName) {
                $route->name($routeName);
            }
        }

        // POST / - Store: Create new resource
        if ($shouldInclude('store')) {
            $routeName = $setRouteName('store');
            $route = $this->post('/', 'store');
            if ($routeName) {
                $route->name($routeName);
            }
        }

        // GET /{id} - Show: Get single resource
        if ($shouldInclude('show')) {
            $routeName = $setRouteName('show');
            $route = $this->get('{id}', 'show')->where('id', $idConstraint);
            if ($routeName) {
                $route->name($routeName);
            }
        }

        // PUT /{id} - Update: Full update resource
        if ($shouldInclude('update')) {
            $routeName = $setRouteName('update');
            $route = $this->put('{id}', 'update')->where('id', $idConstraint);
            if ($routeName) {
                $route->name($routeName);
            }
        }

        // PATCH /{id} - Update: Partial update resource
        if ($shouldInclude('update')) {
            $routeName = $setRouteName('update');
            $route = $this->patch('{id}', 'update')->where('id', $idConstraint);
            if ($routeName) {
                $route->name($routeName);
            }
        }

        // DELETE /{id} - Destroy: Delete resource
        if ($shouldInclude('destroy')) {
            $routeName = $setRouteName('destroy');
            $route = $this->delete('{id}', 'destroy')->where('id', $idConstraint);
            if ($routeName) {
                $route->name($routeName);
            }
        }

        return $this;
    }

    protected function getComponentPath(){
        $parentModule = $this->data['parent_module'] ?? null;
        $currentModule = $this->data['module'] ?? null;
        $base = $this->data['context'] ?? '';

        $blade = $this->data['view'] ?? null;
        if(!$blade){
            return null;
        }
        if(str_starts_with($blade, '@MODULE:')){
            $view = str_replace('@MODULE:', '', $blade);
            $base = ($base ? $base . '.' : '') . 'modules';
            if($currentModule && $parentModule){
                $base = $base . '.' . $parentModule . '.' . $currentModule;
            }
            else{
                $base = $base . '.' . $parentModule.$currentModule;
            }
            return $base . '.' . $view;
        }
        else if(str_starts_with($blade, '@PAGE:')){
            $view = str_replace('@PAGE:', '', $blade);
            $base = ($base ? $base . '.' : '') . 'pages';
            
            return $base . '.' . $view;
        }
        else if(str_starts_with($blade, '@BASE:')){
            $view = str_replace('@BASE:', $base.'.', $blade);
            return $view;
        }
        else if($base){
            return $base . '.' . $blade;
        }
        return $blade;
    }




    protected function setRouteAttribute(...$arguments)
    {
        $this->setRouteDataValue($this->currentMethod, ...$arguments);
        return $this;
    }


    protected function getAliasMethod($name)
    {
        $t = strtolower($name);
        return $this->methodMap[$t] ?? null;
    }

    public function isModule()
    {
        return in_array($this->type, $this->moduleTypes);
    }

    public function isContext()
    {
        return $this->type == 'context';
    }

    public function isChannel()
    {
        return $this->type == 'channel';
    }


    /**
     * set route data value
     *
     * @param string $name
     * @param mixed $arguments
     * @return bool
     */
    protected function setRouteDataValue($name, ...$arguments): bool
    {
        $t = count($arguments);
        // check if the method is a http method
        if (in_array($name, $this->routeMethods)) {
            if ($name == 'match') {
                if ($t < 2 || !is_array($arguments[0])) {
                    throw new \Exception('Match must be an array');
                }
                $this->data['match'] = array_shift($arguments);
                $t--;
            } else {
                $this->data['method'] = $name;
            }

            $params = ['uri', 'action'];
            $t = count($arguments);
            if ($t == 1) {
                if (!is_string($arguments[0])) {
                    throw new \Exception('URI must be a string');
                }
                $this->data[$params[0]] = $arguments[0];
            } else if ($t >= 2) {
                if (!is_string($arguments[0]) || !(is_string($arguments[1]) || is_callable($arguments[1]) || is_array($arguments[1]))) {
                    throw new \Exception('URI and Action must be a string or callable or array');
                }
                $this->data[$params[0]] = $arguments[0];
                $this->data[$params[1]] = $arguments[1];
            }
        } 
        // check if route name
        else if (in_array($name, ['as', 'route_name', 'routename', 'nickname', 'name'])) {
            if (!is_string($arguments[0])) {
                throw new \Exception($name . ' must be a string');
            }
            $this->data['as'] = $arguments[0];
        } 
        
        // check if display name
        else if (in_array($name, ['display', 'display_name', 'displayname', 'display_name'])) {
            if (!is_string($arguments[0])) {
                throw new \Exception($name . ' must be a string');
            }
            $this->data['display_name'] = $arguments[0];
        } 
        
        // check if title, description, uri, method, prefix, controller, slug
        else if (in_array($name, ['title', 'description', 'uri', 'method', 'prefix', 'controller', 'slug'])) {
            if (!is_string($arguments[0])) {
                throw new \Exception('Title, Description, URI, Method, Action, Prefix, Controller must be a string');
            }

            $this->data[$name] = $arguments[0] ?? $this->data[$name];
        } 
        
        // check if action
        else if (in_array($name, ['action'])) {
            if (!(is_string($arguments[0]) || is_callable($arguments[0])) || (is_array($arguments[0]) && count($arguments[0]) == 2 && is_string($arguments[0][1]))) {
                throw new \Exception('Action must be a string or callable');
            }
            $this->data[$name] = $arguments[0];
        } 
        
        // check if middleware or permission
        else if (in_array($name, ['middleware', 'permission'])) {
            if (!is_string($arguments[0]) && !is_array($arguments[0])) {
                throw new \Exception($name . ' must be a string or array');
            }
            $this->data[$name] = $t == 1 ? $arguments[0] : $arguments;
        } 
        
        // check if priority
        else if (in_array($name, ['priority'])) {
            if ($t < 1 || !is_int($arguments[0])) {
                throw new \Exception($name . ' must be an integer');
            } else {
                $this->data[$name] = $arguments[0];
            }
        } 
        
        // check if view
        else if (in_array($name, ['view'])) {
            if ($t < 1 || !is_string($arguments[0])) {
                throw new \Exception($name . ' must be a string');
            } else {
                $this->data[$name] = $arguments[0];
            }
        } 
        
        // check if view module
        else if (in_array($name, ['viewmodule'])) {
            if ($t < 1 || !is_string($arguments[0])) {
                throw new \Exception($name . ' must be a string');
            } else {
                $this->data['view'] = '@MODULE:' . $arguments[0];
            }
        } 
        
        // check if view page
        else if (in_array($name, ['viewpage'])) {
            if ($t < 1 || !is_string($arguments[0])) {
                throw new \Exception($name . ' must be a string');
            } else {
                $this->data['view'] = '@PAGE:' . $arguments[0];
            }
        } 
        
        // if the method is not a route attribute
        else {
            return false;
        }
        return true;
    }

    /**
     * check if the method is a http method
     *
     * @param string $name
     * @return bool
     */
    protected function isHttpMethod($name): bool
    {
        return in_array($name, $this->routeMethods) || $name == 'match';
    }
    /**
     * check if the method is a route attribute
     *
     * @param string $name
     * @return bool
     */
    protected function isRouteAttribute($name): bool
    {
        return in_array($name, $this->routeAttributes);
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
     * get route data value
     *
     * @param string $name
     * @return mixed
     */
    public function getRouteDataValue($name): mixed
    {
        if (in_array($name, ['as', 'route_name', 'routename', 'nickname', 'name'])) {
            return $this->data['as'];
        }
        if (in_array($name, ['display', 'display_name', 'displayname'])) {
            return $this->data['display_name'] ?? null;
        }
        return $this->data[$name] ?? null;
    }

    public function getParentName(): string
    {
        return $this->parent?->getFullRouteName() ?? '';
    }

    public function getFullRouteName(): string
    {
        $name = $this->getParentName();
        if ($this->data['as']) {
            $name = ($name ? $name . '.' : '') . $this->data['as'];
        }
        return $name;
    }

    public function getRouteName(): string
    {
        return $this->data['as'] === null ? null : $this->getFullRouteName();
    }

    /**
     * Đăng ký route cho tất cả các HTTP methods
     */
    public function any($uri, $action)
    {
        return $this->setRouteDataValue('any', $uri, $action);
    }
    /**
     * Đặt điều kiện regex cho tham số route
     */
    public function where($param, $pattern = null)
    {
        if (!isset($this->data['wheres'])) {
            $this->data['wheres'] = [];
        }
        if (is_array($param)) {
            $this->data['wheres'] = array_merge($this->data['wheres'], $param);
        } else {
            $this->data['wheres'][$param] = $pattern;
        }
        return $this;
    }
    /**
     * Đặt domain cho route
     */
    public function domain($domain)
    {
        $this->data['domain'] = $domain;
        return $this;
    }
    /**
     * Đặt namespace cho controller
     */
    public function namespace($namespace)
    {
        $this->data['namespace'] = $namespace;
        return $this;
    }
    /**
     * Đăng ký route fallback (khi không khớp route nào)
     */
    public function fallback($action)
    {
        $this->data['fallback'] = $action;
        return $this;
    }
    /**
     * Đặt giá trị mặc định cho tham số route
     */
    public function defaults($param, $value = null)
    {
        if (!isset($this->data['defaults'])) {
            $this->data['defaults'] = [];
        }
        if (is_array($param)) {
            $this->data['defaults'] = array_merge($this->data['defaults'], $param);
        } else {
            $this->data['defaults'][$param] = $value;
        }
        return $this;
    }
    /**
     * Loại bỏ middleware cho route
     */
    public function withoutMiddleware($middleware)
    {
        if (!isset($this->data['withoutMiddleware'])) {
            $this->data['withoutMiddleware'] = [];
        }
        if (is_array($middleware)) {
            $this->data['withoutMiddleware'] = array_merge($this->data['withoutMiddleware'], $middleware);
        } else {
            $this->data['withoutMiddleware'][] = $middleware;
        }
        return $this;
    }
    /**
     * Sử dụng route model binding theo scope
     */
    public function scopeBindings()
    {
        $this->data['scopeBindings'] = true;
        return $this;
    }
    /**
     * Xử lý khi model không tìm thấy
     */
    public function missing($callback)
    {
        $this->data['missing'] = $callback;
        return $this;
    }
    /**
     * Đăng ký route chuyển hướng
     */
    public function redirect($uri, $destination, $status = 302)
    {
        $this->data['redirect'] = compact('uri', 'destination', 'status');
        return $this;
    }

    /**
     * Đăng ký resource controller
     */
    public function resource($name, $controller, array $options = [])
    {
        $this->data['resource'] = compact('name', 'controller', 'options');
        return $this;
    }
    /**
     * Đăng ký resource controller cho API
     */
    public function apiResource($name, $controller, array $options = [])
    {
        $this->data['apiResource'] = compact('name', 'controller', 'options');
        return $this;
    }


    // MODULE METHODS


    /**
     * create a group
     *
     * @param array|string $data
     * @param callable|string $callback
     * @return $this
     */
    protected function addSubModule($data = null, $callback = null)
    {
        $groupData = is_array($data) ? $data : (is_array($callback) && !is_callable($callback) ? $callback : []);
        $groupData['type'] = 'module';
        if (is_string($data)) {
            if (!array_key_exists('slug', $groupData) || $groupData['slug'] == null || $groupData['slug'] == '') {
                $groupData['slug'] = $data;
            }
            if ((!array_key_exists('prefix', $groupData) || $groupData['prefix'] == null || $groupData['prefix'] == '') && !is_string($callback)) {
                $groupData['prefix'] = $data;
            }
        }
        if (is_string($callback) && (!array_key_exists('prefix', $groupData) || $groupData['prefix'] == null || $groupData['prefix'] == '')) {
            $groupData['prefix'] = $callback;
        }
        

        $callFn = is_callable($callback) ? $callback : (is_callable($data) ? $data : null);

        $parentModule = $this->data['parent_module'] ?? null;
        $currentModule = $this->data['module'] ?? null;
        if($currentModule && $parentModule){
            $groupData['parent_module'] = $parentModule . '.' . $currentModule;
        }
        else{
            $groupData['parent_module'] = $parentModule.$currentModule;
        }
        $groupData['module'] = $groupData['slug'] ?? null;

        $group = new Module($groupData, $this);
        if ($callFn) {
            $callFn($group);
        }
        $this->children[] = $group;
        return $group;
    }


    protected function doGroupAction($callback)
    {
        if (is_callable($callback)) {
            call_user_func_array($callback, [$this]);
        }
        return $this;
    }

    /**
     * thêm route con
     *
     * @param array{type:string,name:string,slug:string,as:string,title:string,description:string,uri:string,method:string,action:string,middleware:string,permission:string,prefix:string,controller:string} $data
     * @return Router
     */
    protected function addChildRouter($data = null)
    {
        if (!($this->isModule() || $this->isChannel() || $this->isContext())) {
            throw new \Exception('Method addChildRouter is not allowed for ' . $this->type);
        }
        $moduleData = [
            'context' => $this->data['context'],
            'module' => $this->data['module'],
            'parent_module' => $this->data['parent_module']
        ];

        $router = new Router(
            array_merge(
                ['type' => 'router'],
                $data,
                $moduleData
            ),
            $this
        );
        $this->children[] = $router;
        return $router;
    }

    protected function addChild(...$args)
    {
        $method = $this->currentMethod;
        if (!$method) {
            throw new \Exception('Method addChild is not allowed for ' . $this->type);
        }
        return $this->addChildRouter(['type' => 'router'])->{$method}(...$args);
    }


    // CHANNEL METHODS

    /**
     * create a group
     *
     * @param array|string $data
     * @param callable|string $callback
     * @return $this
     */
    protected function addModule($data = null, $callback = null)
    {
        if (is_string($data) && $callback == null) {
            if ($this->children && count($this->children) > 0) {
                foreach ($this->children as $child) {
                    if ($child->slug == $data) {
                        return $child;
                    }
                }
            }
        }
        $groupData = is_array($data) ? $data : (is_array($callback) && !is_callable($callback) ? $callback : []);
        $groupData['type'] = 'module';
        $groupData['context'] = $this->data['context'];
        $parentModule = $this->data['parent_module'] ?? null;
        $currentModule = $this->data['module'] ?? null;
        if($currentModule && $parentModule){
            $groupData['parent_module'] = $parentModule . '.' . $currentModule;
        }
        else{
            $groupData['parent_module'] = $parentModule.$currentModule;
        }
        // $groupData['parent_module'] = $this;
        if (is_string($data)) {
            if (!array_key_exists('slug', $groupData) || $groupData['slug'] == null || $groupData['slug'] == '') {
                $groupData['slug'] = $data;
            }
            if ((!array_key_exists('prefix', $groupData) || $groupData['prefix'] == null || $groupData['prefix'] == '') && !is_string($callback)) {
                $groupData['prefix'] = $data;
            }
        }
        if (is_string($callback) && (!array_key_exists('prefix', $groupData) || $groupData['prefix'] == null || $groupData['prefix'] == '')) {
            $groupData['prefix'] = $callback;
        }

        $groupData['module'] = $groupData['slug'] ?? null;
        if(array_key_exists('slug', $groupData) && $groupData['slug'] && (!array_key_exists('as', $groupData) || $groupData['as'] == null || $groupData['as'] == '')){
            $groupData['as'] = $groupData['slug'];
        }
        $callFn = is_callable($callback) ? $callback : (is_callable($data) ? $data : null);

        $group = new Module($groupData, $this);
        if ($callFn) {
            $callFn($group);
        }
        $this->children[] = $group;
        return $group;
    }

    public function __get($name)
    {
        $name = strtolower($name);
        if ($this->isRouteAttribute($name)) {
            return $this->getRouteDataValue($name);
        }
        return null;
    }
    public function __set($name, $value)
    {
        $name = strtolower($name);
        if ($this->isRouteAttribute($name)) {
            return $this->setRouteDataValue($name, $value);
        }
        return false;
    }

    /**
     * Sắp xếp children theo priority (số càng nhỏ càng ưu tiên cao)
     *
     * @param array $children
     * @return array
     */
    protected function sortChildrenByPriority(array $children): array
    {
        // Sắp xếp theo priority
        usort($children, function ($a, $b) {
            $priorityA = $a->priority ?? 0;
            $priorityB = $b->priority ?? 0;

            // Nếu priority bằng nhau, giữ nguyên thứ tự ban đầu
            if ($priorityA === $priorityB) {
                return 0;
            }

            // Số càng nhỏ càng ưu tiên cao
            return $priorityA <=> $priorityB;
        });

        return $children;
    }
}
