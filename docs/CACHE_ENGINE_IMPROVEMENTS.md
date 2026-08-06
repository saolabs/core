# Cải Tiến CacheEngine::getKey() - Xử Lý Object và Request

## 📋 Vấn Đề Ban Đầu

Hàm `getKey()` gốc có các hạn chế:

1. **Không xử lý object trong params** ❌
   ```php
   // Nếu params chứa object, json_encode() sẽ fail hoặc không chính xác
   $params = ['user' => $userObject]; // ❌ Không hoạt động đúng
   ```

2. **Không xử lý Request object đặc biệt** ❌
   ```php
   // Request object không được xử lý đặc biệt
   $params = ['request' => $request]; // ❌ Không lấy RequestUri
   ```

3. **json_encode() với object có thể gây lỗi** ❌
   - Object không có method `JsonSerializable` sẽ bị serialize không đúng
   - Request object serialize sẽ rất lớn và không cần thiết

---

## ✨ Giải Pháp

### 1. **Xử Lý Request Object Đặc Biệt** ✅

Khi gặp Request object, hàm sẽ tự động:
- Lấy `RequestUri` qua `$request->getRequestUri()`
- Lấy HTTP method
- Lấy query parameters (đã được sắp xếp)

```php
$request = request();
$key = CacheEngine::getKey('users', ['request' => $request]);

// Request object sẽ được normalize thành:
[
    'uri' => '/api/users?page=1&sort=name',
    'method' => 'GET',
    'query' => ['page' => 1, 'sort' => 'name'] // Đã được sắp xếp
]
```

### 2. **Xử Lý Object Khác** ✅

Hàm hỗ trợ nhiều loại object:

#### Model (Eloquent)
```php
$user = User::find(1);
$key = CacheEngine::getKey('profile', ['user' => $user]);

// Sẽ normalize thành:
['class' => 'App\Models\User', 'key' => 1]
```

#### Object có `toArray()`
```php
$data = new CustomObject();
$key = CacheEngine::getKey('data', ['obj' => $data]);

// Sẽ gọi $data->toArray() và normalize
```

#### Object có `__toString()`
```php
$stringable = new StringableObject();
$key = CacheEngine::getKey('data', ['obj' => $stringable]);

// Sẽ gọi (string)$stringable
```

#### Object khác
```php
$obj = new SomeObject();
$key = CacheEngine::getKey('data', ['obj' => $obj]);

// Sẽ normalize thành:
['class' => 'SomeObject', 'hash' => 'spl_object_hash']
```

### 3. **Recursive Normalization** ✅

Hàm có thể normalize nested array và object:

```php
$params = [
    'user' => $userObject, // Model
    'request' => $request, // Request
    'data' => [
        'nested' => $anotherObject, // Object khác
    ],
];

$key = CacheEngine::getKey('complex', $params);
// Tất cả object sẽ được normalize đệ quy
```

### 4. **Sắp Xếp Để Đảm Bảo Tính Nhất Quán** ✅

- Array keys được sắp xếp (`ksort`)
- Query parameters được sắp xếp
- Đảm bảo cùng params tạo ra cùng key

```php
// Cả 2 đều tạo ra cùng key:
CacheEngine::getKey('test', ['b' => 2, 'a' => 1]);
CacheEngine::getKey('test', ['a' => 1, 'b' => 2]);
```

---

## 📊 So Sánh

### Trước
```php
// ❌ Lỗi hoặc không chính xác
$key = CacheEngine::getKey('users', [
    'request' => $request, // Object không được xử lý
    'user' => $user,      // Object không được xử lý
]);
```

### Sau
```php
// ✅ Hoạt động đúng
$key = CacheEngine::getKey('users', [
    'request' => $request, // ✅ Tự động lấy RequestUri
    'user' => $user,      // ✅ Normalize thành ['class', 'key']
]);
```

---

## 🎯 Ví Dụ Sử Dụng

### 1. Với Request Object
```php
use Illuminate\Http\Request;
use Saola\Core\Engines\CacheEngine;

// Trong controller
public function index(Request $request)
{
    $key = CacheEngine::getKey('users_list', [
        'request' => $request, // Tự động lấy RequestUri
        'filters' => $request->only(['status', 'role']),
    ]);
    
    // Key sẽ bao gồm:
    // - Domain
    // - 'users-list'
    // - RequestUri: /api/users?status=active&role=admin
    // - Method: GET
    // - Filters: ['status' => 'active', 'role' => 'admin']
}
```

### 2. Với Model Object
```php
$user = User::find(1);
$key = CacheEngine::getKey('user_profile', [
    'user' => $user,
    'include' => ['posts', 'comments'],
]);

// Key sẽ bao gồm:
// - Domain
// - 'user-profile'
// - User: ['class' => 'App\Models\User', 'key' => 1]
// - Include: ['posts', 'comments']
```

### 3. Với Nested Object
```php
$params = [
    'request' => $request,
    'user' => $user,
    'options' => [
        'include' => ['profile'],
        'filters' => $request->query(),
    ],
];

$key = CacheEngine::getKey('complex_data', $params);
// Tất cả object sẽ được normalize đệ quy
```

### 4. Backward Compatible
```php
// Code cũ vẫn hoạt động bình thường
$key = CacheEngine::getKey('simple', ['id' => 1, 'status' => 'active']);
$key = CacheEngine::getKey('simple', 'string_param');
```

---

## 🔍 Chi Tiết Implementation

### normalizeParams()
- Xử lý array và normalize từng phần tử
- Đệ quy với nested array

### normalizeValue()
- **Request**: Lấy URI, method, query
- **Model**: Lấy class và key
- **toArray()**: Gọi method và normalize kết quả
- **__toString()**: Convert sang string
- **Object khác**: Lấy class và object hash
- **Array**: Đệ quy normalize
- **Primitive**: Trả về nguyên bản

---

## ⚠️ Lưu Ý

1. **Object Hash**: Với object không có method đặc biệt, sử dụng `spl_object_hash()` - có thể không stable giữa các request. Nên sử dụng Model hoặc object có `toArray()`/`__toString()`.

2. **Performance**: Normalize object có thể tốn thời gian với object phức tạp. Nên cache kết quả nếu có thể.

3. **RequestUri**: RequestUri bao gồm cả query string, nên nếu đã có query params riêng, có thể bị duplicate. Có thể tùy chỉnh logic nếu cần.

4. **Backward Compatible**: Code cũ vẫn hoạt động bình thường, không breaking change.

---

## 🚀 Kết Luận

Hàm `getKey()` đã được cải thiện để:
- ✅ Xử lý Request object và tự động lấy RequestUri
- ✅ Xử lý các loại object khác (Model, toArray, __toString, etc.)
- ✅ Normalize đệ quy với nested structure
- ✅ Đảm bảo tính nhất quán với sắp xếp
- ✅ Backward compatible với code cũ
- ✅ Type-safe và dễ maintain

