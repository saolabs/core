# ViewContextManager - Ví Dụ Sử Dụng

## Cú Pháp

```php
registerContext(string $name, array $directories, ?array $variables = null)
updateContext(string $context, array $directories, ?array $variables = null)
updateContextDirectories(string $context, array $directories, ?array $variables = null)
```

## Ví Dụ Đăng Ký Context

### Ví Dụ 1: Đăng Ký Cơ Bản (Tự Động Suy Diễn Variables)

```php
use One\Core\Engines\ViewContextManager;

$contextManager = app(ViewContextManager::class);

// Chỉ truyền Directories, Variables sẽ tự động suy diễn từ Directories['base']
$contextManager->registerContext('admin', [
    'base' => 'admin',
    'components' => 'admin.components',
    'modules' => 'admin.modules',
    'layouts' => 'admin.layouts',
    'templates' => 'admin.templates',
    'pages' => 'admin.pages',
]);

// Variables tự động được tạo:
// '__base__' => 'admin.'
// '__component__' => 'admin.components.'
// '__module__' => 'admin.modules.'
// '__layout__' => 'admin.layouts.'
// '__template__' => 'admin.templates.'
// '__page__' => 'admin.pages.'
// '__pagination__' => 'admin.pagination.'
```

### Ví Dụ 2: Đăng Ký Với Variables Tùy Chỉnh

```php
$contextManager->registerContext('web', [
    'base' => 'web',
    'components' => 'web.components',
    'modules' => 'web.modules',
    'layouts' => 'web.layouts',
    'templates' => 'web.templates',
    'pages' => 'web.pages',
], [
    '__widget__' => 'web.widgets.',
    '__partial__' => 'web.partials.',
    '__custom__' => 'web.custom.',
]);

// Variables sẽ bao gồm:
// - Các variables mặc định (từ base)
// - Các variables tùy chỉnh (__widget__, __partial__, __custom__)
```

### Ví Dụ 3: Đăng Ký Với Base Path Phức Tạp

```php
$contextManager->registerContext('api', [
    'base' => 'api.v1',
    'components' => 'api.shared.components',
    'modules' => 'api.v1.modules',
    'layouts' => 'api.layouts',
    'templates' => 'api.v1.templates',
    'pages' => 'api.pages',
]);

// Variables tự động:
// '__base__' => 'api.v1.'
// '__component__' => 'api.shared.components.'
// '__module__' => 'api.v1.modules.'
// '__layout__' => 'api.layouts.'
// '__template__' => 'api.v1.templates.'
// '__page__' => 'api.pages.'
```

### Ví Dụ 4: Đăng Ký Tối Thiểu (Chỉ Base)

```php
$contextManager->registerContext('simple', [
    'base' => 'simple',
]);

// Các directories khác sẽ được suy diễn từ base:
// 'components' => 'simple.components'
// 'modules' => 'simple.modules'
// 'layouts' => 'simple.layouts'
// 'templates' => 'simple.templates'
// 'pages' => 'simple.pages'
```

## Ví Dụ Cập Nhật Context

### Ví Dụ 1: Cập Nhật Directories (Tự Động Suy Diễn Variables)

```php
// Cập nhật directories, variables tự động suy diễn từ Directories['base']
$contextManager->updateContext('web', [
    'base' => 'themes.my-theme',
    'components' => 'themes.my-theme.components',
    'layouts' => 'themes.my-theme.layouts',
]);

// Variables tự động được cập nhật:
// '__base__' => 'themes.my-theme.'
// '__component__' => 'themes.my-theme.components.'
// '__layout__' => 'themes.my-theme.layouts.'
// Các variables khác giữ nguyên hoặc suy diễn từ base mới
```

### Ví Dụ 2: Cập Nhật Với Variables Tùy Chỉnh

```php
$contextManager->updateContext('web', [
    'base' => 'themes.my-theme',
    'components' => 'themes.my-theme.components',
    'layouts' => 'themes.my-theme.layouts',
], [
    '__theme__' => 'themes.my-theme.',
    '__theme_name__' => 'my-theme',
]);

// Variables sẽ được merge:
// - Variables mặc định từ base
// - Variables tùy chỉnh (__theme__, __theme_name__)
```

