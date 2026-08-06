# Hướng Dẫn Các Thay Đổi Gần Đây

## 📋 Tổng Quan

Tài liệu này mô tả các thay đổi và cải tiến gần đây trong Core Framework, bao gồm:
- **ViewMethods**: Tích hợp ViewContextManager
- **ResponseMethods**: Tự động trả về View/JSON
- **CRUDMethods**: Cải thiện logic getValidatorRepository
- **ModuleMethods**: RepositoryTap và error handling

---

## 🔄 Thay Đổi Cấu Trúc

### **Namespace Mới**

Tất cả Methods traits đã được di chuyển từ `Services\Methods` sang `Support\Methods`:

```php
// ❌ Cũ
use Saola\Core\Services\Methods\ViewMethods;
use Saola\Core\Services\Methods\ResponseMethods;

// ✅ Mới
use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\ResponseMethods;
```

---

## 📦 ViewMethods - Tích Hợp ViewContextManager

### **Thay Đổi Chính**

ViewMethods đã được refactor để sử dụng `ViewContextManager` từ service container.

### **Cách Sử Dụng**

```php
use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ViewMethods;

class UserService extends ModuleService
{
    use ViewMethods;
    
    protected $context = 'web';
    protected $module = 'users';
    protected $moduleName = 'Người dùng';
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView(); // Khởi tạo view context
    }
    
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        // Render view với context
        return $this->render('users.index', [
            'users' => $users
        ]);
    }
}
```

### **Các Method Mới**

#### **1. `initView()`**

Khởi tạo view context và các cấu hình view:

```php
public function initView()
{
    if (!$this->moduleBlade) {
        $this->moduleBlade = $this->module;
    }
    // Thiết lập viewBasePath, moduleBlade, pageViewBlade
    // ...
}
```

#### **2. `getViewContextManager()`**

Lấy ViewContextManager từ container:

```php
protected function getViewContextManager(): ViewContextManager
{
    return App::make(ViewContextManager::class);
}
```

#### **3. `getModuleActionKey()`**

Tạo key cho module action:

```php
protected function getModuleActionKey(string $action = ''): string
{
    return $this->context . 
           ($this->module ? '.' . $this->module : '') . 
           ($action ? '.' . $action : '');
}
```

### **Lưu Ý**

- `viewInit()` đã đổi tên thành `initView()`
- ViewContextManager được lấy từ container (singleton)
- Context được quản lý tự động qua ViewContextManager

---

## 📦 ResponseMethods - Tự Động View/JSON

### **Tính Năng Mới**

ResponseMethods tự động quyết định trả về View hoặc JSON dựa trên request headers.

### **Cách Sử Dụng**

```php
use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\Support\Methods\ViewMethods;

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
        ], 'users.index');
    }
}
```

### **Các Header Được Hỗ Trợ**

1. **`x-one-response: json`** - Header custom
2. **`Accept: application/json`** - Standard header

### **Cấu Trúc JSON Response**

Khi trả về JSON, response có cấu trúc:

```json
{
  "data": {
    "users": [...],
    "title": "Danh sách người dùng"
  },
  "view": "<html>...</html>"
}
```

### **Options**

```php
return $this->response($request, $data, 'users.index', [
    'status' => 201,                    // HTTP status code
    'headers' => ['X-Custom' => 'val'], // Custom headers
    'jsonOptions' => JSON_PRETTY_PRINT, // JSON options
    'forceJson' => true,                // Buộc JSON
    'forceView' => true,                // Buộc View
    'includeView' => false              // Không include view HTML
]);
```

### **Các Method**

#### **1. `response()`**

Method chính để trả về response:

```php
public function response(
    Request $request, 
    array $data = [], 
    ?string $bladePath = null, 
    array $options = []
): View|JsonResponse
```

#### **2. `wantsJsonResponse()`**

Kiểm tra request có muốn JSON không:

```php
public function wantsJsonResponse(Request $request): bool
```

