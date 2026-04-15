# Hướng Dẫn Sử Dụng Macro và AttributeMethods

## Tổng Quan

Hệ thống Macro cho phép bạn tạo các method động và xử lý attribute một cách linh hoạt trong Service. Có hai thành phần chính:

1. **OneMacro**: Trait quản lý macro methods
2. **AttributeMethods**: Trait quản lý attribute accessors

## Cài Đặt

Service mặc định đã include cả hai traits:

```php
use One\Core\Services\Service;

class MyService extends Service
{
    // Đã có sẵn OneMacro và AttributeMethods
}
```

## 1. OneMacro - Macro Methods

### Khái Niệm

Macro cho phép bạn đăng ký các callback để xử lý khi gọi method không tồn tại. Có nhiều loại macro:

- **MACRO_TYPE_NAME**: Macro theo tên method chính xác
- **MACRO_TYPE_COMPARE**: Macro so sánh (bao gồm NAME và PATTERN)
- **MACRO_TYPE_PATTERN**: Macro theo regex pattern
- **MACRO_TYPE_ATTRIBUTE**: Macro cho attribute accessors
- **MACRO_TYPE_EQUAL**: Macro so sánh bằng

### Các Method Chính

#### `addMacro($subject, $callback, $type)`

Đăng ký một macro mới.

**Tham số:**
- `$subject`: Tên method hoặc pattern
- `$callback`: Closure hoặc callable
- `$type`: Loại macro (mặc định: `MACRO_TYPE_COMPARE`)

**Ví dụ:**

```php
class UserService extends Service
{
    public function initService()
    {
        // Macro NAME type - chính xác theo tên
        $this->addMacro('getUserById', function($id) {
            return User::find($id);
        }, self::MACRO_TYPE_NAME);

        // Macro COMPARE type - tự động detect NAME hoặc PATTERN
        $this->addMacro('getUserByEmail', function($email) {
            return User::where('email', $email)->first();
        });

        // Macro PATTERN - sử dụng regex với capture groups
        $this->addMacro('/^getUserBy(.+)$/', function($field, $value) {
            // $field = matches[1] (từ regex capture group)
            // $value = argument[0] (từ method call)
            $fieldName = strtolower($field);
            return User::where($fieldName, $value)->first();
        }, self::MACRO_TYPE_PATTERN);
    }
}

// Sử dụng
$service = new UserService();
$user = $service->getUserById(1); // Gọi macro
$user = $service->getUserByEmail('test@example.com'); // Gọi macro
$user = $service->getUserByName('John'); // Gọi macro pattern
```

#### `addMethodMacro($subject, $callback)`

Shorthand cho `addMacro()` với type `MACRO_TYPE_COMPARE`.

```php
$this->addMethodMacro('calculateTotal', function($items) {
    return array_sum(array_column($items, 'price'));
});

// Sử dụng
$total = $service->calculateTotal($items);
```

#### `addAttributeMacro($subject, $callback)`

Đăng ký macro cho attribute accessors (sẽ được giải thích ở phần AttributeMethods).

### Auto-Init Macros

Bạn có thể tạo method tự động được gọi để setup macros. Method phải có pattern: `setup*Macro()` hoặc `set*Macro()`.

```php
class ProductService extends Service
{
    // Tự động được gọi trong init()
    protected function setupProductMacro()
    {
        $this->addMethodMacro('getProduct', function($id) {
            return Product::find($id);
        });
    }

    protected function setupCategoryMacro()
    {
        $this->addMethodMacro('getCategory', function($id) {
            return Category::find($id);
        });
    }
}
```

### Tham Số Callback - Logic Matches

**Quan trọng:** Tham số được truyền vào callback phụ thuộc vào loại macro:

#### 1. So Sánh `===` (NAME, EQUAL, COMPARE, ATTRIBUTE)

**KHÔNG có matches** - Chỉ truyền arguments từ method call:

```php
// So sánh ===
$this->addMacro('getUserById', function($id) {
    // Chỉ nhận $id từ method call
    // KHÔNG có matches vì đã biết method name rồi
}, self::MACRO_TYPE_NAME);

// Sử dụng
$service->getUserById(123); // $id = 123
```

#### 2. Pattern CÓ Capture Groups `()`

**CÓ matches** - Capture groups được truyền trước arguments:

