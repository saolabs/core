# Triển khai Blade Directive `@startReactive` / `@endReactive` cho Laravel

## Tổng quan

Các directive `@startReactive` và `@endReactive` được OneView compiler tự động chèn vào blade template để đánh dấu các vùng reactive — những đoạn HTML phụ thuộc vào state variables và cần được hydrate/re-render phía client.

Khi blade render, các directive này sẽ gọi helper method để in ra HTML marker (comment hoặc attribute) giúp JS runtime biết vùng nào cần theo dõi và cập nhật khi state thay đổi.

---

## 1. Cú pháp từ Compiler Output

### `@startReactive`

```blade
@startReactive($type, $registryID, $stateKeys, $options = [])
```

**Tham số:**

| Tham số | Kiểu | Mô tả |
|---------|------|--------|
| `$type` | `string` | Loại directive reactive. Một trong: `'if'`, `'foreach'`, `'for'`, `'while'`, `'switch'`, `'output'`, `'include'`, `'isset'`, `'empty'` |
| `$registryID` | `string` (PHP expression) | ID duy nhất, format: `'rc-' . $__VIEW_ID__ . '-{type}-{N}'`. Đây là **biểu thức PHP**, không phải chuỗi tĩnh |
| `$stateKeys` | `array` | Danh sách tên state variables mà vùng này phụ thuộc, ví dụ: `['count']`, `['products', 'users']` |
| `$options` | `array` (optional) | Tùy chọn bổ sung. Hiện chỉ dùng cho type `'output'` |

### `@endReactive`

```blade
@endReactive($type, $registryID)
```

**Tham số:**

| Tham số | Kiểu | Mô tả |
|---------|------|--------|
| `$type` | `string` | Cùng type với `@startReactive` tương ứng |
| `$registryID` | `string` (PHP expression) | Cùng ID với `@startReactive` tương ứng |

---

## 2. Các trường hợp sử dụng (Compiler Output)

### 2.1. Block directives (`if`, `foreach`, `for`, `while`, `switch`)

`@startReactive` đặt **trước** directive mở, `@endReactive` đặt **sau** directive đóng, **trên cùng dòng**:

```blade
@startReactive('if', 'rc-' . $__VIEW_ID__ . '-if-1', ['count']) @if($count > 5)
    <p>Count lớn hơn 5</p>
@else
    <p>Count nhỏ hơn hoặc bằng 5</p>
@endif @endReactive('if', 'rc-' . $__VIEW_ID__ . '-if-1')
```

```blade
@startReactive('foreach', 'rc-' . $__VIEW_ID__ . '-foreach-2', ['todos']) @foreach($todos as $todo)
    <li>{{ $todo['title'] }}</li>
@endforeach @endReactive('foreach', 'rc-' . $__VIEW_ID__ . '-foreach-2')
```

```blade
@startReactive('for', 'rc-' . $__VIEW_ID__ . '-for-3', ['inventory']) @for($i = 0; $i < count($inventory); $i++)
    <p>{{ $inventory[$i]['name'] }}</p>
@endfor @endReactive('for', 'rc-' . $__VIEW_ID__ . '-for-3')
```

```blade
@startReactive('switch', 'rc-' . $__VIEW_ID__ . '-switch-4', ['status']) @switch($status)
    @case('active')
        <span>Active</span>
        @break
    @default
        <span>Unknown</span>
@endswitch @endReactive('switch', 'rc-' . $__VIEW_ID__ . '-switch-4')
```

### 2.2. Output (`{{ }}` và `{!! !!}`)

Wrapping **inline** (trên cùng dòng, bao quanh expression):

```blade
<h1>@startReactive('output', 'rc-' . $__VIEW_ID__ . '-output-1', ['title'], ["type" => 'output', "escapeHTML" => true]) {{ $title }} @endReactive('output', 'rc-' . $__VIEW_ID__ . '-output-1')</h1>
```

Unescaped output:

```blade
<div>@startReactive('output', 'rc-' . $__VIEW_ID__ . '-output-2', ['htmlContent'], ["type" => 'output', "escapeHTML" => false]) {!! $htmlContent !!} @endReactive('output', 'rc-' . $__VIEW_ID__ . '-output-2')</div>
```

