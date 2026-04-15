# ViewPathResolver - Cơ Chế Quản Lý View Linh Hoạt

## Tổng Quan

`ViewPathResolver` là một cơ chế quản lý view paths linh hoạt, cho phép dễ dàng thay đổi các base directories trong runtime và tuân thủ hoàn toàn chuẩn Laravel Octane.

## Đặc Điểm

- ✅ **Dễ dàng thay đổi base dir**: Có thể thay đổi base directories trong runtime
- ✅ **Octane Compatible**: Không có static state leak, reset đúng cách sau mỗi request
- ✅ **Hỗ trợ multiple base dirs**: Quản lý nhiều base directories với priority
- ✅ **Tương thích ngược**: Hoạt động song song với logic cũ trong `ViewMethods`
- ✅ **Alias support**: Hỗ trợ các alias như `@module`, `@page`, `@base`, `@component`, etc.

## Cấu Trúc

### ViewPathResolver Class

Class chính quản lý các base directories:

```php
use One\Core\Engines\ViewPathResolver;

$resolver = new ViewPathResolver([
    'defaultBase' => 'web',
    'context' => 'admin',
    'viewFolder' => 'v2',
]);
```

### Tích Hợp Với ViewMethods

`ViewMethods` trait đã được cập nhật để hỗ trợ `ViewPathResolver`:

```php
use One\Core\Support\Methods\ViewMethods;

class MyService
{
    use ViewMethods;

    public function __construct()
    {
        // Bật sử dụng ViewPathResolver
        $this->useViewPathResolver(true);
        
        // Khởi tạo view
        $this->initView();
    }
}
```

## Cách Sử Dụng

### 1. Bật ViewPathResolver

```php
// Trong service constructor hoặc init method
$this->useViewPathResolver(true);
$this->initView();
```

### 2. Thay Đổi Base Directory

#### Thay đổi một base directory cụ thể:

```php
// Thay đổi base directory
$this->setBaseDirectory('base', 'admin.v2', 100);

// Thay đổi module directory
$this->setBaseDirectory('module', 'admin.v2.modules', 90);

// Thay đổi page directory
$this->setBaseDirectory('page', 'admin.v2.pages', 80);

// Thay đổi component directory
$this->setBaseDirectory('component', 'admin.v2.components', 70);
```

#### Thay đổi nhiều base directories cùng lúc:

```php
$this->setViewConfig([
    'context' => 'admin',
    'viewFolder' => 'v2',
    'baseDirectories' => [
        'base' => [
            'path' => 'admin.v2',
            'priority' => 100,
        ],
        'module' => [
            'path' => 'admin.v2.modules',
            'priority' => 90,
        ],
        'page' => [
            'path' => 'admin.v2.pages',
            'priority' => 80,
        ],
        'component' => [
            'path' => 'admin.v2.components',
            'priority' => 70,
        ],
    ],
]);
```

### 3. Sử Dụng Alias

ViewPathResolver hỗ trợ các alias để truy cập view dễ dàng:

```php
// Sử dụng alias trong render
$this->render('@module.index');        // => admin.v2.modules.index
$this->render('@page.about');          // => admin.v2.pages.about
$this->render('@base.home');           // => admin.v2.home
$this->render('@component.button');    // => admin.v2.components.button
$this->render('@layout.main');         // => admin.v2.layouts.main
```

### 4. Truy Cập Trực Tiếp ViewPathResolver

```php
$resolver = $this->getViewPathResolver();

// Lấy base directory
$basePath = $resolver->getBaseDirectory('base');

// Resolve path
$resolvedPath = $resolver->resolve('@module.index');

// Lấy tất cả base directories
$allDirs = $resolver->getAllBaseDirectories();

// Lấy default view data
$viewData = $resolver->getDefaultViewData([
    'module_slug' => 'my-module',
    'module_name' => 'My Module',
]);
```

## Các Base Directories Mặc Định

ViewPathResolver tự động tạo các base directories sau:

| Tên | Path Mặc Định | Priority | Mô Tả |
|-----|---------------|----------|-------|
| `base` | `{context}.{viewFolder}` | 100 | Base path chính |
| `module` | `{base}.modules` | 90 | Module views |
| `page` | `{base}.pages` | 80 | Page views |
| `component` | `{base}.components` | 70 | Component views |
| `template` | `{base}.templates` | 60 | Template views |
| `layout` | `{base}.layouts` | 50 | Layout views |
| `pagination` | `{base}.pagination` | 40 | Pagination views |

## Octane Compatibility

ViewPathResolver tuân thủ hoàn toàn chuẩn Octane:

