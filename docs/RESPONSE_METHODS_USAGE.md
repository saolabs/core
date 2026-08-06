# Hướng Dẫn Sử Dụng ResponseMethods Trait

## 📋 Tổng Quan

Trait `ResponseMethods` giúp tự động quyết định trả về **View** hoặc **JSON** dựa trên request headers, giúp code gọn hơn và hỗ trợ cả Web và API trong cùng một method.

---

## 🚀 Cài Đặt

### 1. Thêm Trait vào Service

```php
<?php

namespace App\Services;

use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\Support\Methods\ViewMethods; // Optional - nếu cần render view

class UserService extends ModuleService
{
    use ResponseMethods, ViewMethods; // Thêm ResponseMethods
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView(); // Nếu dùng ViewMethods
    }
}
```

---

## 📖 Các Cách Sử Dụng

### **1. Sử Dụng Cơ Bản - Tự Động View/JSON**

```php
class UserService extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        // Tự động trả về view hoặc JSON
        return $this->response($request, [
            'users' => $users,
            'title' => 'Danh sách người dùng'
        ], 'users.index'); // Blade path
    }
}
```

**Kết quả:**
- Nếu request có header `x-one-response: json` hoặc `Accept: application/json` → Trả về JSON
- Nếu không → Trả về View `users.index`

---

### **2. Trong Controller**

```php
class UserController extends Controller
{
    public function index(Request $request, UserService $service)
    {
        // Service tự động quyết định view hay JSON
        return $service->getUserList($request);
    }
}
```

---

### **3. Chỉ Trả Về JSON (Không Có View)**

```php
public function getUserData(Request $request)
{
    $users = $this->repository->getResults($request);
    
    // Không có bladePath → luôn trả về JSON
    return $this->response($request, [
        'users' => $users,
        'count' => $users->count()
    ]);
}
```

---

### **4. Với Options - Custom Status Code & Headers**

```php
public function createUser(Request $request)
{
    $validated = $this->validate($request, 'CreateUser');
    $user = $this->repository->create($validated);
    
    return $this->response($request, [
        'user' => $user,
        'message' => 'Tạo người dùng thành công'
    ], 'users.detail', [
        'status' => 201, // HTTP status code
        'headers' => [
            'X-Custom-Header' => 'value',
            'X-User-ID' => $user->id
        ],
        'jsonOptions' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ]);
}
```

---

### **5. Buộc Trả Về JSON**

```php
public function exportUsers(Request $request)
{
    $users = $this->repository->getResults($request);
    
    // Buộc trả về JSON dù có bladePath
    return $this->response($request, [
        'users' => $users
    ], 'users.index', [
        'forceJson' => true
    ]);
}
```

---

### **6. Buộc Trả Về View**

```php
public function showUser(Request $request, $id)
{
    $user = $this->getDetail($id);
    
    // Buộc trả về view dù có header JSON
    return $this->response($request, [
        'user' => $user
    ], 'users.detail', [
        'forceView' => true
    ]);
}
```

---

### **7. Sử Dụng Helper Method `autoResponse()`**

```php
public function getUserList(Request $request)
{
    $users = $this->repository->getResults($request);
    
    // Alias của response()
    return $this->autoResponse($request, [
        'users' => $users
    ], 'users.index');
}
```

---

### **8. Kiểm Tra Request Có Muốn JSON Không**

```php
public function getUserList(Request $request)
{
    $users = $this->repository->getResults($request);
    
    // Kiểm tra trước khi xử lý
    if ($this->wantsJsonResponse($request)) {
        // Logic đặc biệt cho JSON response
        return $this->response($request, [
            'users' => $users->toArray(),
            'meta' => [
                'total' => $users->total(),
                'page' => $users->currentPage()
            ]
        ]);
    }
    
    // Logic cho view response
    return $this->response($request, [
        'users' => $users
    ], 'users.index');
}
```

---

## 🎯 Các Trường Hợp Sử Dụng Thực Tế

### **Ví Dụ 1: Service Đầy Đủ**

```php
<?php

namespace App\Services;

use Illuminate\Http\Request;
use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\Support\Methods\ViewMethods;

class UserService extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
        $this->module = 'users';
        $this->moduleName = 'Người dùng';
    }
    
    /**
     * Danh sách người dùng
     */
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        return $this->response($request, [
            'users' => $users,
            'title' => 'Danh sách người dùng'
        ], 'users.index');
    }
    
    /**
     * Chi tiết người dùng
     */
    public function getUserDetail(Request $request, $id)
    {
        $user = $this->getDetail($id);
        
        if (!$user || $user->isEmpty()) {
            return $this->response($request, [
                'error' => 'Không tìm thấy người dùng'
            ], null, ['status' => 404]);
        }
        
        return $this->response($request, [
            'user' => $user,
            'title' => 'Chi tiết người dùng'
        ], 'users.detail');
    }
    
    /**
     * Tạo người dùng mới
     */
    public function createUser(Request $request)
    {
        $validated = $this->validate($request, 'CreateUser');
        $user = $this->repository->create($validated);
        
        return $this->response($request, [
            'user' => $user,
            'message' => 'Tạo người dùng thành công'
        ], 'users.detail', [
            'status' => 201
        ]);
    }
    
    /**
     * Cập nhật người dùng
     */
    public function updateUser(Request $request, $id)
    {
        $validated = $this->validate($request, 'UpdateUser');
        $user = $this->repository->update($id, $validated);
        
        return $this->response($request, [
            'user' => $user,
            'message' => 'Cập nhật thành công'
        ], 'users.detail');
    }
}
```

---

### **Ví Dụ 2: Controller Đơn Giản**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;