```php
// Pattern: /^(set|get)(.*)Attribute$/
$this->addMacro('/^(set|get)(.*)Attribute$/', function($action, $attributeName, $value = null) {
    // $action = "set" hoặc "get" (từ matches[1])
    // $attributeName = "FullName" (từ matches[2])
    // $value = argument[0] (từ method call, nếu có)
    
    if ($action === 'get') {
        return $this->attributes[$attributeName] ?? null;
    } else {
        $this->attributes[$attributeName] = $value;
        return $this;
    }
}, self::MACRO_TYPE_PATTERN);

// Sử dụng
$service->getFullNameAttribute(); // $action='get', $attributeName='FullName'
$service->setFullNameAttribute('John'); // $action='set', $attributeName='FullName', $value='John'
```

#### 3. Pattern KHÔNG CÓ Capture Groups

**KHÔNG có matches** - Chỉ truyền arguments:

```php
// Pattern: /^abc.*$/ (không có capture groups)
$this->addMacro('/^abc.*$/', function($param) {
    // Không có matches, chỉ có arguments từ method call
    // Method "abc123('test')" → $param = 'test'
});
```

### Return Values Đặc Biệt

Macro có thể return các giá trị đặc biệt để điều khiển flow:

```php
const NO_MACRO_VALUE_RETURN = '<--!!!no-macro-value-return!!!-->';
const NEXT_MACRO = '<--!!!next-macro-value-return!!!-->';
const STOP_MACRO = '<--!!!stop-macro-value-return!!!-->';
const NO_MACRO_EXIST = '<--!!!no-macro-exist!!!-->';
```

**Ví dụ:**

```php
$this->addMethodMacro('processData', function($data) {
    if (empty($data)) {
        return self::NO_MACRO_VALUE_RETURN; // Không return giá trị, tiếp tục macro khác
    }
    return $this->doProcess($data);
});

$this->addMethodMacro('processData', function($data) {
    // Macro thứ 2 sẽ được gọi nếu macro đầu return NO_MACRO_VALUE_RETURN
    return $this->fallbackProcess($data);
});
```

## 2. AttributeMethods - Attribute Accessors

### Khái Niệm

AttributeMethods cho phép bạn xử lý dynamic attributes giống Laravel Model accessors/mutators.

### Tự Động Khởi Tạo

Khi Service được khởi tạo, `initAttributeMethods()` tự động được gọi và đăng ký các macro cho:

- `__getAttribute__`: Xử lý `$service->attribute`
- `__setAttribute__`: Xử lý `$service->attribute = value`
- `__issetAttribute__`: Xử lý `isset($service->attribute)`
- `__unsetAttribute__`: Xử lý `unset($service->attribute)`

### Sử Dụng Cơ Bản

```php
class ConfigService extends Service
{
    // Attributes được lưu trong $attributes array
    // Không cần khai báo property
}

$service = new ConfigService();

// Set attribute
$service->site_name = 'My Site';
$service->api_key = 'secret123';

// Get attribute
echo $service->site_name; // 'My Site'
echo $service->api_key; // 'secret123'

// Check isset
if (isset($service->site_name)) {
    // ...
}

// Unset
unset($service->api_key);
```

### Custom Accessors

Bạn có thể tạo custom accessor methods:

```php
class UserService extends Service
{
    // Getter accessor
    public function getFullNameAttribute()
    {
        return $this->attributes['first_name'] . ' ' . $this->attributes['last_name'];
    }

    // Setter accessor
    public function setFullNameAttribute($value)
    {
        $parts = explode(' ', $value);
        $this->attributes['first_name'] = $parts[0] ?? '';
        $this->attributes['last_name'] = $parts[1] ?? '';
    }

    // Isset accessor
    public function issetFullNameAttribute()
    {
        return isset($this->attributes['first_name']) && isset($this->attributes['last_name']);
    }

    // Unset accessor
    public function unsetFullNameAttribute()
    {
        unset($this->attributes['first_name']);
        unset($this->attributes['last_name']);
    }
}

$service = new UserService();

// Sử dụng
$service->full_name = 'John Doe';
echo $service->full_name; // 'John Doe'
echo $service->first_name; // 'John'
echo $service->last_name; // 'Doe'

if (isset($service->full_name)) {
    // ...
}

unset($service->full_name);
```

### Custom Attribute Macros

Bạn có thể đăng ký custom attribute macros:

```php
class CacheService extends Service
{
    public function initService()
    {
        // Custom getter cho cache
        $this->addAttributeMacro('cache', function($key) {
            return Cache::get($key);
        });

        // Custom setter cho cache
        $this->addAttributeMacro('cache', function($key, $value) {
            return Cache::put($key, $value);
        });
    }
}

$service = new CacheService();
$service->cache['user_1'] = 'User Data'; // Gọi setter macro
$data = $service->cache['user_1']; // Gọi getter macro
```

