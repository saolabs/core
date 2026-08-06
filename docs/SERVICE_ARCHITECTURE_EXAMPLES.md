# Ví Dụ Cụ Thể: Kiến Trúc Service Layer

## 📋 Các Tình Huống Sử Dụng

### **Tình Huống 1: Service Đơn Giản (Chỉ DB + Logic)**

```php
// UserService.php
class UserService extends ModuleService
{
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
    }
    
    // Business Logic
    public function createUser(array $data): User
    {
        // Validate
        $validated = $this->validate($request, 'CreateUser');
        
        // Business rules
        if ($this->isEmailExists($validated['email'])) {
            throw new \Exception('Email đã tồn tại');
        }
        
        // Create
        $user = $this->repository->create($validated);
        
        // Post-create logic
        $this->afterCreateUser($user);
        
        return $user;
    }
    
    public function getUserList(Request $request)
    {
        // Business logic
        $users = $this->repository->getResults($request);
        
        // Transform data
        return $this->transformUserList($users);
    }
    
    protected function afterCreateUser(User $user)
    {
        // Business logic after create
    }
    
    protected function transformUserList($users)
    {
        // Transform logic
        return $users;
    }
}

// UserController.php
class UserController extends Controller
{
    public function index(Request $request, UserService $service)
    {
        $users = $service->getUserList($request);
        return view('users.index', ['users' => $users]);
    }
    
    public function store(Request $request, UserService $service)
    {
        $user = $service->createUser($request->all());
        return redirect()->route('users.show', $user);
    }
}
```

---

### **Tình Huống 2: Service với View Rendering**

```php
// UserService.php
use Saola\Core\Support\Methods\ViewMethods;

class UserService extends ModuleService
{
    use ViewMethods; // Thêm view methods
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView(); // Init view
    }
    
    // Get data
    public function getUserList(Request $request)
    {
        return $this->repository->getResults($request);
    }
    
    // Render view
    public function renderUserList(Request $request)
    {
        $users = $this->getUserList($request);
        return $this->render('users.index', ['users' => $users]);
    }
    
    public function renderUserDetail($id)
    {
        $user = $this->getDetail($id);
        return $this->render('users.detail', ['user' => $user]);
    }
}

// UserController.php
class UserController extends Controller
{
    public function index(Request $request, UserService $service)
    {
        // Service render luôn
        return $service->renderUserList($request);
    }
    
    public function show($id, UserService $service)
    {
        return $service->renderUserDetail($id);
    }
}
```

---

### **Tình Huống 3: Service với Jobs**

```php
// UserService.php
class UserService extends ModuleService
{
    protected ?JobService $jobService = null;
    
    public function setJobService(JobService $jobService)
    {
        $this->jobService = $jobService;
        return $this;
    }
    
    public function createUser(array $data): User
    {
        $user = $this->repository->create($data);
        
        // Dispatch jobs
        if ($this->jobService) {
            $this->jobService->dispatch(new SendWelcomeEmail($user));
            $this->jobService->dispatch(new CreateUserProfile($user));
        } else {
            // Fallback: dispatch trực tiếp
            SendWelcomeEmail::dispatch($user);
        }
        
        return $user;
    }
}

// Hoặc tạo JobService riêng
class JobService
{
    public function dispatch($job)
    {
        if ($job instanceof \Illuminate\Contracts\Queue\ShouldQueue) {
            dispatch($job);
        } else {
            $job->handle();
        }
    }
    
    public function dispatchSync($job)
    {
        if (method_exists($job, 'handle')) {
            $job->handle();
        }
    }
}
```

---

### **Tình Huống 4: Service với Cache**

```php
// UserService.php
class UserService extends ModuleService
{
    use CacheMethods; // Đã có sẵn
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->setCacheTime(60); // Cache 60 phút
        $this->cacheKey = 'users';
    }
    
    // Cached method
    public function getUserListCache(Request $request)
    {
        // Tự động cache nhờ CacheMethods trait
        return $this->cache('list', [$request], function() use ($request) {
            return $this->repository->getResults($request);
        });
    }
    
    // Hoặc manual cache
    public function getUserList(Request $request)
    {
        $cacheKey = CacheEngine::getKey('users_list', ['request' => $request]);
        
        return cache()->remember($cacheKey, 3600, function() use ($request) {
            return $this->repository->getResults($request);
        });
    }
}
```

---

### **Tình Huống 5: Service với Response Formatting**

```php
// UserService.php
class UserService extends ModuleService
{
    protected ?ResponseService $responseService = null;
    
    public function setResponseService(ResponseService $responseService)
    {
        $this->responseService = $responseService;
        return $this;
    }
    
    public function getUserList(Request $request)
    {
        $users = $this->repository->getResults($request);
        
        // Format response nếu có response service
        if ($this->responseService) {
            return $this->responseService->format($users, $request);
        }
        
        // Fallback: return raw data
        return $users;
    }
}

// ResponseService.php
class ResponseService
{
    public function format($data, Request $request)
    {
        $format = $request->header('Accept', 'application/json');
        
        switch ($format) {
            case 'application/json':
                return response()->json($data);
            case 'application/xml':
                return $this->toXml($data);
            default:
                return $data;
        }
    }
    
    protected function toXml($data)
    {
        // XML conversion logic
    }
}
```