#### **3. `getHeaderCaseInsensitive()`**

Lấy header value không phân biệt hoa/thường:

```php
protected function getHeaderCaseInsensitive(
    Request $request, 
    string $headerName, 
    $default = null
)
```

### **Ví Dụ**

#### **Web Request (trả về View)**
```bash
GET /users
```

#### **API Request (trả về JSON)**
```bash
curl -H "x-one-response: json" /users
# hoặc
curl -H "Accept: application/json" /users
```

---

## 📦 CRUDMethods - Cải Thiện Logic

### **Thay Đổi: `getValidatorRepository()`**

Logic đã được cải thiện để rõ ràng và an toàn hơn.

#### **Logic Cũ (Có Bug)**

```php
// ❌ Logic cũ có bug
return $this->validatorRepository??$this->repository??$this->repositoryClass?app($this->repositoryClass):null;
```

**Vấn đề:** Logic phức tạp, có thể gây lỗi khi `repositoryClass` là string.

#### **Logic Mới (Đã Sửa)**

```php
// ✅ Logic mới rõ ràng và an toàn
public function getValidatorRepository()
{
    // Ưu tiên 1: validatorRepository
    if ($this->validatorRepository !== null) {
        return $this->validatorRepository;
    }
    
    // Ưu tiên 2: repository
    if ($this->repository !== null) {
        return $this->repository;
    }
    
    // Ưu tiên 3: repositoryClass (resolve từ container)
    if ($this->repositoryClass && 
        is_string($this->repositoryClass) && 
        class_exists($this->repositoryClass)) {
        return app($this->repositoryClass);
    }
    
    return null;
}
```

### **Ưu Tiên**

1. **validatorRepository** - Nếu đã được set trực tiếp
2. **repository** - Nếu có repository instance
3. **repositoryClass** - Resolve từ container nếu là string hợp lệ
4. **null** - Nếu không có gì

### **Cải Thiện**

- ✅ Logic rõ ràng, dễ đọc
- ✅ Kiểm tra `class_exists()` trước khi resolve
- ✅ An toàn hơn với type checking
- ✅ Có documentation đầy đủ

---

## 📦 ModuleMethods - RepositoryTap

### **Tính Năng: `repositoryTap()`**

Method mới để thực hiện operations với repository một cách an toàn.

### **Cách Sử Dụng**

```php
use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ModuleMethods;

class UserService extends ModuleService
{
    use ModuleMethods;
    
    public function getUserList(Request $request)
    {
        return $this->repositoryTap(function($repository) use ($request) {
            return $repository->getResults($request);
        }, EmptyCollection::class);
    }
    
    public function getUserDetail($id)
    {
        return $this->repositoryTap(function($repository) use ($id) {
            return $repository->detail($id);
        }, EmptyMask::class);
    }
}
```

### **Signature**

```php
public function repositoryTap(
    callable $callback, 
    mixed $default = null, 
    bool $logError = null
): mixed
```

### **Tham Số**

- **`$callback`**: Callback thực hiện với repository
- **`$default`**: Giá trị mặc định khi có lỗi (có thể là class string, object, hoặc giá trị khác)
- **`$logError`**: Có log lỗi không (mặc định: true trong debug mode)

### **Tính Năng**

1. **Error Handling**: Tự động catch exceptions
2. **Default Value**: Trả về giá trị mặc định khi có lỗi
3. **Auto Resolve**: Tự động resolve class từ container nếu `$default` là class string
4. **Logging**: Tự động log lỗi trong debug mode

### **Ví Dụ**

#### **1. Với Default Class**

```php
$users = $this->repositoryTap(function($repo) {
    return $repo->getResults($request);
}, EmptyCollection::class); // Tự động resolve từ container
```

#### **2. Với Default Value**

```php
$result = $this->repositoryTap(function($repo) {
    return $repo->create($data);
}, false); // Trả về false nếu có lỗi
```

#### **3. Với Custom Error Handling**