### Ví Dụ 3: Cập Nhật Chỉ Directories

```php
$contextManager->updateContextDirectories('web', [
    'base' => 'themes.new-theme',
    'layouts' => 'themes.new-theme.layouts',
]);

// Chỉ cập nhật directories, variables tự động suy diễn
```

### Ví Dụ 4: Cập Nhật Chỉ Variables

```php
$contextManager->updateContextVariables('web', [
    '__widget__' => 'web.widgets.',
    '__modal__' => 'web.modals.',
]);

// Chỉ cập nhật variables, không ảnh hưởng đến directories
```

## Ví Dụ Thực Tế: Theme System

### Đăng Ký Context Ban Đầu

```php
// app/Providers/AppServiceProvider.php

use One\Core\Engines\ViewContextManager;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $contextManager = app(ViewContextManager::class);

        // Đăng ký context 'web' ban đầu
        $contextManager->registerContext('web', [
            'base' => 'web',
            'components' => 'web.components',
            'modules' => 'web.modules',
            'layouts' => 'web.layouts',
            'templates' => 'web.templates',
            'pages' => 'web.pages',
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
            // Cập nhật context với theme
            $contextManager->updateContext('web', [
                'base' => "themes.{$activeTheme}",
                'components' => "themes.{$activeTheme}.components",
                'layouts' => "themes.{$activeTheme}.layouts",
                'templates' => "themes.{$activeTheme}.templates",
            ]);
        }
    }
}
```

### Đổi Theme

```php
// app/Services/ThemeService.php

class ThemeService extends Service
{
    use ViewMethods;

    protected $context = 'admin';
    protected $module = 'themes';

    public function activateTheme(string $themeName)
    {
        // Validate theme
        $themePath = resource_path("views/themes/{$themeName}");
        if (!is_dir($themePath)) {
            throw new \Exception("Theme {$themeName} không tồn tại");
        }

        // Lưu vào database
        DB::table('settings')->updateOrInsert(
            ['key' => 'active_theme'],
            ['value' => $themeName, 'updated_at' => now()]
        );

        // Cập nhật context - Variables tự động suy diễn từ base
        $contextManager = $this->getViewContextManager();
        
        $contextManager->updateContext('web', [
            'base' => "themes.{$themeName}",
            'components' => "themes.{$themeName}.components",
            'layouts' => "themes.{$themeName}.layouts",
            'templates' => "themes.{$themeName}.templates",
            // modules và pages giữ nguyên
            'modules' => 'web.modules',
            'pages' => 'web.pages',
        ]);

        // Clear cache
        Artisan::call('view:clear');

        return true;
    }
}
```

## Ví Dụ: Multi-Tenant

```php
// Đăng ký context cho tenant
$contextManager->registerContext('tenant.abc', [
    'base' => 'tenants.abc',
    'components' => 'tenants.abc.components',
    'modules' => 'tenants.abc.modules',
    'layouts' => 'tenants.abc.layouts',
]);

// Cập nhật khi switch tenant
$contextManager->updateContext('tenant.abc', [
    'base' => 'tenants.xyz',
    'components' => 'tenants.xyz.components',
    'layouts' => 'tenants.xyz.layouts',
]);
```

## Ví Dụ: API Context

```php
$contextManager->registerContext('api', [
    'base' => 'api.v1',
    'components' => 'api.shared.components',
    'modules' => 'api.v1.modules',
    'layouts' => 'api.layouts',
    'templates' => 'api.v1.templates',
    'pages' => 'api.pages',
], [
    '__version__' => 'v1.',
    '__api_base__' => 'api.v1.',
]);
```

## Lưu Ý

1. **Directories['base'] là bắt buộc** để suy diễn variables
2. **Variables tự động suy diễn** từ `Directories['base']` nếu không truyền
3. **Variables được merge** với variables hiện có khi update
4. **Directories được merge** với directories hiện có khi update
5. **Base path** được sử dụng để suy diễn các paths khác nếu không chỉ định

