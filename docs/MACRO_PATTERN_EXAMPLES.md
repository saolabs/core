# Ví Dụ Sử Dụng Pattern Macro với Matches

## Tổng Quan

Khi sử dụng Pattern Macro với regex:
- **Có capture groups** `()`: Các capture groups sẽ được truyền vào callback
- **Không có capture groups**: Chỉ truyền arguments từ method call, không truyền matches

**Lưu ý:** Method name gốc KHÔNG được truyền vào vì bạn đã biết từ pattern rồi.

## Cú Pháp

### Pattern CÓ Capture Groups

```php
// Pattern: /^(set|get)(.*)Attribute$/
// Method call: $service->getFullNameAttribute()
// Callback nhận: function($action, $attributeName) {
//     $action = "get" (từ matches[1])
//     $attributeName = "FullName" (từ matches[2])
// }

$this->addMacro('/^(set|get)(.*)Attribute$/', function($action, $attributeName) {
    // Có capture groups → nhận matches[1], matches[2], ...
}, self::MACRO_TYPE_PATTERN);
```

### Pattern KHÔNG CÓ Capture Groups

```php
// Pattern: /^abc.*$/ hoặc /^abc[A-z0-9]+$/
// Method call: $service->abc123('param')
// Callback nhận: function($param) {
//     $param = 'param' (từ argument[0])
//     // KHÔNG có matches vì pattern không có capture groups
// }

$this->addMacro('/^abc.*$/', function() {
    // Không có capture groups → chỉ nhận arguments từ method call
    $args = func_get_args();
}, self::MACRO_TYPE_PATTERN);
```

## Ví Dụ 1: Pattern `/^(set|get)(.*)Attribute$/`

### Đăng Ký Macro

```php
class UserService extends Service
{
    public function initService()
    {
        // Pattern: /^(set|get)(.*)Attribute$/
        // matches[0] = full match (ví dụ: "getFullNameAttribute")
        // matches[1] = "set" hoặc "get"
        // matches[2] = phần còn lại (ví dụ: "FullName")
        
        $this->addMacro('/^(set|get)(.*)Attribute$/', function($action, $attributeName) {
            // $action = "set" hoặc "get"
            // $attributeName = "FullName", "Email", etc.
            
            $method = strtolower($action) . ucfirst($attributeName) . 'Attribute';
            
            if (method_exists($this, $method)) {
                return $this->{$method}();
            }
            
            // Fallback: xử lý với $attributes array
            $key = strtolower(preg_replace('/([A-Z])/', '_$1', $attributeName));
            $key = ltrim($key, '_');
            
            if ($action === 'get') {
                return $this->attributes[$key] ?? null;
            } else {
                // set - cần value từ arguments
                $value = func_get_arg(2); // Tham số thứ 3 (sau $action và $attributeName)
                $this->attributes[$key] = $value;
                return $this;
            }
        }, self::MACRO_TYPE_PATTERN);
    }
}
```

### Sử Dụng

```php
$service = new UserService();

// Get attribute
$service->fullName = 'John Doe';
$name = $service->getFullNameAttribute(); // "John Doe"

// Set attribute
$service->setFullNameAttribute('Jane Doe');
echo $service->fullName; // "Jane Doe"

// Với custom accessor
class UserService extends Service
{
    public function getFullNameAttribute()
    {
        return ($this->attributes['first_name'] ?? '') . ' ' . ($this->attributes['last_name'] ?? '');
    }
    
    public function setFullNameAttribute($value)
    {
        $parts = explode(' ', $value);
        $this->attributes['first_name'] = $parts[0] ?? '';
        $this->attributes['last_name'] = $parts[1] ?? '';
    }
}

$service = new UserService();
$service->setFullNameAttribute('John Doe');
echo $service->getFullNameAttribute(); // "John Doe"
```

## Ví Dụ 2: Pattern Phức Tạp Hơn

```php
class ApiService extends Service
{
    public function initService()
    {
        // Pattern: /^call(\w+)Api$/
        // matches[1] = tên API (ví dụ: "User", "Product")
        
        $this->addMacro('/^call(\w+)Api$/', function($apiName) {
            $endpoint = strtolower($apiName);
            return $this->makeRequest("/api/{$endpoint}");
        }, self::MACRO_TYPE_PATTERN);
        
        // Pattern: /^get(\w+)By(\w+)$/
        // matches[1] = model name (ví dụ: "User")
        // matches[2] = field name (ví dụ: "Email")
        
        $this->addMacro('/^get(\w+)By(\w+)$/', function($modelName, $fieldName) {
            $modelClass = "App\\Models\\{$modelName}";
            $field = strtolower($fieldName);
            $value = func_get_arg(2); // Tham số từ method call
            
            if (class_exists($modelClass)) {
                return $modelClass::where($field, $value)->first();
            }
            return null;
        }, self::MACRO_TYPE_PATTERN);
    }
    
    protected function makeRequest($endpoint)
    {
        // Implementation
        return "Request to {$endpoint}";
    }
}

// Sử dụng
$service = new ApiService();

// Gọi API
$result = $service->callUserApi(); // "Request to /api/user"
$result = $service->callProductApi(); // "Request to /api/product"

// Get model by field
$user = $service->getUserByEmail('test@example.com');
// Tương đương: User::where('email', 'test@example.com')->first()
```