**Options cho output type:**

```php
["type" => 'output', "escapeHTML" => true]   // cho {{ }}
["type" => 'output', "escapeHTML" => false]  // cho {!! !!}
```

### 2.3. Include

Wrapping **inline** (cùng dòng):

```blade
@startReactive('include', 'rc-' . $__VIEW_ID__ . '-include-5', ['products']) @include('partials.product-list', ['data' => $products]) @endReactive('include', 'rc-' . $__VIEW_ID__ . '-include-5')
```

---

## 3. Triển khai Laravel

### 3.1. Đăng ký Blade Directives

Trong `AppServiceProvider` hoặc `OneViewServiceProvider`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class OneViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::directive('startReactive', function (string $expression) {
            return "<?php echo \$__helper->startReactive({$expression}); ?>";
        });

        Blade::directive('endReactive', function (string $expression) {
            return "<?php echo \$__helper->endReactive({$expression}); ?>";
        });
    }
}
```

### 3.2. ReactiveHelper Class

```php
<?php

namespace App\OneView;

class ReactiveHelper
{
    /**
     * Mở một vùng reactive.
     *
     * @param  string  $type        Loại directive: 'if', 'foreach', 'for', 'while', 'switch', 'output', 'include', 'isset', 'empty'
     * @param  string  $registryID  ID duy nhất của vùng reactive, format: 'rc-{viewID}-{type}-{N}'
     * @param  array   $stateKeys   Danh sách state variables mà vùng này phụ thuộc
     * @param  array   $options     (Optional) Tùy chọn bổ sung — hiện dùng cho type 'output'
     * @return string  HTML marker output
     */
    public function startReactive(
        string $type,
        string $registryID,
        array $stateKeys,
        array $options = []
    ): string {
        $keysJson = json_encode($stateKeys);
        $optionsAttr = '';

        if (!empty($options)) {
            $optionsAttr = ' data-options=\'' . json_encode($options) . '\'';
        }

        return "<!--reactive:{$registryID}:{$type}:{$keysJson}{$optionsAttr}-->";
    }

    /**
     * Đóng một vùng reactive.
     *
     * @param  string  $type        Loại directive (cùng với startReactive tương ứng)
     * @param  string  $registryID  ID duy nhất (cùng với startReactive tương ứng)
     * @return string  HTML marker output
     */
    public function endReactive(string $type, string $registryID): string
    {
        return "<!--/reactive:{$registryID}-->";
    }
}
```

### 3.3. Inject `$__helper` vào View

Trong `OneViewServiceProvider` hoặc `ViewServiceProvider`:

```php
<?php

namespace App\Providers;

use App\OneView\ReactiveHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class OneViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReactiveHelper::class);
    }

    public function boot(): void
    {
        // Inject $__helper vào tất cả views
        View::composer('*', function ($view) {
            $view->with('__helper', app(ReactiveHelper::class));
        });

        // Đăng ký Blade directives
        Blade::directive('startReactive', function (string $expression) {
            return "<?php echo \$__helper->startReactive({$expression}); ?>";
        });

        Blade::directive('endReactive', function (string $expression) {
            return "<?php echo \$__helper->endReactive({$expression}); ?>";
        });
    }
}
```

---

## 4. HTML Output mong đợi

### Ví dụ Input (Blade compiled):

```blade
<h1>@startReactive('output', 'rc-' . $__VIEW_ID__ . '-output-1', ['title'], ["type" => 'output', "escapeHTML" => true]) {{ $title }} @endReactive('output', 'rc-' . $__VIEW_ID__ . '-output-1')</h1>

@startReactive('foreach', 'rc-' . $__VIEW_ID__ . '-foreach-2', ['todos']) @foreach($todos as $todo)
    <li>{{ $todo['name'] }}</li>
@endforeach @endReactive('foreach', 'rc-' . $__VIEW_ID__ . '-foreach-2')
```

### Ví dụ Output (HTML rendered, giả sử `$__VIEW_ID__ = 'v1'`):

```html
<h1><!--reactive:rc-v1-output-1:output:["title"] data-options='{"type":"output","escapeHTML":true}'-->Hello World<!--/reactive:rc-v1-output-1--></h1>