---

### **Tình Huống 6: Service Phức Tạp (Tất Cả Tính Năng)**

```php
// UserService.php
use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\CacheMethods;

class UserService extends ModuleService
{
    use ViewMethods, CacheMethods;
    
    protected ?JobService $jobService = null;
    protected ?ResponseService $responseService = null;
    protected ?NotificationService $notificationService = null;
    
    public function initUser()
    {
        $this->setRepositoryClass(UserRepository::class);
        $this->initView();
        $this->setCacheTime(60);
        $this->cacheKey = 'users';
    }
    
    // Setter methods for composition
    public function setJobService(JobService $service) { ... }
    public function setResponseService(ResponseService $service) { ... }
    public function setNotificationService(NotificationService $service) { ... }
    
    // Business Logic Methods
    public function createUser(array $data): User
    {
        // 1. Validate
        $validated = $this->validate($request, 'CreateUser');
        
        // 2. Business rules
        $this->checkBusinessRules($validated);
        
        // 3. Create
        $user = $this->repository->create($validated);
        
        // 4. Post-create actions
        $this->afterCreateUser($user);
        
        return $user;
    }
    
    public function getUserList(Request $request)
    {
        // Cached
        return $this->cache('list', [$request], function() use ($request) {
            return $this->repository->getResults($request);
        });
    }
    
    public function renderUserList(Request $request)
    {
        $users = $this->getUserList($request);
        return $this->render('users.index', ['users' => $users]);
    }
    
    protected function afterCreateUser(User $user)
    {
        // Jobs
        if ($this->jobService) {
            $this->jobService->dispatch(new SendWelcomeEmail($user));
        }
        
        // Notifications
        if ($this->notificationService) {
            $this->notificationService->notify($user, 'welcome');
        }
        
        // Events
        $this->fire('userCreated', $user);
    }
}
```

---

## 🎯 Quyết Định: Khi Nào Tách Riêng?

### **Tách Riêng Khi:**

1. **View Service**
   - ✅ Có nhiều view logic phức tạp
   - ✅ Cần tái sử dụng view logic ở nhiều service
   - ✅ Có theme system phức tạp

2. **Job Service**
   - ✅ Có nhiều job logic phức tạp
   - ✅ Cần quản lý queue, retry, etc.
   - ✅ Có job scheduling phức tạp

3. **Response Service**
   - ✅ Có nhiều format (JSON, XML, CSV, etc.)
   - ✅ Cần transform data phức tạp
   - ✅ Có API versioning

### **Giữ Trong Main Service Khi:**

1. **View**
   - ✅ Chỉ render đơn giản
   - ✅ View logic gắn chặt với business logic
   - ✅ Sử dụng trait `ViewMethods` là đủ

2. **Jobs**
   - ✅ Chỉ dispatch đơn giản
   - ✅ Không cần quản lý queue phức tạp
   - ✅ Dispatch trực tiếp: `SendEmail::dispatch($user)`

3. **Response**
   - ✅ Chỉ trả về data, controller format
   - ✅ Hoặc format đơn giản trong service

---

## 📊 Decision Matrix

| Tính Năng | Đơn Giản | Phức Tạp | Đề Xuất |
|-----------|----------|----------|---------|
| **DB Operations** | Trong Service | Trong Service | ✅ Repository (đã có) |
| **View Rendering** | Trait | Service riêng | ⚠️ Tùy độ phức tạp |
| **Jobs** | Dispatch trực tiếp | Service riêng | ⚠️ Tùy độ phức tạp |
| **Cache** | Trait | Trait | ✅ CacheMethods (đã có) |
| **Response** | Controller | Service riêng | ⚠️ Tùy format |

---

## ✅ Kết Luận

### **Khuyến Nghị Cuối Cùng:**

1. **Repository**: ✅ **Đã tích hợp sẵn** - Giữ nguyên
2. **View**: ⚠️ **Sử dụng trait** - Chỉ tách riêng nếu phức tạp
3. **Jobs**: ⚠️ **Dispatch trực tiếp** - Chỉ tách riêng nếu cần quản lý phức tạp
4. **Cache**: ✅ **Trait đã có** - Giữ nguyên
5. **Response**: ⚠️ **Controller format** - Chỉ tách riêng nếu nhiều format

### **Pattern Đề Xuất:**

```
MainService extends ModuleService
├── Repository: ✅ Đã có (ModuleMethods)
├── View: ⚠️ Trait (ViewMethods) - Optional
├── Cache: ✅ Trait (CacheMethods) - Đã có
├── Jobs: ⚠️ Dispatch trực tiếp hoặc JobService (nếu phức tạp)
└── Response: ⚠️ Controller hoặc ResponseService (nếu nhiều format)
```

### **Tính Khả Thi: ⭐⭐⭐⭐⭐**

- ✅ Cấu trúc hiện tại đã hỗ trợ tốt
- ✅ Chỉ cần tổ chức lại, không cần refactor lớn
- ✅ Có thể implement từng bước
- ✅ Backward compatible