## Ví Dụ 3: Pattern với Nhiều Capture Groups

```php
class RouteService extends Service
{
    public function initService()
    {
        // Pattern: /^route(\w+)To(\w+)$/
        // matches[1] = từ route
        // matches[2] = đến route
        
        $this->addMacro('/^route(\w+)To(\w+)$/', function($from, $to) {
            return [
                'from' => strtolower($from),
                'to' => strtolower($to),
                'path' => "/{$from}/{$to}",
            ];
        }, self::MACRO_TYPE_PATTERN);
        
        // Pattern: /^(\w+)Action(\d+)$/
        // matches[1] = action name
        // matches[2] = ID
        
        $this->addMacro('/^(\w+)Action(\d+)$/', function($action, $id) {
            $method = strtolower($action) . 'Action';
            if (method_exists($this, $method)) {
                return $this->{$method}($id);
            }
            return null;
        }, self::MACRO_TYPE_PATTERN);
    }
    
    protected function deleteAction($id)
    {
        return "Deleted item {$id}";
    }
}

// Sử dụng
$service = new RouteService();

$route = $service->routeHomeToAbout();
// ['from' => 'home', 'to' => 'about', 'path' => '/home/about']

$result = $service->deleteAction(123);
// "Deleted item 123"
```

## Ví Dụ 4: Kết Hợp với Arguments Từ Method Call

```php
class DataService extends Service
{
    public function initService()
    {
        // Pattern: /^save(\w+)Data$/
        // matches[1] = data type
        // Arguments từ method call sẽ được thêm sau matches
        
        $this->addMacro('/^save(\w+)Data$/', function($dataType, $data) {
            // $dataType = matches[1] (từ regex)
            // $data = argument[0] (từ method call)
            
            $table = strtolower($dataType) . '_data';
            return DB::table($table)->insert($data);
        }, self::MACRO_TYPE_PATTERN);
        
        // Pattern: /^update(\w+)By(\w+)$/
        // matches[1] = model name
        // matches[2] = field name
        // Arguments: value, data
        
        $this->addMacro('/^update(\w+)By(\w+)$/', function($modelName, $fieldName, $value, $data) {
            // $modelName = matches[1]
            // $fieldName = matches[2]
            // $value = argument[0]
            // $data = argument[1]
            
            $modelClass = "App\\Models\\{$modelName}";
            $field = strtolower($fieldName);
            
            if (class_exists($modelClass)) {
                return $modelClass::where($field, $value)->update($data);
            }
            return false;
        }, self::MACRO_TYPE_PATTERN);
    }
}

// Sử dụng
$service = new DataService();

// Save data
$service->saveUserData(['name' => 'John', 'email' => 'john@example.com']);

// Update by field
$service->updateUserByEmail('john@example.com', ['name' => 'Jane']);
// Tương đương: User::where('email', 'john@example.com')->update(['name' => 'Jane'])
```

## Lưu Ý Quan Trọng

### 1. Thứ Tự Tham Số

Tham số được truyền vào callback theo thứ tự:
1. **Capture groups từ regex** (chỉ khi pattern CÓ capture groups)
2. **Arguments từ method call** (argument[0], argument[1], ...)
3. **KHÔNG có method name** - vì đã biết rồi!

```php
// Pattern: /^(\w+)(\d+)$/
// Method call: $service->test123('extra')
// Callback nhận: function($word, $number, $extra) {
//     $word = 'test' (matches[1])
//     $number = '123' (matches[2])
//     $extra = 'extra' (argument[0])
//     // KHÔNG có "test123" vì đã biết từ $word và $number rồi
// }
```

### 2. So Sánh === Không Có Matches

Khi so sánh bằng `===` (MACRO_TYPE_NAME, MACRO_TYPE_EQUAL, MACRO_TYPE_COMPARE, MACRO_TYPE_ATTRIBUTE), đã biết method name rồi nên **KHÔNG truyền matches**:

```php
// So sánh ===
$this->addMacro('getUserById', function($id) {
    // Chỉ nhận arguments từ method call
    // $id = argument[0]
    // KHÔNG có matches vì đã biết method name là "getUserById" rồi
}, self::MACRO_TYPE_NAME);

// Sử dụng
$service->getUserById(123); // $id = 123
```

### 3. Pattern Macro - Chỉ Có Matches Khi Có Capture Groups

**Pattern CÓ capture groups** → Truyền matches:

```php
// Pattern: /^(set|get)(.*)Attribute$/
$this->addMacro('/^(set|get)(.*)Attribute$/', function($action, $attributeName) {
    // $action = "set" hoặc "get" (từ matches[1])
    // $attributeName = "FullName" (từ matches[2])
    // Sau đó là arguments từ method call (nếu có)
});
```

**Pattern KHÔNG CÓ capture groups** → Không truyền matches:

```php
// Pattern: /^abc.*$/
$this->addMacro('/^abc.*$/', function() {
    // Không có matches, chỉ có arguments từ method call
    // Method "abc123('param')" → chỉ nhận 'param'
});
```

### 2. Pattern Có Capture Groups vs Không Có

**Pattern CÓ capture groups** - Truyền matches vào callback:

```php
// Pattern: /^abc(.*)$/ hoặc /^abc([^$]*)/ hoặc /^(abc|def)/
$this->addMacro('/^abc(.*)$/', function($rest) {
    // $rest = matches[1] (phần sau "abc")
    // Ví dụ: method "abc123" → $rest = "123"
});
```

**Pattern KHÔNG CÓ capture groups** - Chỉ truyền arguments:

```php
// Pattern: /^abc.*$/ hoặc /^abc[A-z0-9]+$/
$this->addMacro('/^abc.*$/', function() {
    // Không có matches, chỉ có arguments từ method call
    $args = func_get_args();
    // Ví dụ: method "abc123('param')" → $args = ['param']
});
```

### 3. Khi Nào Cần Capture Groups?

**CẦN capture groups** khi bạn cần lấy giá trị từ method name:

```php
// ✅ Cần: /^get(\w+)ById$/ → lấy model name
$this->addMacro('/^get(\w+)ById$/', function($modelName) {
    // $modelName = "User", "Product", etc.
});

// ✅ Cần: /^(set|get)(.*)Attribute$/ → lấy action và attribute
$this->addMacro('/^(set|get)(.*)Attribute$/', function($action, $attr) {
    // $action = "set" hoặc "get"
    // $attr = "FullName", "Email", etc.
});
```

**KHÔNG CẦN capture groups** khi chỉ cần match pattern:

```php
// ✅ Không cần: /^abc.*$/ → chỉ cần biết bắt đầu bằng "abc"
$this->addMacro('/^abc.*$/', function() {
    // Đã biết method bắt đầu bằng "abc" rồi, không cần lấy phần sau
});

// ✅ Không cần: /^api[A-z0-9]+$/ → chỉ cần match pattern
$this->addMacro('/^api[A-z0-9]+$/', function() {
    // Đã biết method match pattern rồi
});
```

### 4. Full Match Không Được Truyền

`matches[0]` (full match) không được truyền vào callback, chỉ có capture groups:

```php
// Pattern: /^(set|get)(.*)Attribute$/
// Method: getFullNameAttribute()
// matches[0] = "getFullNameAttribute" (KHÔNG được truyền)
// matches[1] = "get" (được truyền)
// matches[2] = "FullName" (được truyền)
```

## Ví Dụ Thực Tế: Dynamic Attribute Accessors

```php
class ModelService extends Service
{
    protected $model;
    
    public function __construct($model = null)
    {
        parent::__construct();
        $this->model = $model;
    }
    
    public function initService()
    {
        // Pattern cho getter/setter động
        $this->addMacro('/^(set|get)(.*)Attribute$/', function($action, $attributeName) {
            $attribute = $this->normalizeAttributeName($attributeName);
            
            if ($action === 'get') {
                // Getter
                $method = 'get' . ucfirst($attribute) . 'Attribute';
                if (method_exists($this, $method)) {
                    return $this->{$method}();
                }
                return $this->attributes[$attribute] ?? null;
            } else {
                // Setter
                $value = func_get_arg(2); // Value từ method call
                $method = 'set' . ucfirst($attribute) . 'Attribute';
                if (method_exists($this, $method)) {
                    return $this->{$method}($value);
                }
                $this->attributes[$attribute] = $value;
                return $this;
            }
        }, self::MACRO_TYPE_PATTERN);
    }
    
    protected function normalizeAttributeName($name)
    {
        // Convert "FullName" -> "full_name"
        return strtolower(preg_replace('/([A-Z])/', '_$1', $name));
    }
}

// Sử dụng
$service = new ModelService();

// Set attributes
$service->setFullNameAttribute('John Doe');
$service->setEmailAttribute('john@example.com');

// Get attributes
echo $service->getFullNameAttribute(); // "John Doe"
echo $service->getEmailAttribute(); // "john@example.com"
```

## Kết Luận

Pattern macros với matches cho phép bạn tạo dynamic methods rất mạnh mẽ. Nhớ rằng:
- Capture groups được truyền trước arguments
- Sử dụng `func_get_arg()` để truy cập arguments sau capture groups
- Pattern phải có capture groups để hoạt động