## 3. Ví Dụ Tổng Hợp

### Ví Dụ 1: Service với Macro và Attributes

```php
class ShoppingCartService extends Service
{
    protected $context = 'web';
    protected $module = 'cart';

    public function initService()
    {
        // Macro: Thêm sản phẩm vào giỏ
        $this->addMethodMacro('addItem', function($productId, $quantity = 1) {
            $items = $this->items ?? [];
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'added_at' => now(),
            ];
            $this->items = $items;
            return $this;
        });

        // Macro: Tính tổng tiền
        $this->addMethodMacro('calculateTotal', function() {
            $total = 0;
            foreach ($this->items ?? [] as $item) {
                $product = Product::find($item['product_id']);
                $total += $product->price * $item['quantity'];
            }
            return $total;
        });

        // Macro Pattern: Lấy item theo điều kiện
        $this->addMacro('/^getItemBy(.+)$/', function($field, $value) {
            // $field = matches[1] (từ regex capture group)
            // $value = argument[0] (từ method call)
            $fieldName = strtolower($field);
            
            foreach ($this->items ?? [] as $item) {
                if (isset($item[$fieldName]) && $item[$fieldName] == $value) {
                    return $item;
                }
            }
            return null;
        }, self::MACRO_TYPE_PATTERN);
    }

    // Attribute accessor: Tổng số items
    public function getItemCountAttribute()
    {
        return count($this->items ?? []);
    }

    // Attribute accessor: Trạng thái giỏ hàng
    public function getIsEmptyAttribute()
    {
        return empty($this->items);
    }
}

// Sử dụng
$cart = new ShoppingCartService();

// Sử dụng macro
$cart->addItem(1, 2);
$cart->addItem(2, 1);
$total = $cart->calculateTotal();

// Sử dụng macro pattern
$item = $cart->getItemByProductId(1);

// Sử dụng attributes
echo $cart->item_count; // 2
echo $cart->is_empty ? 'Empty' : 'Has items'; // 'Has items'

// Set attribute trực tiếp
$cart->items = []; // Clear cart
```

### Ví Dụ 2: Service với Custom Attribute Logic

```php
class SettingsService extends Service
{
    public function initService()
    {
        // Load settings từ database
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $settings = DB::table('settings')->pluck('value', 'key')->toArray();
        foreach ($settings as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    // Custom getter: Parse JSON settings
    public function getJsonSettingsAttribute()
    {
        $json = $this->attributes['json_settings'] ?? '{}';
        return json_decode($json, true);
    }

    // Custom setter: Auto-save to database
    public function setJsonSettingsAttribute($value)
    {
        $json = json_encode($value);
        $this->attributes['json_settings'] = $json;
        
        // Auto-save
        DB::table('settings')->updateOrInsert(
            ['key' => 'json_settings'],
            ['value' => $json, 'updated_at' => now()]
        );
    }

    // Macro: Get setting với default value
    $this->addMethodMacro('getSetting', function($key, $default = null) {
        return $this->attributes[$key] ?? $default;
    });

    // Macro: Set setting và save
    $this->addMethodMacro('setSetting', function($key, $value) {
        $this->attributes[$key] = $value;
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
        return $this;
    });
}

// Sử dụng
$settings = new SettingsService();

// Sử dụng attribute
$settings->site_name = 'My Site';
$settings->json_settings = ['theme' => 'dark', 'lang' => 'vi'];

// Sử dụng macro
$theme = $settings->getSetting('theme', 'light');
$settings->setSetting('api_key', 'secret123');
```

## 4. Best Practices

### 1. Sử Dụng Auto-Init Methods

Thay vì gọi `addMacro()` trong `initService()`, tạo method `setup*Macro()`:

```php
// ✅ Tốt
protected function setupUserMacro()
{
    $this->addMethodMacro('getUser', function($id) {
        return User::find($id);
    });
}

// ❌ Không tốt
public function initService()
{
    $this->addMethodMacro('getUser', function($id) {
        return User::find($id);
    });
}
```

### 2. Đặt Tên Macro Rõ Ràng

```php
// ✅ Tốt
$this->addMethodMacro('getUserById', function($id) { ... });
$this->addMethodMacro('getUserByEmail', function($email) { ... });

// ❌ Không tốt
$this->addMethodMacro('get', function($id) { ... });
```

### 3. Sử Dụng Pattern Macros Cho Dynamic Methods

