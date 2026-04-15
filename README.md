# Saola Core

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-green.svg)](https://laravel.com)
[![Laravel Octane](https://img.shields.io/badge/Octane-2.x-orange.svg)](https://laravel.com/docs/octane)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

**Saola Core** — PHP/Laravel backend library của hệ sinh thái [Saola](https://github.com/saolabs), framework full-stack để build ứng dụng web reactive với Laravel + TypeScript + `.sao` templates.

---

## Hệ sinh thái Saola

| Package | Vai trò |
|---|---|
| **`saola/core`** *(repo này)* | Laravel backend core: repository, service, routing, events, cache... |
| [`saola-compiler`](https://github.com/saolabs/saola-compiler) | Biên dịch `.sao` templates → Blade (SSR) + JavaScript (CSR) |
| [`saola-client`](https://github.com/saolabs/saola-client) | TypeScript runtime: reactive views, state, hydration |

---

## Tính năng chính

### Repository Pattern
- `BaseRepository` với CRUD, Filter, Cache tích hợp
- Support MySQL và PostgreSQL
- `RepositoryTap` — safe operations với error handling
- Mask system — transform data trước khi trả về

### Service Layer
- `BaseService` — business logic layer
- `ModuleService` — module-based CRUD
- `ViewContextManager` — quản lý view state cho Saola client hydration
- Response methods tự động View/JSON theo request headers

### Module Routing
- Tổ chức routes theo modules thay vì file route phẳng
- `Module`, `Router`, `Action`, `ModuleService` classes
- Context-aware routing

### Magic Classes
- `Arr` — Array wrapper với magic methods
- `Str` — String utilities với hỗ trợ tiếng Việt
- `Any` — Universal data wrapper

### Engines
- `ShortCode` — WordPress-style shortcode system
- `ViewContextManager` — Multi-context view state
- `DCrypt` — Secure data encryption/decryption

### Event System
- Class-based event isolation, multi-listener support
- Octane-safe event dispatch

---

## Cài đặt

```bash
composer require saola/core
```

Publish config:

```bash
php artisan vendor:publish --tag=saola-config
```

---

## Cấu trúc thư mục

```
src/
├── App/                     # Application layer (Saola\Core\Framework\)
│   ├── Console/             # Artisan commands
│   ├── Database/            # Repositories & services
│   ├── Http/                # Controllers, middleware
│   ├── Providers/           # Service providers
│   ├── Routing/             # Module routing
│   ├── Services/            # Base services
│   ├── Support/             # Helpers, traits
│   └── View/                # View composers, compilers
└── core/                    # Saola\Core namespace
    ├── Concerns/            # Model traits
    ├── Engines/             # ShortCode, Cache, DCrypt
    ├── Events/              # Event system
    ├── Magic/               # Arr, Str, Any
    ├── Models/              # Base Eloquent models
    ├── Repositories/        # Repository pattern base
    └── ...
```

---

## Yêu cầu

- **PHP**: `^8.2`
- **Laravel**: `^12.0 | ^13.0`
- **Laravel Octane**: `^2.0` (optional)

Ghi chu: Laravel 13 yeu cau PHP >= 8.3.

---

## Tài liệu

- [Kiến trúc hệ thống](docs/ARCHITECTURE.md)
- [Roadmap & Workflow](docs/ROADMAP.md)
- [Repository Pattern](docs/BASE_REPOSITORY_QUERY_METHODS.md)
- [View Context Manager](docs/VIEW_CONTEXT_MANAGER.md)

---

## License

MIT © [SaoLabs Team](https://github.com/saolabs)


## 🚀 Tính năng chính

### 🔧 Core Engines
- **ShortCode Engine**: Hệ thống shortcode mạnh mẽ tương tự WordPress
- **View Manager**: Quản lý view và template với cache thông minh
- **Cache Engine**: Hệ thống cache đa lớp với auto-invalidation
- **DCrypt Engine**: Mã hóa/giải mã dữ liệu an toàn
- **JSON Data Engine**: Xử lý dữ liệu JSON hiệu quả

### 🎯 Magic Classes
- **Arr**: Array wrapper với magic methods và helper functions
- **Str**: String utilities với hỗ trợ tiếng Việt
- **Any**: Universal data wrapper cho mọi kiểu dữ liệu

### 🗂️ File Management
- **Filemanager**: Quản lý file và thư mục toàn diện
- **File Methods**: Các phương thức xử lý file nâng cao
- **Zip Methods**: Nén và giải nén file
- **File Converter**: Chuyển đổi định dạng file

### 🌐 HTTP & API
- **HTTP Client**: HTTP client với Promise support
- **CURL Wrapper**: CURL utilities nâng cao
- **Base API**: Framework cho API development
- **HTTP Promise**: Promise-based HTTP requests

### 🎨 HTML & UI
- **HTML Builder**: Tạo HTML elements programmatically
- **Form Builder**: Form generation với validation
- **Menu Builder**: Menu system linh hoạt
- **Input Types**: Input components đa dạng

### 📊 Repository Pattern
- **Base Repository**: Repository pattern implementation
- **CRUD Actions**: CRUD operations tự động
- **Filter Actions**: Advanced filtering và searching
- **Cache Tasks**: Cache management cho repositories
- **RepositoryTap**: Safe repository operations với error handling

### 🎨 Service Layer
- **ModuleService**: Service cho modules với CRUD operations
- **ViewService**: Service cho view rendering với ViewContextManager
- **ResponseMethods**: Tự động trả về View/JSON dựa trên headers
- **ViewMethods**: View rendering với context management
- **ModuleMethods**: Repository operations với error handling
- **CRUDMethods**: CRUD operations với validation

### 🔐 Security & Validation
- **Validators**: Validation system mở rộng
- **Default Methods**: Security utilities
- **System Mail Alert**: Email security alerts

### 🎯 Event System
- **EventMethods**: Hệ thống quản lý sự kiện mạnh mẽ
- **Event Dispatcher**: Event dispatching và handling
- **Event Methods**: Magic methods cho event management
- **Multi-listener Support**: Một event có thể có nhiều listeners
- **Class-based Isolation**: Mỗi class có vùng events riêng biệt

### 🌍 Internationalization
- **Locale Management**: Multi-language support
- **Language Files**: Dynamic language loading

## 📋 Yêu cầu hệ thống

- **PHP**: ^8.1
- **Laravel**: ^11.0 | ^12.0
- **Laravel Octane**: ^2.0 (tùy chọn)

## 🛠️ Cài đặt

### 1. Cài đặt qua Composer

```bash
composer require one/core
```

### 2. Đăng ký Service Provider

Service Provider sẽ được tự động đăng ký thông qua Laravel's auto-discovery.

### 3. Publish Configuration và Migrations (tùy chọn)

```bash
# Publish config file
php artisan vendor:publish --provider="One\Core\Providers\OneServiceProvider" --tag="one-config"

# Publish migrations
php artisan vendor:publish --provider="One\Core\Providers\OneServiceProvider" --tag="one-migrations"

# Hoặc publish tất cả
php artisan vendor:publish --provider="One\Core\Providers\OneServiceProvider"
```

### 4. Chạy Migrations

```bash
# Chạy migrations của thư viện
php artisan migrate

# Hoặc sử dụng command riêng
php artisan one:publish-migrations
php artisan migrate
```

## 🚀 Sử dụng nhanh

### ShortCode Engine

```php
use One\Core\Engines\ShortCode;

// Đăng ký shortcode
ShortCode::addShortcode('hello', function($atts, $content, $tag) {
    return '<h2>Xin chào từ shortcode!</h2>';
});

// Sử dụng trong nội dung
$content = "Đây là nội dung. [hello] Và đây là nội dung sau.";
$result = ShortCode::do($content, false);
```

### Magic Array

```php
use One\Core\Magic\Arr;

$data = new Arr(['name' => 'John', 'age' => 30]);

// Magic methods
echo $data->name; // John
echo $data->get('age'); // 30
echo $data->has('email'); // false

// Array operations
$data->set('email', 'john@example.com');
$data->remove('age');
```

### File Management

```php
use One\Core\Files\Filemanager;

$fm = new Filemanager('/path/to/directory');

// File operations
$fm->saveHtml('index.html', '<h1>Hello World</h1>');
$content = $fm->getHtml('index.html');

// Directory operations
$files = $fm->getList();
$fm->copy('source.txt', 'destination.txt');
```

### HTTP Client

```php
use One\Core\Http\Client;

$client = new Client();

// GET request
$response = $client->get('https://api.example.com/users');

// POST request với data
$response = $client->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Repository Pattern

```php
use One\Core\Repositories\BaseRepository;

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
use One\Core\Services\ModuleService;
use One\Core\Support\Methods\ViewMethods;
use One\Core\Support\Methods\ResponseMethods;
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
        
        // Tự động trả về view hoặc JSON dựa trên header
        return $this->response($request, [
            'users' => $users,
            'title' => 'Danh sách người dùng'
        ], 'users.index');
    }
}
```

**Request Headers:**
- `x-one-response: json` → Trả về JSON
- `Accept: application/json` → Trả về JSON
- Không có header → Trả về View

### Event System

```php
use One\Core\Events\EventMethods;

class UserService
{
    use EventMethods;
    
    public function createUser($data)
    {
        // Đăng ký event listeners
        static::on('user.creating', function($userData) {
            // Validate trước khi tạo
            return $userData;
        });
        
        static::on('user.created', function($user) {
            // Gửi email chào mừng
            Mail::to($user->email)->send(new WelcomeMail($user));
        });
        
        // Kích hoạt event trước khi tạo
        $data = static::trigger('user.creating', $data);
        
        $user = User::create($data);
        
        // Kích hoạt event sau khi tạo
        static::trigger('user.created', $user);
        
        return $user;
    }
}

// Sử dụng từ bên ngoài
UserService::on('user.created', function($user) {
    // Log activity
    Activity::log('user_created', $user);
});

// Kiểm tra event có tồn tại
if (UserService::hasEvent('user.created')) {
    // Event đã được đăng ký
}

### Event System - Chi tiết kỹ thuật

EventMethods trait cung cấp hệ thống quản lý sự kiện hoàn chỉnh với các tính năng:

#### **Cấu trúc dữ liệu**
- **Class-based Isolation**: Mỗi class có vùng events riêng biệt
- **Multi-listener Support**: Một event có thể có nhiều listeners
- **Case-insensitive**: Event names tự động chuyển về lowercase

#### **Các phương thức chính**
- `_addEventListener()`: Đăng ký listener cho event
- `_dispatchEvent()`: Kích hoạt event và thực thi listeners
- `_removeEvent()`: Xóa event listeners
- `_eventExists()`: Kiểm tra event có tồn tại
- `callEventMethod()`: Router chính để gọi các method

#### **Magic Methods Support**
Trait được thiết kế để hoạt động với magic methods:
- `__callStatic()`: Xử lý static method calls
- `__call()`: Xử lý instance method calls

#### **Return Values**
- `_dispatchEvent()` trả về mảng kết quả từ tất cả listeners
- Các method khác trả về boolean hoặc void tùy theo chức năng

#### **Ví dụ sử dụng thực tế**

```php
class OrderService
{
    use EventMethods;
    
    public function processOrder($orderData)
    {
        // Đăng ký các event listeners
        static::on('order.validating', function($data) {
            // Validate order data
            if (empty($data['items'])) {
                throw new Exception('Order must have items');
            }
            return $data;
        });
        
        static::on('order.processing', function($order) {
            // Update inventory
            foreach ($order->items as $item) {
                Inventory::decrease($item->product_id, $item->quantity);
            }
        });
        
        static::on('order.completed', function($order) {
            // Send confirmation email
            Mail::to($order->customer_email)->send(new OrderConfirmation($order));
            
            // Log activity
            Activity::log('order_completed', $order);
        });
        
        // Kích hoạt validation event
        $orderData = static::trigger('order.validating', $orderData);
        
        // Tạo order
        $order = Order::create($orderData);
        
        // Kích hoạt processing event
        static::trigger('order.processing', $order);
        
        // Cập nhật trạng thái
        $order->update(['status' => 'completed']);
        
        // Kích hoạt completion event
        static::trigger('order.completed', $order);
        
        return $order;
    }
}
```

## 🔧 Laravel Octane Support

One Core được thiết kế để tương thích hoàn toàn với Laravel Octane:

### Tự động State Management

```php
use One\Core\Contracts\OctaneCompatible;

class MyService implements OctaneCompatible
{
    private static $cache = [];
    
    public static function resetStaticState(): void
    {
        self::$cache = [];
    }
    
    public function resetInstanceState(): void
    {
        // Reset instance state
    }
    
    public static function getStaticProperties(): array
    {
        return ['cache'];
    }
}
```

### Octane Events

- **WorkerStarting**: Khởi tạo worker
- **RequestReceived**: Xử lý request mới
- **RequestTerminated**: Reset state sau request

## 📚 API Documentation

### ShortCode API

| Method | Description |
|--------|-------------|
| `ShortCode::addShortcode($tag, $callback)` | Đăng ký shortcode mới |
| `ShortCode::do($content, $ignore_html)` | Xử lý nội dung có shortcode |
| `ShortCode::hasShortcode($content, $tag)` | Kiểm tra shortcode có tồn tại |
| `ShortCode::removeShortcode($tag)` | Xóa shortcode |

### Arr API

| Method | Description |
|--------|-------------|
| `$arr->get($key, $default)` | Lấy giá trị theo key |
| `$arr->set($key, $value)` | Gán giá trị |
| `$arr->has($key)` | Kiểm tra key có tồn tại |
| `$arr->remove($key)` | Xóa key |
| `$arr->merge($array)` | Merge với array khác |

### Filemanager API

| Method | Description |
|--------|-------------|
| `$fm->saveHtml($filename, $content)` | Lưu file HTML |
| `$fm->getHtml($filename)` | Đọc file HTML |
| `$fm->copy($src, $dst)` | Copy file/thư mục |
| `$fm->move($src, $dst)` | Di chuyển file/thư mục |
| `$fm->delete($path)` | Xóa file/thư mục |

### Event System API

| Method | Description |
|--------|-------------|
| `static::on($event, $closure)` | Đăng ký event listener |
| `static::addEventListener($event, $closure)` | Đăng ký event listener (alias) |
| `static::trigger($event, ...$params)` | Kích hoạt event |
| `static::fire($event, ...$params)` | Kích hoạt event (alias) |
| `static::emit($event, ...$params)` | Kích hoạt event (alias) |
| `static::hasEvent($event)` | Kiểm tra event có tồn tại |
| `static::eventExists($event)` | Kiểm tra event có tồn tại (alias) |
| `static::hasEventListener($event)` | Kiểm tra event có tồn tại (alias) |
| `static::removeEvent($event, $closure)` | Xóa event listener |
| `static::off($event, $closure)` | Xóa event listener (alias) |
| `static::removeEventListener($event, $closure)` | Xóa event listener (alias) |

## 🧪 Testing

### Chạy tests

```bash
composer test
```

### Octane Compatibility Tests

```bash
php artisan test --filter=OctaneCompatibilityTest
```

## 🔒 Security

- Tất cả input được sanitize tự động
- SQL injection protection
- XSS protection
- CSRF protection
- Secure file operations

## 🌍 Internationalization

```php
use One\Core\Languages\Locale;

// Set language
Locale::setLang('vi');

// Get translation
$message = Locale::get('welcome.message');
```

## 📦 Package Structure

```
src/
├── core/
│   ├── Async/            # Async/await utilities
│   ├── Concerns/         # Traits và shared functionality
│   ├── Contracts/        # Interfaces và contracts
│   ├── Console/          # Console commands
│   ├── Crawlers/         # Web crawling utilities
│   ├── Database/         # Database utilities
│   ├── Engines/          # Core engines (ShortCode, Cache, ViewContextManager, etc.)
│   ├── Events/           # Event system (EventMethods, EventDispatcher)
│   ├── Files/            # File management system
│   ├── Html/             # HTML builders và components
│   ├── Http/             # HTTP client và utilities
│   ├── Languages/        # Internationalization
│   ├── Laravel/          # Laravel integrations
│   ├── Magic/            # Magic classes (Arr, Str, Any)
│   ├── Mailer/           # Email system
│   ├── Masks/            # Data masking và transformation
│   ├── Models/           # Base models
│   ├── Promise/          # Promise utilities
│   ├── Providers/        # Service providers
│   ├── Queues/           # Queue management
│   ├── Repositories/     # Repository pattern implementation
│   ├── Services/         # Service classes (ModuleService, ViewService, etc.)
│   ├── Support/Methods/  # Support methods (ViewMethods, ResponseMethods, etc.)
│   ├── System/           # System utilities
│   └── Validators/       # Validation system
├── helpers/              # Helper functions
└── tests/                # Test files
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📚 Documentation

### Tài Liệu Chi Tiết

- **[Quick Start Guide](./docs/QUICK_START_GUIDE.md)** - Hướng dẫn bắt đầu nhanh
- **[Recent Updates Guide](./docs/RECENT_UPDATES_GUIDE.md)** - Các thay đổi gần đây
- **[Structure Overview](./docs/STRUCTURE_OVERVIEW.md)** - Tổng quan cấu trúc
- **[Response Methods Usage](./docs/RESPONSE_METHODS_USAGE.md)** - Hướng dẫn ResponseMethods
- **[View Context Manager Guide](./docs/VIEW_CONTEXT_MANAGER_GUIDE.md)** - Hướng dẫn ViewContextManager
- **[Service Architecture Analysis](./docs/SERVICE_ARCHITECTURE_ANALYSIS.md)** - Phân tích kiến trúc Service
- **[Helpers Guide](./docs/HELPERS_GUIDE.md)** - Hướng dẫn sử dụng các hàm helper

### Tài Liệu Khác

Xem thêm trong thư mục [`docs/`](./docs/) để biết thêm chi tiết.

## 🆘 Support

- **Documentation**: [https://one.dev/docs](https://one.dev/docs)
- **Issues**: [GitHub Issues](https://github.com/one/core/issues)
- **Discussions**: [GitHub Discussions](https://github.com/one/core/discussions)
- **Email**: support@one.dev

## 🏆 Credits

Developed with ❤️ by the One Team

---

**One Core** - Empowering Laravel development with powerful tools and utilities.
