# Saola Core

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-green.svg)](https://laravel.com)
[![Laravel Octane](https://img.shields.io/badge/Octane-2.x-orange.svg)](https://laravel.com/docs/octane)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

**Saola Core** — PHP/Laravel backend library của hệ sinh thái [Saola](https://github.com/saolabs), framework full-stack để xây dựng ứng dụng web reactive với Laravel + TypeScript + `.sao` templates.

---

## Hệ sinh thái Saola

| Package | Vai trò |
|---|---|
| **`saola/core`** *(repo này)* | Laravel backend core: repository, service, routing, events, cache, view context manager... |
| [`saola-compiler`](https://github.com/saolabs/saola-compiler) | Biên dịch `.sao` templates → Blade (SSR) + JavaScript (CSR) |
| [`saola-client`](https://github.com/saolabs/saola-client) | TypeScript runtime: reactive views, state, hydration |

---

## Tính năng chính

### 🔧 Core Engines
- **ShortCode Engine**: Hệ thống shortcode tương tự WordPress (`Saola\Core\Engines\ShortCode`)
- **ViewContextManager**: Quản lý đa context view state cho Saola client hydration
- **Cache Engine**: Cấu hình cache đa lớp với auto-invalidation
- **DCrypt Engine**: Mã hóa/giải mã dữ liệu an toàn

### 🎯 Magic Classes
- **Arr**: Array wrapper với magic methods và helper functions (`Saola\Core\Magic\Arr`)
- **Str**: String utilities với hỗ trợ tiếng Việt
- **Any**: Universal data wrapper cho mọi kiểu dữ liệu

### 🗂️ File Management
- **Filemanager**: Quản lý file và thư mục toàn diện
- **Zip / File Methods**: Nén, giải nén và chuyển đổi định dạng file

### 🌐 HTTP & API
- **HTTP Client**: Client với Promise support (`Saola\Core\Http\Client`)
- **CURL Wrapper**: Support async / curl multi requests

### 📊 Repository Pattern
- **BaseRepository**: CRUD, Filter, Search, Pagination và Cache integration (`Saola\Core\Repositories\BaseRepository`)
- **RepositoryTap**: Safe repository operations với error handling
- **Support Databases**: MySQL & PostgreSQL

### 🎨 Service Layer & Auto Response
- **BaseService / ModuleService**: Business logic & Module CRUD management (`Saola\Core\Services\ModuleService`)
- **ResponseMethods**: Tự động trả về View hoặc JSON dựa trên request headers (`X-Saola-Response`, `Accept: application/json`)
- **ViewMethods**: Integrated ViewContextManager rendering

### 🎯 Event System
- **EventMethods**: Class-based isolation, multi-listener support, magic method event triggering (`Saola\Core\Events\EventMethods`)

---

## Yêu cầu hệ thống

- **PHP**: `^8.2`
- **Laravel**: `^12.0 | ^13.0`
- **Laravel Octane**: `^2.0` (tùy chọn)

---

## Cài đặt

### 1. Cài đặt qua Composer

```bash
composer require saola/core
```

### 2. Đăng ký Service Provider & Publish Config

Service Provider `Saola\Core\Providers\SaolaServiceProvider` tự động đăng ký qua Laravel Auto-Discovery.

Publish configuration:

```bash
php artisan vendor:publish --tag=saola-config
```

Publish migrations (tùy chọn):

```bash
php artisan vendor:publish --tag=saola-migrations
php artisan migrate
```

---

## Cấu trúc thư mục

```
src/
├── config/                  # Configuration files (saola.php)
├── core/                    # Saola\Core namespace root
│   ├── Async/               # Async/await utilities
│   ├── Concerns/            # Model traits (HasUuid, HasSlug, ...)
│   ├── Console/             # Artisan commands
│   ├── Contracts/           # Interfaces & contracts
│   ├── Crawlers/            # Web crawling utilities
│   ├── Database/            # DB utilities & query helpers
│   ├── Engines/             # ShortCode, Cache, DCrypt, ViewContextManager
│   ├── Events/              # Event system (EventMethods)
│   ├── Exceptions/          # Exception handlers
│   ├── Files/               # File management system
│   ├── Html/                # HTML, Form, Menu builders
│   ├── Http/                # HTTP client & utilities
│   ├── Languages/           # Locale management (i18n)
│   ├── Laravel/             # Laravel framework integrations
│   ├── Magic/               # Arr, Str, Any magic classes
│   ├── Mailer/              # Email alert & mailer system
│   ├── Masks/               # Data masking & transformation
│   ├── Models/              # Base Eloquent models
│   ├── Promise/             # Promise & async utilities
│   ├── Providers/           # SaolaServiceProvider
│   ├── Queues/              # Queue management
│   ├── Repositories/        # BaseRepository, Filter & CRUD actions
│   ├── Routing/             # Module routing system (Action, Module, Router)
│   ├── Services/            # BaseService, ModuleService, ViewService
│   ├── Support/             # Utility helpers & traits (Methods/ViewMethods, etc.)
│   ├── System/              # System utilities
│   └── Validators/          # Validation system
├── helpers/                 # Global helpers (__loader__.php, helpers.php)
└── templates/               # Default templates
```

---

## Ví dụ sử dụng

### ShortCode Engine

```php
use Saola\Core\Engines\ShortCode;

// Đăng ký shortcode
ShortCode::addShortcode('hello', function($atts, $content, $tag) {
    return '<h2>Xin chào từ shortcode!</h2>';
});

// Sử dụng trong nội dung
$content = "Nội dung gốc. [hello] Nội dung sau.";
$result = ShortCode::do($content, false);
```

### Magic Array

```php
use Saola\Core\Magic\Arr;

$data = new Arr(['name' => 'Saola', 'version' => '2.0']);

echo $data->name; // Saola
echo $data->get('version'); // 2.0
$data->set('type', 'framework');
```

### Repository Pattern

```php
use Saola\Core\Repositories\BaseRepository;

class UserRepository extends BaseRepository
{
    protected $model = User::class;

    public function findByEmail($email)
    {
        return $this->model::where('email', $email)->first();
    }
}
```

### Service Layer với Auto View/JSON Response

```php
use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\ResponseMethods;
use Illuminate\Http\Request;

class UserService extends ModuleService
{
    use ViewMethods, ResponseMethods;

    protected $context = 'web';
    protected $module = 'users';

    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
    }

    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);

        // Tự động trả về view hoặc JSON dựa trên request headers
        return $this->response([
            'users' => $users,
            'title' => 'Danh sách người dùng'
        ], 'users.index');
    }
}
```

**Request Headers kiểm tra:**
- `X-Saola-Response: json` hoặc `X-Sao-Response: json` → Trả về JSON
- `Accept: application/json` → Trả về JSON
- Request thông thường → Trả về Blade View

### Event System

```php
use Saola\Core\Events\EventMethods;

class UserService
{
    use EventMethods;

    public function createUser($data)
    {
        static::on('user.created', function($user) {
            // Gửi email hoặc log activity
        });

        $user = User::create($data);
        static::trigger('user.created', $user);

        return $user;
    }
}
```

---

## Laravel Octane Support

Saola Core hỗ trợ tối ưu state management cho Laravel Octane qua contract `Saola\Core\Contracts\OctaneCompatible`:

```php
use Saola\Core\Contracts\OctaneCompatible;

class MyService implements OctaneCompatible
{
    private static $cache = [];

    public static function resetStaticState(): void
    {
        self::$cache = [];
    }

    public function resetInstanceState(): void
    {
    }

    public static function getStaticProperties(): array
    {
        return ['cache'];
    }
}
```

---

## Tài liệu chi tiết

- **[Kiến trúc hệ thống](docs/ARCHITECTURE.md)**
- **[Tổng quan cấu trúc](docs/STRUCTURE_OVERVIEW.md)**
- **[Quick Start Guide](docs/QUICK_START_GUIDE.md)**
- **[Recent Updates Guide](docs/RECENT_UPDATES_GUIDE.md)**
- **[Response Methods Usage](docs/RESPONSE_METHODS_USAGE.md)**
- **[View Context Manager Guide](docs/VIEW_CONTEXT_MANAGER_GUIDE.md)**

Xem toàn bộ danh mục tài liệu trong thư mục [`docs/`](./docs/README.md).

---

## License

MIT © [SaoLabs Team](https://github.com/saolabs)
