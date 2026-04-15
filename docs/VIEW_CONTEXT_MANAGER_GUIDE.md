# ViewContextManager - Hướng Dẫn Chi Tiết

## 📋 Mục Lục

1. [Tổng Quan](#tổng-quan)
2. [Kiến Trúc](#kiến-trúc)
3. [Luồng Hoạt Động](#luồng-hoạt-động)
4. [Hướng Dẫn Triển Khai](#hướng-dẫn-triển-khai)
5. [Hướng Dẫn Sử Dụng](#hướng-dẫn-sử-dụng)
6. [Ví Dụ Thực Tế](#ví-dụ-thực-tế)
7. [Best Practices](#best-practices)

---

## 📖 Tổng Quan

### ViewContextManager là gì?

`ViewContextManager` là một cơ chế quản lý view theo **context** (admin, web, api, ...), cho phép:

- ✅ Quản lý các base directories cho mỗi context
- ✅ Đăng ký và cập nhật context động
- ✅ Render view với context, module, blade, data
- ✅ Tuân thủ Laravel Octane (không bị reset giữa các requests)
- ✅ Hỗ trợ cập nhật động (ví dụ: đổi theme)

### Các Thành Phần

1. **ViewContextManager** - Class quản lý contexts và render view
2. **ViewMethods** - Trait cho Service để sử dụng view
3. **Context** - Tên context (admin, web, api, ...)
4. **Module** - Tên module (users, products, ...)
5. **Directories** - Các thư mục base (components, modules, layouts, templates, pages)
6. **Variables** - Các biến đại diện cho directories (__component__, __module__, ...)

---

## 🏗️ Kiến Trúc

### Sơ Đồ Kiến Trúc

```
┌─────────────────────────────────────────────────────────┐
│                    Application Layer                      │
│                                                           │
│  ┌──────────────┐    ┌──────────────┐                  │
│  │   Service    │    │  Controller  │                  │
│  │ (ViewMethods)│    │              │                  │
│  └──────┬───────┘    └──────┬───────┘                  │
│         │                   │                           │
│         └──────────┬────────┘                           │
│                    │                                    │
│                    ▼                                    │
│         ┌──────────────────────┐                        │
│         │  ViewContextManager   │                        │
│         │   (Singleton)        │                        │
│         └──────────────────────┘                        │
│                    │                                    │
│         ┌──────────┴──────────┐                        │
│         │                      │                        │
│         ▼                      ▼                        │
│  ┌─────────────┐      ┌─────────────┐                 │
│  │  Contexts   │      │   Render    │                 │
│  │  Registry   │      │   Methods   │                 │
│  └─────────────┘      └─────────────┘                 │
│                                                           │
└─────────────────────────────────────────────────────────┘
```

### Cấu Trúc Context

Mỗi context có cấu trúc:

```php
[
    'admin' => [
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
    ],
]
```

### Cấu Trúc Thư Mục View

```
resources/views/
├── admin/
│   ├── components/
│   │   ├── button.blade.php
│   │   └── card.blade.php
│   ├── modules/
│   │   └── users/
│   │       ├── index.blade.php
│   │       ├── create.blade.php
│   │       └── edit.blade.php
│   ├── layouts/
│   │   └── main.blade.php
│   ├── templates/
│   │   └── form.blade.php
│   └── pages/
│       └── dashboard.blade.php
├── web/
│   ├── components/
│   ├── modules/
│   ├── layouts/
│   ├── templates/
│   └── pages/
│       ├── home.blade.php
│       └── about.blade.php
└── _system/
    └── ...
```

---

## 🔄 Luồng Hoạt Động

### 1. Luồng Đăng Ký Context

```
AppServiceProvider::boot()
    │
    ├─> Lấy ViewContextManager từ container
    │
    ├─> registerContext('admin', [...])
    │   │
    │   ├─> Lưu directories
    │   ├─> Tạo variables từ directories (nếu chưa có)
    │   └─> Lưu vào $contexts['admin']
    │
    └─> registerContext('web', [...])
        └─> Tương tự...
```

### 2. Luồng Render View

```
Service::render('index', $data)
    │
    ├─> ViewMethods::render('index', $data)
    │   │
    │   ├─> Lấy context từ $this->context
    │   ├─> Lấy module từ $this->module
    │   └─> getViewContextManager() từ container
    │
    ├─> ViewContextManager::renderModule($context, $module, 'index', $data)
    │   │
    │   ├─> resolvePath($context, $module, 'index', 'modules')
    │   │   │
    │   │   ├─> getBaseDirectory($context, 'modules')
    │   │   │   └─> Trả về: 'admin.modules'
    │   │   │
    │   │   └─> Tạo path: 'admin.modules.users.index'
    │   │
    │   ├─> getContextVariables($context)
    │   │   └─> Trả về: ['__component__' => 'admin.components.', ...]
    │   │
    │   ├─> Merge data:
    │   │   - Variables từ context
    │   │   - module_slug, module_name
    │   │   - Data từ service
    │   │
    │   └─> view('admin.modules.users.index', $mergedData)
    │
    └─> Trả về View instance
```

### 3. Luồng Cập Nhật Context (Đổi Theme)

```
Admin chọn theme
    │
    ├─> ThemeService::activateTheme('my-theme')
    │   │
    │   ├─> Lưu vào database
    │   │
    │   └─> ViewContextManager::updateContext('web', [
    │           'directories' => [
    │               'components' => 'themes.my-theme.components',
    │               'layouts' => 'themes.my-theme.layouts',
    │           ],
    │       ])
    │       │
    │       ├─> Cập nhật directories
    │       ├─> regenerateVariablesFromDirectories()
    │       └─> Context được cập nhật ngay lập tức
    │
    └─> Request tiếp theo sử dụng theme mới
```

---

## 🚀 Hướng Dẫn Triển Khai

### Bước 1: Đăng Ký ViewContextManager (Đã có sẵn)

ViewContextManager đã được đăng ký như singleton trong `OneServiceProvider`:

```php
// src/core/Providers/OneServiceProvider.php
$this->app->singleton(ViewContextManager::class, function ($app) {
    return new ViewContextManager();
});
```

### Bước 2: Đăng Ký Contexts Trong AppServiceProvider

```php
// app/Providers/AppServiceProvider.php

use One\Core\Engines\ViewContextManager;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
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
        ]);

        // Load theme nếu có
        $this->loadActiveTheme($contextManager);
    }

    protected function loadActiveTheme(ViewContextManager $contextManager)
    {
        $activeTheme = DB::table('settings')
            ->where('key', 'active_theme')
            ->value('value');

        if ($activeTheme) {
            $contextManager->updateContext('web', [
                'directories' => [
                    'components' => "themes.{$activeTheme}.components",
                    'layouts' => "themes.{$activeTheme}.layouts",
                    'templates' => "themes.{$activeTheme}.templates",
                ],
            ]);
        }
    }
}
```

### Bước 3: Tạo Service Với ViewMethods

```php
// app/Services/UserService.php

namespace App\Services;

use One\Core\Services\Service;
use One\Core\Support\Methods\ViewMethods;

class UserService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'users';
    protected $moduleName = 'Người Dùng';

    public function index()
    {
        return $this->render('index', [
            'users' => $this->getUsers(),
        ]);
    }

    public function create()
    {
        return $this->render('form', [
            'user' => new User(),
        ]);
    }

    public function edit($id)
    {
        return $this->render('form', [
            'user' => $this->getUser($id),
        ]);
    }
}
```

### Bước 4: Tạo Views

Tạo các file view theo cấu trúc:

```
resources/views/admin/modules/users/
├── index.blade.php
├── form.blade.php
└── detail.blade.php
```

---

## 📚 Hướng Dẫn Sử Dụng

### 1. Sử Dụng Cơ Bản

#### Render Module View

```php
class ProductService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'products';

    public function index()
    {
        // Render: admin.modules.products.index
        return $this->render('index', [
            'products' => $this->getProducts(),
        ]);
    }
}
```

#### Render Page View

```php
class PageService extends Service
{
    use ViewMethods;

    protected $context = 'web';
    protected $module = 'pages';

    public function home()
    {
        // Render: web.pages.home
        return $this->renderPage('home', [
            'title' => 'Trang Chủ',
        ]);
    }
}
```

#### Render Component

```php
// Trong blade file
@include($__component__ . 'button', ['text' => 'Click me'])

// Hoặc trong service
return $this->renderComponent('button', ['text' => 'Click me']);
```

### 2. Sử Dụng Variables Trong Blade

```blade
{{-- resources/views/admin/modules/users/index.blade.php --}}

@extends($__layout__ . 'main')

@section('content')
    <div class="container">
        {{-- Sử dụng component --}}
        @include($__component__ . 'card', ['title' => 'Danh Sách Người Dùng'])
        
        {{-- Sử dụng module path --}}
        <a href="{{ route('admin.users.create') }}">
            Thêm Mới
        </a>
        
        {{-- Variables có sẵn --}}
        <p>Module: {{ $module_slug }}</p>
        <p>Context: {{ $context }}</p>
    </div>
@endsection
```

### 3. Cập Nhật Context Động

#### Đổi Theme

```php
class ThemeService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'themes';

    public function activateTheme(string $themeName)
    {
        // Lưu vào database
        DB::table('settings')->updateOrInsert(
            ['key' => 'active_theme'],
            ['value' => $themeName]
        );

        // Cập nhật context
        $contextManager = $this->getViewContextManager();
        
        $contextManager->updateContext('web', [
            'directories' => [
                'components' => "themes.{$themeName}.components",
                'layouts' => "themes.{$themeName}.layouts",
                'templates' => "themes.{$themeName}.templates",
            ],
        ]);

        return true;
    }
}
```

#### Cập Nhật Variables

```php
$contextManager = app(ViewContextManager::class);

$contextManager->updateContextVariables('web', [
    '__widget__' => 'web.widgets.',
    '__partial__' => 'web.partials.',
]);
```

### 4. Truy Cập Trực Tiếp ViewContextManager

```php
// Lấy từ container
$contextManager = app(ViewContextManager::class);

// Hoặc từ service
$contextManager = $service->getViewContextManager();

// Kiểm tra context
if ($contextManager->hasContext('admin')) {
    // ...
}

// Lấy directories
$moduleDir = $contextManager->getBaseDirectory('admin', 'modules');

// Lấy variables
$variables = $contextManager->getContextVariables('admin');
$componentPath = $contextManager->getContextVariable('admin', '__component__');
```

---

## 💡 Ví Dụ Thực Tế

### Ví Dụ 1: CRUD Service

```php
class PostService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'posts';
    protected $moduleName = 'Bài Viết';

    public function index()
    {
        return $this->render('index', [
            'posts' => Post::paginate(20),
        ]);
    }

    public function create()
    {
        return $this->render('form', [
            'post' => new Post(),
            'categories' => Category::all(),
        ]);
    }

    public function store(Request $request)
    {
        $post = Post::create($request->validated());
        
        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Tạo bài viết thành công');
    }

    public function edit($id)
    {
        return $this->render('form', [
            'post' => Post::findOrFail($id),
            'categories' => Category::all(),
        ]);
    }
}
```

### Ví Dụ 2: Multi-Context Service

```php
class ContentService extends Service
{
    use ViewMethods;

    protected $module = 'content';

    public function renderForAdmin($view, $data = [])
    {
        $oldContext = $this->context;
        $this->context = 'admin';
        
        $result = $this->render($view, $data);
        
        $this->context = $oldContext;
        
        return $result;
    }

    public function renderForWeb($view, $data = [])
    {
        $oldContext = $this->context;
        $this->context = 'web';
        
        $result = $this->render($view, $data);
        
        $this->context = $oldContext;
        
        return $result;
    }
}
```

### Ví Dụ 3: Theme System (Giống WordPress)

```php
// app/Services/ThemeService.php
class ThemeService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'themes';

    public function getAvailableThemes(): array
    {
        $themesPath = resource_path('views/themes');
        $themes = [];
        
        foreach (glob($themesPath . '/*', GLOB_ONLYDIR) as $dir) {
            $themeName = basename($dir);
            $themes[] = [
                'name' => $themeName,
                'path' => $dir,
            ];
        }
        
        return $themes;
    }

    public function activateTheme(string $themeName): bool
    {
        // Validate theme exists
        $themePath = resource_path("views/themes/{$themeName}");
        if (!is_dir($themePath)) {
            throw new \Exception("Theme {$themeName} không tồn tại");
        }

        // Lưu vào database
        DB::table('settings')->updateOrInsert(
            ['key' => 'active_theme'],
            ['value' => $themeName, 'updated_at' => now()]
        );

        // Cập nhật context
        $contextManager = $this->getViewContextManager();
        
        $contextManager->updateContext('web', [
            'directories' => [
                'components' => "themes.{$themeName}.components",
                'layouts' => "themes.{$themeName}.layouts",
                'templates' => "themes.{$themeName}.templates",
                'modules' => 'web.modules', // Giữ nguyên
                'pages' => 'web.pages', // Giữ nguyên
            ],
            'variables' => [
                '__theme__' => "themes.{$themeName}.",
                '__theme_name__' => $themeName,
            ],
        ]);

        // Clear cache
        Artisan::call('view:clear');

        return true;
    }

    public function getActiveTheme(): ?string
    {
        return DB::table('settings')
            ->where('key', 'active_theme')
            ->value('value');
    }
}
```

### Ví Dụ 4: Sử Dụng Trong Controller

```php
// app/Http/Controllers/Admin/UserController.php
class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        return $this->userService->index();
    }

    public function create()
    {
        return $this->userService->create();
    }

    public function store(Request $request)
    {
        return $this->userService->store($request);
    }
}
```

---

## ✅ Best Practices

### 1. Đăng Ký Contexts

- ✅ Đăng ký tất cả contexts trong `AppServiceProvider::boot()`
- ✅ Load theme/settings từ database khi boot
- ✅ Sử dụng config file nếu có nhiều contexts

### 2. Service Design

- ✅ Mỗi service có một context và module rõ ràng
- ✅ Đặt `$context` và `$module` trong constructor hoặc property
- ✅ Sử dụng `$moduleName` để hiển thị tên module

### 3. View Organization

- ✅ Tổ chức views theo cấu trúc: `{context}/{type}/{module}/{blade}`
- ✅ Sử dụng components cho các phần tử tái sử dụng
- ✅ Sử dụng layouts cho cấu trúc chung
- ✅ Sử dụng templates cho các mẫu form/table

### 4. Variables

- ✅ Sử dụng variables trong blade: `$__component__`, `$__module__`, etc.
- ✅ Thêm variables tùy chỉnh khi cần: `__widget__`, `__partial__`
- ✅ Variables tự động được merge vào view data

### 5. Performance

- ✅ ViewContextManager là singleton, không tạo instance mới
- ✅ Contexts được cache trong memory (không reset trong Octane)
- ✅ Chỉ update context khi thực sự cần (đổi theme, settings)

### 6. Testing

```php
// tests/Feature/ViewContextTest.php
class ViewContextTest extends TestCase
{
    public function test_render_module_view()
    {
        $contextManager = app(ViewContextManager::class);
        
        $contextManager->registerContext('test', [
            'directories' => [
                'modules' => 'test.modules',
            ],
        ]);

        $view = $contextManager->renderModule('test', 'users', 'index', []);
        
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
    }
}
```

---

## 🔍 Troubleshooting

### Context không tồn tại

```php
// Kiểm tra context đã được đăng ký chưa
$contextManager = app(ViewContextManager::class);

if (!$contextManager->hasContext('admin')) {
    // Đăng ký context
    $contextManager->registerContext('admin', [...]);
}
```

### View không tìm thấy

```php
// Kiểm tra path đã được resolve đúng chưa
$path = $contextManager->resolvePath('admin', 'users', 'index', 'modules');
// Kết quả: 'admin.modules.users.index'

// Kiểm tra view có tồn tại không
if (!view()->exists($path)) {
    // Tạo view hoặc kiểm tra cấu trúc thư mục
}
```

### Variables không có trong view

```php
// Đảm bảo context đã có variables
$variables = $contextManager->getContextVariables('admin');

// Nếu không có, update context với variables
$contextManager->updateContextVariables('admin', [
    '__component__' => 'admin.components.',
    // ...
]);
```

---

## 📝 Tóm Tắt

1. **ViewContextManager** quản lý contexts và render view
2. **ViewMethods** trait cho service để sử dụng view dễ dàng
3. **Context** được đăng ký trong AppServiceProvider
4. **Service** có `$context` và `$module`, gọi `render()` để render view
5. **View path** được resolve tự động: `{context}.modules.{module}.{blade}`
6. **Variables** tự động được merge vào view data
7. **Update động** được hỗ trợ (đổi theme, settings)

---

## 🔗 Tài Liệu Liên Quan

- [VIEW_CONTEXT_MANAGER.md](./VIEW_CONTEXT_MANAGER.md) - Tài liệu cơ bản
- [VIEW_CONTEXT_UPDATE.md](./VIEW_CONTEXT_UPDATE.md) - Cập nhật context động
- [VIEW_CONTEXT_MANAGER_EXAMPLES.php](./VIEW_CONTEXT_MANAGER_EXAMPLES.php) - Ví dụ code

