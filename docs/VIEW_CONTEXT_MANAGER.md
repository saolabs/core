# View Context Manager - Quản Lý View Theo Context

## Tổng Quan

Cơ chế quản lý view theo context (admin, web, ...) với các base directories:
- `components` - Components chung
- `modules` - Views của modules
- `layouts` - Layouts
- `templates` - Templates
- `pages` - Pages

**Lưu ý**: Thư viện KHÔNG tự động đăng ký context. Phía web (application) sẽ tự đăng ký các context cần thiết.

## Cấu Trúc

### 1. ViewContextManager
Quản lý các context và cấu hình (directories + variables) của chúng.

### 2. ViewEngine
Engine render view với: **context**, **module**, **blade**, **data**

### 3. ViewMethods (Trait)
Trait cho Service, tự động sử dụng ViewEngine khi có `context` và `module`.

## Đăng Ký Context

### Trong ServiceProvider

```php
use One\Core\Engines\ViewContextManager;
use One\Core\Engines\ViewEngine;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Lấy ViewContextManager (có thể từ singleton hoặc tạo mới)
        $contextManager = app(ViewContextManager::class);
        
        // Đăng ký context 'admin'
        $contextManager->registerContext('admin', [
            'directories' => [
                'components' => 'admin.components',
                'modules' => 'admin.modules',
                'layouts' => 'admin.layouts',
                'templates' => 'admin.templates',
                'pages' => 'admin.pages',
            ],
            'variables' => [
                '__component__' => 'admin.components.',
                '__module__' => 'admin.modules.',
                '__layout__' => 'admin.layouts.',
                '__template__' => 'admin.templates.',
                '__page__' => 'admin.pages.',
                '__base__' => 'admin.',
                '__system__' => '_system.',
                '__pagination__' => 'admin.pagination.',
            ],
        ]);

        // Đăng ký context 'web'
        $contextManager->registerContext('web', [
            'directories' => [
                'components' => 'web.components',
                'modules' => 'web.modules',
                'layouts' => 'web.layouts',
                'templates' => 'web.templates',
                'pages' => 'web.pages',
            ],
            // Variables sẽ tự động được tạo từ directories nếu không chỉ định
        ]);

        // Đăng ký context với cấu trúc tùy chỉnh
        $contextManager->registerContext('api', [
            'directories' => [
                'components' => 'api.shared.components',
                'modules' => 'api.v1.modules',
                'layouts' => 'api.layouts',
                'templates' => 'api.templates',
                'pages' => 'api.pages',
            ],
            'variables' => [
                '__component__' => 'api.shared.components.',
                '__module__' => 'api.v1.modules.',
                '__layout__' => 'api.layouts.',
                '__base__' => 'api.',
            ],
        ]);
    }
}
```

### Đăng ký với Variables tự động

Nếu không chỉ định `variables`, hệ thống sẽ tự động tạo từ `directories`:

```php
$contextManager->registerContext('web', [
    'directories' => [
        'components' => 'web.components',
        'modules' => 'web.modules',
        'layouts' => 'web.layouts',
        'templates' => 'web.templates',
        'pages' => 'web.pages',
    ],
    // Variables sẽ tự động là:
    // '__component__' => 'web.components.',
    // '__module__' => 'web.modules.',
    // '__layout__' => 'web.layouts.',
    // '__template__' => 'web.templates.',
    // '__page__' => 'web.pages.',
    // '__base__' => 'web.',
]);
```

### Cập nhật Variables sau khi đăng ký

```php
// Cập nhật thêm variables cho context
$contextManager->updateContextVariables('admin', [
    '__custom__' => 'admin.custom.',
    '__widget__' => 'admin.widgets.',
]);
```

## Cách Sử Dụng

### Service có context và module

```php
class UserService extends Service
{
    use ViewMethods;

    protected $context = 'admin';  // Context: admin, web, api, ...
    protected $module = 'users';    // Module name

    public function __construct()
    {
        parent::__construct();
        $this->initView();  // Khởi tạo view engine
    }

    public function index()
    {
        // Render module view: admin.modules.users.index
        return $this->render('index', [
            'users' => $this->getUsers(),
        ]);
    }

    public function show($id)
    {
        // Render module view: admin.modules.users.detail
        return $this->render('detail', [
            'user' => $this->getUser($id),
        ]);
    }
}
```

### Render các loại view khác nhau

```php
class PageService extends Service
{
    use ViewMethods;

    protected $context = 'web';
    protected $module = 'pages';

    public function home()
    {
        // Render page view: web.pages.home
        return $this->renderPage('home', [
            'title' => 'Home Page',
        ]);
    }

    public function about()
    {
        // Render page view: web.pages.about
        return $this->renderPage('about', []);
    }
}
```

### Render component

```php
// Render component: {context}.components.button
return $this->renderComponent('button', [
    'text' => 'Click me',
]);
```

### Render layout

```php
// Render layout: {context}.layouts.main
return $this->renderLayout('main', [
    'title' => 'Page Title',
]);
```


## Cấu Trúc Thư Mục View

```
resources/views/
├── admin/
│   ├── components/
│   ├── modules/
│   │   └── users/
│   │       ├── index.blade.php
│   │       ├── detail.blade.php
│   │       └── form.blade.php
│   ├── layouts/
│   ├── templates/
│   └── pages/
├── web/
│   ├── components/
│   ├── modules/
│   ├── layouts/
│   ├── templates/
│   └── pages/
│       ├── home.blade.php
│       └── about.blade.php
└── _system/
```

## Ví Dụ Đầy Đủ

```php
class ProductService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'products';
    protected $moduleName = 'Sản Phẩm';

    public function __construct()
    {
        parent::__construct();
        $this->initView();
    }

    public function index()
    {
        // Render: admin.modules.products.index
        return $this->render('index', [
            'products' => $this->getProducts(),
        ]);
    }

    public function create()
    {
        // Render: admin.modules.products.form
        return $this->render('form', [
            'product' => new Product(),
        ]);
    }

    public function edit($id)
    {
        // Render: admin.modules.products.form
        return $this->render('form', [
            'product' => $this->getProduct($id),
        ]);
    }
}
```

## Thay Đổi Context Trong Runtime

```php
class MultiContextService extends Service
{
    use ViewMethods;

    public function renderForAdmin()
    {
        $oldContext = $this->context;
        $this->context = 'admin';
        $this->initView();
        
        $result = $this->render('index', []);
        
        $this->context = $oldContext;
        $this->initView();
        
        return $result;
    }
}
```

## Octane Compatibility

ViewEngine và ViewContextManager đều implement `OctaneCompatible`:
- Không có static state
- Tự động reset sau mỗi request
- An toàn với Laravel Octane

## Tóm Tắt

1. **Service có `context` và `module`**
2. **Gọi `initView()` trong constructor**
3. **Render với `render()`, `renderModule()`, `renderPage()`, etc.**
4. **ViewEngine tự động resolve path: `{context}.modules.{module}.{blade}`**