1. **Không có static state**: Tất cả state được lưu trong instance
2. **Reset instance state**: Method `resetInstanceState()` clear cache sau mỗi request
3. **Implement OctaneCompatible interface**: Đảm bảo tương thích với Octane lifecycle

### Tự Động Reset

ViewPathResolver sẽ tự động được reset thông qua `OctaneServiceProvider`:

```php
// Trong OctaneServiceProvider
protected function resetViewEngines(): void
{
    // ViewPathResolver instances sẽ được reset
    // thông qua resetServicesState()
}
```

## Ví Dụ Sử Dụng

### Ví Dụ 1: Thay Đổi Context

```php
class AdminService
{
    use ViewMethods;

    public function __construct()
    {
        $this->context = 'admin';
        $this->useViewPathResolver(true);
        $this->initView();
    }

    public function switchToV2()
    {
        // Chuyển sang version 2
        $this->setViewConfig([
            'viewFolder' => 'v2',
        ]);
    }
}
```

### Ví Dụ 2: Multi-Tenant Views

```php
class TenantService
{
    use ViewMethods;

    public function setTenant(string $tenant)
    {
        $this->useViewPathResolver(true);
        
        // Mỗi tenant có base directory riêng
        $this->setBaseDirectory('base', "tenants.{$tenant}", 100);
        $this->setBaseDirectory('module', "tenants.{$tenant}.modules", 90);
        $this->setBaseDirectory('page', "tenants.{$tenant}.pages", 80);
        
        $this->initView();
    }
}
```

### Ví Dụ 3: Theme Support

```php
class ThemeService
{
    use ViewMethods;

    public function setTheme(string $theme)
    {
        $this->useViewPathResolver(true);
        
        // Theme có priority cao hơn base
        $this->setBaseDirectory('base', "themes.{$theme}", 150);
        $this->setBaseDirectory('layout', "themes.{$theme}.layouts", 140);
        $this->setBaseDirectory('component', "themes.{$theme}.components", 130);
        
        $this->initView();
    }
}
```

## Migration từ Code Cũ

Nếu bạn đang sử dụng code cũ, bạn có thể:

1. **Giữ nguyên code cũ**: Không cần thay đổi gì, code vẫn hoạt động bình thường
2. **Từ từ migrate**: Bật `useViewPathResolver(true)` khi cần
3. **Hybrid**: Sử dụng cả hai cơ chế song song

```php
// Code cũ vẫn hoạt động
$this->initView();
$this->render('index');

// Hoặc sử dụng ViewPathResolver
$this->useViewPathResolver(true);
$this->initView();
$this->render('@module.index');
```

## Best Practices

1. **Luôn gọi `initView()` sau khi thay đổi config**: Đảm bảo các paths được sync đúng
2. **Sử dụng alias**: Giúp code dễ đọc và maintain hơn
3. **Reset state trong Octane**: ViewPathResolver tự động reset, không cần lo lắng
4. **Priority**: Sử dụng priority để quản lý thứ tự resolve paths
5. **Clone instance**: Sử dụng `clone()` nếu cần tạo instance mới từ instance hiện tại

## API Reference

### ViewPathResolver Methods

- `setBaseDirectory(string $name, string $path, int $priority = 100): self`
- `getBaseDirectory(string $name): ?string`
- `removeBaseDirectory(string $name): self`
- `setContext(string $context): self`
- `setViewFolder(?string $viewFolder): self`
- `resolve(string $path): string`
- `getAllBaseDirectories(): array`
- `getDefaultViewData(array $additionalData = []): array`
- `resetInstanceState(): void`
- `clone(): self`

### ViewMethods Methods (Mới)

- `useViewPathResolver(bool $use = true): self`
- `getViewPathResolver(): ?ViewPathResolver`
- `setBaseDirectory(string $name, string $path, int $priority = 100): self`
- `resetViewState(): void`

## Troubleshooting

### View không tìm thấy

Kiểm tra:
1. Base directory đã được set đúng chưa?
2. View file có tồn tại không?
3. Đã gọi `initView()` sau khi thay đổi config chưa?

### Octane state leak

ViewPathResolver không có static state, nhưng nếu bạn lưu instance trong static property, cần reset thủ công:

```php
// Trong OctaneServiceProvider
protected function resetServicesState(): void
{
    // ViewPathResolver instances sẽ được reset tự động
    // nếu được đăng ký trong container
}
```

## Kết Luận

ViewPathResolver cung cấp một cơ chế quản lý view paths linh hoạt và mạnh mẽ, đảm bảo:
- Dễ dàng thay đổi base directories
- Tuân thủ chuẩn Octane
- Tương thích với code hiện tại
- Hỗ trợ nhiều use cases phức tạp

