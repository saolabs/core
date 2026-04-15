# ViewContextManager - Share Data

## Tổng Quan

Method `share()` cho phép share data cho một context cụ thể. Data được share sẽ tự động có sẵn trong mọi view của context đó, nhưng **KHÔNG ghi đè** các biến của Directories (variables).

## Cú Pháp

```php
share(string $context, array $data): self
```

## Đặc Điểm

- ✅ Share data cho một context cụ thể
- ✅ Data tự động có sẵn trong mọi view của context
- ✅ **KHÔNG ghi đè** các biến của Directories (__component__, __module__, etc.)
- ✅ Tự động reset sau mỗi request trong Octane
- ✅ Data từ `render()` có thể ghi đè shared data

## Thứ Tự Ưu Tiên Khi Merge Data

Khi render view, data được merge theo thứ tự:

1. **Variables (từ Directories)** - Ưu tiên cao nhất, KHÔNG được ghi đè
2. **Shared data** - Data được share cho context
3. **Module info** - module_slug, context
4. **Data từ render()** - Có thể ghi đè shared data nhưng không ghi đè variables

## Ví Dụ Sử Dụng

### Ví Dụ 1: Share Data Cơ Bản

```php
use One\Core\Engines\ViewContextManager;

$contextManager = app(ViewContextManager::class);

// Share data cho context 'admin'
$contextManager->share('admin', [
    'site_name' => 'My Admin Panel',
    'current_user' => auth()->user(),
    'notifications' => Notification::unread()->get(),
]);

// Bây giờ mọi view của context 'admin' sẽ có:
// - $site_name
// - $current_user
// - $notifications
// - Các variables từ Directories (__component__, __module__, etc.)
```

### Ví Dụ 2: Share Data Trong Middleware

```php
// app/Http/Middleware/ShareAdminData.php

class ShareAdminData
{
    public function handle($request, Closure $next)
    {
        $contextManager = app(ViewContextManager::class);

        // Share data cho context 'admin'
        $contextManager->share('admin', [
            'current_user' => auth()->user(),
            'admin_menu' => $this->getAdminMenu(),
            'site_settings' => $this->getSiteSettings(),
        ]);

        return $next($request);
    }
}
```

### Ví Dụ 3: Share Data Trong ServiceProvider

```php
// app/Providers/AppServiceProvider.php

use One\Core\Engines\ViewContextManager;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $contextManager = app(ViewContextManager::class);

        // Share data cho context 'web'
        $contextManager->share('web', [
            'site_name' => config('app.name'),
            'site_url' => config('app.url'),
            'current_year' => date('Y'),
        ]);

        // Share data cho context 'admin'
        if (auth()->check() && auth()->user()->isAdmin()) {
            $contextManager->share('admin', [
                'admin_user' => auth()->user(),
                'admin_stats' => $this->getAdminStats(),
            ]);
        }
    }
}
```

### Ví Dụ 4: Variables Không Bị Ghi Đè

```php
$contextManager = app(ViewContextManager::class);

// Đăng ký context với variables
$contextManager->registerContext('admin', [
    'base' => 'admin',
    'components' => 'admin.components',
], [
    '__component__' => 'admin.components.',
    '__custom__' => 'admin.custom.',
]);

// Share data - KHÔNG ghi đè variables
$contextManager->share('admin', [
    '__component__' => 'web.components.', // ❌ KHÔNG ghi đè được
    '__custom__' => 'web.custom.', // ❌ KHÔNG ghi đè được
    'site_name' => 'My Site', // ✅ OK, không phải variable
    'user' => auth()->user(), // ✅ OK
]);

// Kết quả trong view:
// __component__ => 'admin.components.' (từ Directories, KHÔNG bị ghi đè)
// __custom__ => 'admin.custom.' (từ Directories, KHÔNG bị ghi đè)
// site_name => 'My Site' (từ shared data)
// user => User object (từ shared data)
```

### Ví Dụ 5: Data Từ Render() Có Thể Ghi Đè Shared Data

```php
// Share data
$contextManager->share('admin', [
    'title' => 'Default Title',
    'user' => auth()->user(),
]);

// Render với data riêng
$contextManager->renderModule('admin', 'users', 'index', [
    'title' => 'User List', // ✅ Ghi đè shared data
    'users' => User::all(), // ✅ Thêm data mới
]);

// Kết quả trong view:
// title => 'User List' (từ render(), ghi đè shared data)
// user => User object (từ shared data, không bị ghi đè)
// users => Collection (từ render())
```

### Ví Dụ 6: Clear Shared Data

```php
$contextManager = app(ViewContextManager::class);

// Clear shared data của một context
$contextManager->clearSharedData('admin');

// Clear tất cả shared data
$contextManager->clearAllSharedData();
```

### Ví Dụ 7: Lấy Shared Data

```php
$contextManager = app(ViewContextManager::class);

// Lấy shared data của một context
$sharedData = $contextManager->getSharedData('admin');
// Kết quả: ['site_name' => '...', 'user' => ...]
```

## Sử Dụng Trong View

```blade
{{-- resources/views/admin/modules/users/index.blade.php --}}

@extends($__layout__ . 'main')

@section('content')
    <div class="container">
        {{-- Sử dụng shared data --}}
        <h1>{{ $site_name }}</h1>
        <p>Welcome, {{ $current_user->name }}</p>
        
        {{-- Sử dụng variables từ Directories (không bị ghi đè) --}}
        @include($__component__ . 'card', ['title' => 'Users'])
        
        {{-- Sử dụng data từ render() --}}
        <ul>
            @foreach($users as $user)
                <li>{{ $user->name }}</li>
            @endforeach
        </ul>
    </div>
@endsection
```

## Octane Compatibility

Shared data tự động được reset sau mỗi request trong Octane:

```php
// ViewContextManager::resetInstanceState()
public function resetInstanceState(): void
{
    // Reset shared data sau mỗi request
    $this->sharedData = [];
}
```

## Best Practices

1. **Share data trong Middleware hoặc ServiceProvider**: Đảm bảo data có sẵn cho mọi view
2. **Không share variables**: Variables từ Directories không thể bị ghi đè, không cần share
3. **Share data chung**: Chỉ share data cần thiết cho nhiều views
4. **Clear khi cần**: Clear shared data khi không cần nữa để tránh memory leak

## Lưu Ý

- ✅ Shared data **KHÔNG ghi đè** variables từ Directories
- ✅ Data từ `render()` **có thể ghi đè** shared data
- ✅ Shared data được **reset sau mỗi request** trong Octane
- ✅ Shared data chỉ áp dụng cho **context cụ thể**