class UserController extends Controller
{
    public function index(Request $request, UserService $service)
    {
        return $service->getUserList($request);
    }
    
    public function show(Request $request, $id, UserService $service)
    {
        return $service->getUserDetail($request, $id);
    }
    
    public function store(Request $request, UserService $service)
    {
        return $service->createUser($request);
    }
    
    public function update(Request $request, $id, UserService $service)
    {
        return $service->updateUser($request, $id);
    }
}
```

---

## 🔍 Cách Request Headers Hoạt Động

### **Header 1: `x-one-response: json`**

```bash
# Request với header custom
curl -H "x-one-response: json" http://example.com/users

# Hoặc các biến thể (đều hoạt động):
curl -H "X-Saola-Response: JSON" http://example.com/users
curl -H "X-ONE-RESPONSE: Json" http://example.com/users
```

**Kết quả:** Trả về JSON

---

### **Header 2: `Accept: application/json`**

```bash
# Request với Accept header
curl -H "Accept: application/json" http://example.com/users

# Hoặc:
curl -H "accept: APPLICATION/JSON" http://example.com/users
```

**Kết quả:** Trả về JSON

---

### **Request Không Có Header JSON**

```bash
# Request bình thường (browser)
GET http://example.com/users
```

**Kết quả:** Trả về View (HTML)

---

## 📊 Bảng So Sánh

| Tình Huống | Header | BladePath | Kết Quả |
|------------|--------|-----------|---------|
| Web request | Không có | `users.index` | View |
| API request | `x-one-response: json` | `users.index` | JSON |
| API request | `Accept: application/json` | `users.index` | JSON |
| API request | Có header JSON | `null` | JSON |
| Force JSON | Bất kỳ | `users.index` | JSON (với `forceJson: true`) |
| Force View | Có header JSON | `users.index` | View (với `forceView: true`) |

---

## ⚙️ Options Chi Tiết

### **Các Options Có Thể Truyền:**

```php
$options = [
    // HTTP status code (mặc định: 200)
    'status' => 201,
    
    // Headers bổ sung cho JSON response
    'headers' => [
        'X-Custom-Header' => 'value',
        'X-API-Version' => 'v1'
    ],
    
    // Options cho json_encode
    // Mặc định: JSON_UNESCAPED_UNICODE
    'jsonOptions' => JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
    
    // Buộc trả về JSON (bỏ qua header check)
    'forceJson' => true,
    
    // Buộc trả về View (bỏ qua header check)
    'forceView' => true,
];
```

---

## 🎨 Tích Hợp Với ViewMethods

Nếu service sử dụng cả `ViewMethods` trait, method `render()` sẽ được tự động sử dụng:

```php
class UserService extends ModuleService
{
    use ResponseMethods, ViewMethods;
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView(); // Quan trọng!
    }
    
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        // Sẽ sử dụng $this->render() từ ViewMethods
        // Thay vì view() helper thông thường
        return $this->response($request, [
            'users' => $users
        ], 'users.index');
    }
}
```

**Lợi ích:**
- Tự động merge `defaultViewData` từ ViewMethods
- Hỗ trợ `moduleBlade`, `pageViewBlade`
- Tích hợp với view system của framework

---

## 🔧 Advanced Usage

### **1. Custom JSON Structure**

```php
public function getUserList(Request $request)
{
    $users = $this->repository->getResults($request);
    
    // Custom structure cho JSON
    $jsonData = [
        'success' => true,
        'data' => [
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage()
            ]
        ],
        'message' => 'Lấy danh sách thành công'
    ];
    
    return $this->response($request, $jsonData, 'users.index');
}
```

---

### **2. Conditional Response**

```php
public function getUserList(Request $request)
{
    $users = $this->repository->getResults($request);
    
    // Nếu là JSON request, thêm metadata
    if ($this->wantsJsonResponse($request)) {
        $data = [
            'users' => $users,
            'meta' => [
                'total' => $users->total(),
                'timestamp' => now()->toIso8601String()
            ]
        ];
    } else {
        $data = [
            'users' => $users,
            'title' => 'Danh sách người dùng'
        ];
    }
    
    return $this->response($request, $data, 'users.index');
}
```

---

## ✅ Best Practices

1. **Luôn truyền Request object** - Để trait có thể kiểm tra headers
2. **Sử dụng ViewMethods** - Nếu cần render view với view system của framework
3. **Tổ chức data rõ ràng** - Phân biệt data cho view và JSON nếu cần
4. **Sử dụng options** - Cho các trường hợp đặc biệt (status code, headers)
5. **Kiểm tra trước khi xử lý** - Dùng `wantsJsonResponse()` nếu cần logic khác nhau

---

## 🐛 Troubleshooting

### **Vấn đề: Luôn trả về JSON**

**Nguyên nhân:** Request có header JSON
**Giải pháp:** Kiểm tra headers trong request hoặc dùng `forceView: true`

---

### **Vấn đề: Luôn trả về View**

**Nguyên nhân:** Không có header JSON trong request
**Giải pháp:** Thêm header `x-one-response: json` hoặc `Accept: application/json`

---

### **Vấn đề: View không render đúng**

**Nguyên nhân:** Chưa gọi `initView()` hoặc chưa dùng ViewMethods
**Giải pháp:** 
```php
use ViewMethods;
$this->initView();
```

---

## 📚 Tài Liệu Tham Khảo

- [Laravel HTTP Responses](https://laravel.com/docs/responses)
- [Laravel Request](https://laravel.com/docs/requests)
- [ViewMethods Trait](./SERVICE_ARCHITECTURE_EXAMPLES.md)