<!--reactive:rc-v1-foreach-2:foreach:["todos"]-->
    <li>Buy groceries</li>
    <li>Clean house</li>
<!--/reactive:rc-v1-foreach-2-->
```

---

## 5. Format HTML Comment Marker

### Start marker:

```
<!--reactive:{registryID}:{type}:{stateKeysJSON}{optionsAttr}-->
```

Trong đó:
- `{registryID}` — VD: `rc-v1-foreach-2`
- `{type}` — VD: `foreach`
- `{stateKeysJSON}` — VD: `["todos"]` hoặc `["count","title"]`
- `{optionsAttr}` — VD: ` data-options='{"type":"output","escapeHTML":true}'` (có space đầu, chỉ khi có options)

### End marker:

```
<!--/reactive:{registryID}-->
```

---

## 6. Registry ID Format

ID có dạng PHP string concatenation expression:

```php
'rc-' . $__VIEW_ID__ . '-{type}-{N}'
```

Khi PHP evaluate, kết quả sẽ là:

```
rc-{viewID}-{type}-{N}
```

Ví dụ với `$__VIEW_ID__ = 'dashboard'`:
- `rc-dashboard-if-1`
- `rc-dashboard-foreach-2`
- `rc-dashboard-output-3`

**Lưu ý**: ID này đồng bộ giữa blade output (PHP) và JS output — JS runtime dùng cùng format ID để tìm và hydrate vùng reactive tương ứng.

---

## 7. Bảng tham chiếu Options

| Type | Options | Mô tả |
|------|---------|--------|
| `output` | `["type" => 'output', "escapeHTML" => true]` | Echo `{{ }}` — HTML escaped |
| `output` | `["type" => 'output', "escapeHTML" => false]` | Echo `{!! !!}` — Raw HTML |
| `if` | *(không có)* | — |
| `foreach` | *(không có)* | — |
| `for` | *(không có)* | — |
| `while` | *(không có)* | — |
| `switch` | *(không có)* | — |
| `include` | *(không có)* | — |
| `isset` | *(không có)* | — |
| `empty` | *(không có)* | — |

---

## 8. Lưu ý quan trọng

1. **`$__VIEW_ID__`** phải được inject vào view trước khi render. Đây là unique identifier cho mỗi view instance, dùng để scope reactive IDs.

2. **Directive parameters là PHP expressions** — `$registryID` truyền vào là biểu thức PHP nối chuỗi (`'rc-' . $__VIEW_ID__ . '-if-1'`), Blade directive sẽ evaluating expression này khi render.

3. **Vùng reactive lồng nhau (nested)** — Các directive reactive có thể lồng nhau. Mỗi vùng có ID riêng. JS runtime sẽ xây dựng dependency tree từ cấu trúc HTML comment markers.

4. **Directives KHÔNG được wrap:** `@bind`, `@attr`, `@class`, `@style`, `@checked`, `@selected`, `@show`, `@hide` — đây là inline attribute directives, được JS runtime xử lý riêng.

5. **Directives trong HTML tag attributes** — Block directives (`@if`, `@for`,...) nằm trong HTML tag attributes không được wrap reactive (vì không thể chèn HTML comment bên trong HTML tag).

---

## 9. Inject `$__VIEW_ID__`

`$__VIEW_ID__` cần có giá trị duy nhất cho mỗi view instance. Có thể tạo tự động:

```php
// Trong ViewComposer hoặc Middleware
View::composer('*', function ($view) {
    if (!isset($view->getData()['__VIEW_ID__'])) {
        // Dùng tên view + short hash để tạo unique ID
        $viewName = $view->name();
        $view->with('__VIEW_ID__', str_replace('.', '-', $viewName));
    }
});
```

Hoặc nếu cùng view render nhiều lần trên 1 page (component):

```php
$view->with('__VIEW_ID__', str_replace('.', '-', $viewName) . '-' . uniqid());
```