```php
// ✅ Tốt - Dynamic methods với capture groups
$this->addMacro('/^get(.+)ById$/', function($model, $id) {
    // $model = matches[1] (từ regex capture group)
    // $id = argument[0] (từ method call)
    $modelClass = "App\\Models\\{$model}";
    return $modelClass::find($id);
}, self::MACRO_TYPE_PATTERN);

// Sử dụng: $service->getUserById(1), $service->getProductById(2)
```

### 4. Validate Input Trong Macros

```php
$this->addMethodMacro('getUserById', function($id) {
    if (!is_numeric($id) || $id <= 0) {
        throw new \InvalidArgumentException('Invalid user ID');
    }
    return User::find($id);
});
```

### 5. Sử Dụng Attributes Cho Configuration

```php
class ApiService extends Service
{
    public function initService()
    {
        // Load config từ file hoặc database
        $this->api_url = config('api.url');
        $this->api_key = config('api.key');
        $this->timeout = config('api.timeout', 30);
    }

    public function makeRequest($endpoint)
    {
        // Sử dụng attributes
        return Http::timeout($this->timeout)
            ->withHeaders(['Authorization' => $this->api_key])
            ->get($this->api_url . $endpoint);
    }
}
```

## 5. Lưu Ý Quan Trọng

### Thứ Tự Ưu Tiên Khi Gọi Method

Khi gọi một method không tồn tại, Service sẽ xử lý theo thứ tự:

1. Event methods
2. Magic methods (`_funcExists`)
3. Event listeners (`on*`)
4. Event emitters (`emit*`)
5. Macro NAME type
6. Macro COMPARE type

### Tham Số Callback - Tóm Tắt

| Loại Macro | Có Matches? | Tham Số Callback |
|------------|-------------|------------------|
| **NAME** (so sánh `===`) | ❌ Không | Chỉ arguments từ method call |
| **EQUAL** (so sánh `===`) | ❌ Không | Chỉ arguments từ method call |
| **COMPARE** (so sánh `===`) | ❌ Không | Chỉ arguments từ method call |
| **ATTRIBUTE** (so sánh `===`) | ❌ Không | Chỉ arguments từ method call |
| **PATTERN** có `()` | ✅ Có | Capture groups + arguments |
| **PATTERN** không có `()` | ❌ Không | Chỉ arguments từ method call |

**Ví dụ:**

```php
// So sánh === - KHÔNG có matches
$this->addMacro('getUser', function($id) {
    // Chỉ nhận $id từ method call
});

// Pattern CÓ capture groups - CÓ matches
$this->addMacro('/^get(\w+)ById$/', function($model, $id) {
    // $model = matches[1]
    // $id = argument[0]
});

// Pattern KHÔNG CÓ capture groups - KHÔNG có matches
$this->addMacro('/^abc.*$/', function($param) {
    // Chỉ nhận $param từ method call
});
```

### Attribute Access Order

Khi truy cập attribute:

1. Custom attribute macro (nếu có)
2. `__getAttribute__` macro (từ AttributeMethods)
3. Custom accessor method (`get*Attribute`)
4. `$attributes` array

### Return Values

- `NO_MACRO_VALUE_RETURN`: Macro không return giá trị, tiếp tục macro khác
- `NEXT_MACRO`: Bỏ qua macro hiện tại, chuyển sang macro tiếp theo
- `STOP_MACRO`: Dừng xử lý macro
- `NO_MACRO_EXIST`: Không tìm thấy macro

## 6. Troubleshooting

### Macro Không Được Gọi

**Vấn đề:** Macro đã đăng ký nhưng không được gọi.

**Giải pháp:**
- Kiểm tra method name có đúng không
- Kiểm tra macro type có đúng không
- Đảm bảo `initService()` hoặc `setup*Macro()` được gọi

### Attribute Không Hoạt Động

**Vấn đề:** Attribute không được set/get.

**Giải pháp:**
- Kiểm tra `initAttributeMethods()` đã được gọi chưa
- Kiểm tra `OneMacro` trait đã được use chưa
- Kiểm tra accessor method name có đúng pattern không

### Conflict Với Method Thật

**Vấn đề:** Macro không được gọi vì method thật đã tồn tại.

**Giải pháp:**
- Macro chỉ được gọi khi method không tồn tại
- Đổi tên macro hoặc đổi tên method thật

## Kết Luận

Macro và AttributeMethods cung cấp một cách linh hoạt để mở rộng Service với dynamic methods và attributes. Sử dụng đúng cách sẽ giúp code gọn gàng và dễ bảo trì hơn.

