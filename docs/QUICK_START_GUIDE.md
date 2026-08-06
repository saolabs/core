# Quick Start Guide - Core Framework

## 🚀 Bắt Đầu Nhanh

Hướng dẫn nhanh để sử dụng Core Framework với các tính năng mới nhất.

---

## 📦 Cài Đặt

### **1. Tạo Service**

```php
<?php

namespace App\Services;

use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\ResponseMethods;
use Illuminate\Http\Request;

class UserService extends ModuleService
{
    use ViewMethods, ResponseMethods;
    
    protected $context = 'web';
    protected $module = 'users';
    protected $moduleName = 'Người dùng';
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
    }
}
```

### **2. Sử Dụng Trong Controller**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;

class UserController extends Controller
{
    public function index(Request $request, UserService $service)
    {
        // Tự động trả về view hoặc JSON
        return $service->getUserList($request);
    }
}
```

---

## 🎯 Các Tính Năng Chính

### **1. Tự Động View/JSON Response**

```php
public function getUserList(Request $request)
{
    $users = $this->repository->getResults($request);
    
    // Tự động trả về view hoặc JSON dựa trên header
    return $this->response($request, [
        'users' => $users,
        'title' => 'Danh sách người dùng'
    ], 'users.index');
}
```

**Request Headers:**
- `x-one-response: json` → Trả về JSON
- `Accept: application/json` → Trả về JSON
- Không có header → Trả về View

### **2. View Rendering với Context**

```php
// Render view thông thường
return $this->render('users.index', ['users' => $users]);

// Render module view
return $this->renderModule('list', ['users' => $users]);

// Render page view
return $this->renderPage('home', ['data' => $data]);

// Render component
return $this->renderComponent('card', ['item' => $item]);
```

### **3. Repository Operations An Toàn**

```php
// Sử dụng repositoryTap để xử lý an toàn
$users = $this->repositoryTap(function($repo) use ($request) {
    return $repo->getResults($request);
}, EmptyCollection::class);

// Hoặc sử dụng các method có sẵn
$users = $this->getResults($request); // Tự động sử dụng repositoryTap
$user = $this->getDetail($id);
```

---

## 📋 Ví Dụ Đầy Đủ

### **Service Hoàn Chỉnh**

```php
<?php

namespace App\Services;

use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\ResponseMethods;
use Illuminate\Http\Request;
use App\Repositories\UserRepository;

class UserService extends ModuleService
{
    use ViewMethods, ResponseMethods;
    
    protected $context = 'web';
    protected $module = 'users';
    protected $moduleName = 'Người dùng';
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
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
}
```

### **Controller**

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
}
```

---

## 🔧 Cấu Hình

### **View Context**

```php
protected $context = 'web'; // web, admin, api, ...
```

### **Module**

```php
protected $module = 'users'; // Tên module
protected $moduleName = 'Người dùng'; // Tên hiển thị
```

### **Repository**

```php
public function initUser()
{
    $this->setRepositoryClass(UserRepository::class);
}
```

---

## 📚 Tài Liệu Chi Tiết

- [RECENT_UPDATES_GUIDE.md](./RECENT_UPDATES_GUIDE.md) - Các thay đổi gần đây
- [RESPONSE_METHODS_USAGE.md](./RESPONSE_METHODS_USAGE.md) - Hướng dẫn ResponseMethods
- [VIEW_CONTEXT_MANAGER_GUIDE.md](./VIEW_CONTEXT_MANAGER_GUIDE.md) - Hướng dẫn ViewContextManager
- [SERVICE_ARCHITECTURE_ANALYSIS.md](./SERVICE_ARCHITECTURE_ANALYSIS.md) - Phân tích kiến trúc

---

## ✅ Best Practices

1. **Luôn gọi `initView()`** sau khi set repository
2. **Sử dụng `response()`** để tự động view/JSON
3. **Sử dụng `repositoryTap()`** cho operations an toàn
4. **Set context và module** trong service
5. **Sử dụng namespace mới** `Support\Methods`

---

## 🐛 Troubleshooting

### **Lỗi: Class not found**

```php
// ✅ Đúng
use Saola\Core\Support\Methods\ViewMethods;

// ❌ Sai (namespace cũ)
use Saola\Core\Services\Methods\ViewMethods;
```

### **Lỗi: Method not found**

```php
// ✅ Đúng
$this->initView();

// ❌ Sai
$this->viewInit();
```

---

**Cập nhật:** 2025-01-XX