```php
$user = $this->repositoryTap(function($repo) use ($id) {
    return $repo->detail($id);
}, EmptyMask::class, false); // Không log lỗi
```

### **Các Method Sử Dụng RepositoryTap**

Tất cả các method CRUD đã được cập nhật để sử dụng `repositoryTap()`:

- `getResults()` → Trả về `EmptyCollection` nếu lỗi
- `getDetail()` → Trả về `EmptyMask` nếu lỗi
- `getTrashedResults()` → Trả về `EmptyCollection` nếu lỗi
- `moveToTrash()` → Trả về `false` nếu lỗi
- `restoreFromTrash()` → Trả về `false` nếu lỗi
- `delete()` → Trả về `false` nếu lỗi
- `erase()` → Trả về `false` nếu lỗi
- `update()` → Trả về `false` nếu lỗi
- `create()` → Trả về `false` nếu lỗi
- `createMany()` → Trả về `false` nếu lỗi

---

## 🔧 Migration Guide

### **Bước 1: Cập Nhật Namespace**

Tìm và thay thế tất cả:

```php
// Cũ
use Saola\Core\Services\Methods\...

// Mới
use Saola\Core\Support\Methods\...
```

### **Bước 2: Cập Nhật Method Names**

```php
// Tìm
$this->viewInit()

// Thay bằng
$this->initView()
```

### **Bước 3: Cập Nhật Service Classes**

```php
use Saola\Core\Services\ModuleService;
use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\ResponseMethods;

class UserService extends ModuleService
{
    use ViewMethods, ResponseMethods;
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
    }
}
```

---

## 📚 Tài Liệu Liên Quan

- [STRUCTURE_OVERVIEW.md](./STRUCTURE_OVERVIEW.md) - Tổng quan cấu trúc
- [RESPONSE_METHODS_USAGE.md](./RESPONSE_METHODS_USAGE.md) - Hướng dẫn ResponseMethods
- [VIEW_CONTEXT_MANAGER_GUIDE.md](./VIEW_CONTEXT_MANAGER_GUIDE.md) - Hướng dẫn ViewContextManager
- [SERVICE_ARCHITECTURE_ANALYSIS.md](./SERVICE_ARCHITECTURE_ANALYSIS.md) - Phân tích kiến trúc

---

## ✅ Checklist Migration

- [ ] Cập nhật namespace từ `Services\Methods` sang `Support\Methods`
- [ ] Đổi `viewInit()` thành `initView()`
- [ ] Cập nhật service classes với namespace mới
- [ ] Test các method đã thay đổi
- [ ] Cập nhật tests nếu có
- [ ] Review code để đảm bảo không có breaking changes

---

## 🐛 Troubleshooting

### **Lỗi: Class not found**

**Nguyên nhân:** Chưa cập nhật namespace

**Giải pháp:**
```php
// Sửa từ
use Saola\Core\Services\Methods\ViewMethods; // ❌ Cũ

// Sau
use Saola\Core\Support\Methods\ViewMethods; // ✅ Mới
```

### **Lỗi: Method viewInit() not found**

**Nguyên nhân:** Method đã đổi tên

**Giải pháp:**
```php
// Sửa từ
$this->viewInit();

// Thành
$this->initView();
```

### **Lỗi: getValidatorRepository() trả về sai**

**Nguyên nhân:** Logic cũ có bug

**Giải pháp:** Đảm bảo đã update code mới nhất với logic đã sửa

---

## 📝 Changelog

### **v2.0.0** (Recent)

- ✅ Di chuyển Methods từ `Services\Methods` sang `Support\Methods`
- ✅ ViewMethods tích hợp ViewContextManager
- ✅ ResponseMethods tự động View/JSON
- ✅ CRUDMethods cải thiện getValidatorRepository
- ✅ ModuleMethods thêm repositoryTap
- ✅ Đổi `viewInit()` thành `initView()`

---

**Cập nhật lần cuối:** 2025-01-XX

