# Cập Nhật Context Động - Ví Dụ: Đổi Theme

## Tổng Quan

ViewContextManager được thiết kế để **KHÔNG bị reset** sau mỗi request trong Octane, cho phép cập nhật context động (ví dụ: khi admin đổi theme).

## Đặc Điểm

- ✅ **Persistent State**: Contexts được giữ lại giữa các requests
- ✅ **Update Động**: Có thể cập nhật context bất cứ lúc nào
- ✅ **Octane Safe**: Được đăng ký như singleton, không bị reset
- ✅ **Auto Regenerate Variables**: Tự động tái tạo variables khi update directories

## Cách Cập Nhật Context

### 1. Cập Nhật Directories

```php
use One\Core\Engines\ViewContextManager;

$contextManager = app(ViewContextManager::class);

// Cập nhật directories của context 'web' (ví dụ: đổi theme)
$contextManager->updateContextDirectories('web', [
    'components' => 'themes.my-theme.components',
    'modules' => 'web.modules',
    'layouts' => 'themes.my-theme.layouts',
    'templates' => 'themes.my-theme.templates',
    'pages' => 'web.pages',
]);

// Variables sẽ tự động được tái tạo từ directories mới
```

### 2. Cập Nhật Variables

```php
// Cập nhật chỉ variables
$contextManager->updateContextVariables('web', [
    '__widget__' => 'themes.my-theme.widgets.',
    '__partial__' => 'themes.my-theme.partials.',
]);
```

### 3. Cập Nhật Toàn Bộ Context

```php
// Cập nhật cả directories và variables
$contextManager->updateContext('web', [
    'directories' => [
        'components' => 'themes.my-theme.components',
        'layouts' => 'themes.my-theme.layouts',
    ],
    'variables' => [
        '__widget__' => 'themes.my-theme.widgets.',
    ],
]);
```

## Ví Dụ: Hệ Thống Đổi Theme (Giống WordPress)

### Bước 1: Lưu Theme Vào Database

```php
// Migration
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->timestamps();
});

// Lưu theme đã chọn
DB::table('settings')->updateOrInsert(
    ['key' => 'active_theme'],
    ['value' => 'my-theme']
);
```

### Bước 2: Service Đổi Theme

```php
class ThemeService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'themes';

    public function activateTheme(string $themeName)
    {
        // Lưu theme vào database
        DB::table('settings')->updateOrInsert(
            ['key' => 'active_theme'],
            ['value' => $themeName]
        );

        // Cập nhật context 'web' với theme mới
        $contextManager = $this->getViewContextManager();
        
        $contextManager->updateContext('web', [
            'directories' => [
                'components' => "themes.{$themeName}.components",
                'modules' => 'web.modules', // Giữ nguyên modules
                'layouts' => "themes.{$themeName}.layouts",
                'templates' => "themes.{$themeName}.templates",
                'pages' => 'web.pages', // Giữ nguyên pages
            ],
            'variables' => [
                '__theme__' => "themes.{$themeName}.",
                '__theme_name__' => $themeName,
            ],
        ]);

        // Clear view cache nếu có
        if (method_exists($this, 'clearViewCache')) {
            $this->clearViewCache();
        }

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

### Bước 3: Load Theme Khi Khởi Động

```php
// Trong AppServiceProvider::boot()

use One\Core\Engines\ViewContextManager;

$contextManager = app(ViewContextManager::class);

// Đăng ký context 'web' ban đầu
$contextManager->registerContext('web', [
    'directories' => [
        'components' => 'web.components',
        'modules' => 'web.modules',
        'layouts' => 'web.layouts',
        'templates' => 'web.templates',
        'pages' => 'web.pages',
    ],
]);

// Load theme đã chọn từ database
$activeTheme = DB::table('settings')
    ->where('key', 'active_theme')
    ->value('value');

if ($activeTheme) {
    // Cập nhật context với theme
    $contextManager->updateContext('web', [
        'directories' => [
            'components' => "themes.{$activeTheme}.components",
            'modules' => 'web.modules',
            'layouts' => "themes.{$activeTheme}.layouts",
            'templates' => "themes.{$activeTheme}.templates",
            'pages' => 'web.pages',
        ],
        'variables' => [
            '__theme__' => "themes.{$activeTheme}.",
            '__theme_name__' => $activeTheme,
        ],
    ]);
}
```

### Bước 4: Sử Dụng Trong Controller

```php
class ThemeController extends Controller
{
    public function activate(Request $request, ThemeService $themeService)
    {
        $themeName = $request->input('theme');
        
        $themeService->activateTheme($themeName);
        
        return redirect()->back()->with('success', 'Theme đã được kích hoạt');
    }
}
```

## Ví Dụ: Multi-Tenant Với Context Động

```php
class TenantService extends Service
{
    public function switchTenant(string $tenantId)
    {
        $tenant = Tenant::find($tenantId);
        
        $contextManager = app(ViewContextManager::class);
        
        // Cập nhật context 'web' với tenant-specific paths
        $contextManager->updateContext('web', [
            'directories' => [
                'components' => "tenants.{$tenant->slug}.components",
                'modules' => 'web.modules',
                'layouts' => "tenants.{$tenant->slug}.layouts",
                'templates' => "tenants.{$tenant->slug}.templates",
                'pages' => "tenants.{$tenant->slug}.pages",
            ],
            'variables' => [
                '__tenant__' => "tenants.{$tenant->slug}.",
                '__tenant_id__' => $tenant->id,
                '__tenant_slug__' => $tenant->slug,
            ],
        ]);
    }
}
```

## Lưu Ý Quan Trọng

### 1. ViewContextManager Là Singleton

ViewContextManager được đăng ký như singleton trong `OneServiceProvider`, đảm bảo:
- Cùng một instance giữa các requests trong Octane
- Contexts được giữ lại sau mỗi request
- Có thể cập nhật từ bất kỳ request nào

### 2. Không Reset Trong Octane

ViewContextManager **KHÔNG** reset contexts trong `resetInstanceState()`:
- Contexts là persistent state, không phải request-specific state
- Cần được giữ lại để có thể update động
- OctaneServiceProvider đã được cấu hình để không reset ViewContextManager

### 3. Auto Regenerate Variables

Khi update directories, variables sẽ tự động được tái tạo:
- Giữ lại các variables tùy chỉnh đã có
- Tạo mới các variables từ directories mới
- Đảm bảo consistency giữa directories và variables

### 4. Cache Considerations

Khi update context, có thể cần clear cache:
- View cache (nếu có)
- Route cache
- Config cache

## Best Practices

1. **Đăng ký context ban đầu trong ServiceProvider**: Đảm bảo context có sẵn khi app khởi động
2. **Load từ database khi boot**: Nếu có settings (như theme), load và apply khi boot
3. **Update khi cần**: Có thể update context bất cứ lúc nào (controller, middleware, etc.)
4. **Clear cache sau update**: Đảm bảo view cache được clear sau khi update
5. **Validate trước khi update**: Kiểm tra theme/path có tồn tại trước khi update

## API Reference

### ViewContextManager Methods

- `updateContextDirectories($context, $directories)` - Cập nhật directories, auto regenerate variables
- `updateContextVariables($context, $variables)` - Chỉ cập nhật variables
- `updateContext($context, $config)` - Cập nhật cả directories và variables
- `registerContext($name, $config)` - Đăng ký context mới
- `getContextConfig($context)` - Lấy toàn bộ config của context

