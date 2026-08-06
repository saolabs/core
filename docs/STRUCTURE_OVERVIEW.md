# Tổng Quan Cấu Trúc Core Framework

## 📁 Cấu Trúc Thư Mục

```
src/core/
├── Services/              # Service classes (BaseService, ModuleService, ViewService)
│   ├── Service.php        # Base service
│   ├── ModuleService.php  # Service cho modules (CRUD)
│   ├── ViewService.php    # Service cho views
│   └── ThemeService.php   # Service cho themes
│
├── Support/Methods/       # Trait methods (Support\Methods namespace)
│   ├── ModuleMethods.php      # Methods cho module operations
│   ├── CRUDMethods.php        # Methods cho CRUD operations
│   ├── ViewMethods.php        # Methods cho view rendering
│   ├── ResponseMethods.php    # Methods cho response handling
│   ├── CacheMethods.php       # Methods cho caching
│   ├── FileMethods.php        # Methods cho file operations
│   ├── AttributeMethods.php   # Methods cho attributes
│   ├── SmartInit.php          # Auto initialization
│   └── OneMacro.php           # Macro system
│
├── Repositories/          # BaseRepository & actions
├── Validators/            # Validator classes
├── Engines/               # Engine classes (ShortCode, ViewContextManager, etc.)
└── Routing/               # Module, Router, Action
```

## 🔄 Thay Đổi Cấu Trúc

### **Trước đây:**
```
Services/Methods/
├── ModuleMethods.php
├── CRUDMethods.php
├── ViewMethods.php
└── ResponseMethods.php
```

### **Hiện tại:**
```
Support/Methods/
├── ModuleMethods.php
├── CRUDMethods.php
├── ViewMethods.php
└── ResponseMethods.php
```

## 📦 Namespace Standard

Tất cả các Methods traits nằm trong namespace:

```php
// ❌ Cũ (phiên bản cũ/legacy)
use One\Core\Services\Methods\ViewMethods;
use One\Core\Services\Methods\ResponseMethods;

// ✅ Hiện tại (Saola Core)
use Saola\Core\Support\Methods\ViewMethods;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\Support\Methods\ModuleMethods;
use Saola\Core\Support\Methods\CRUDMethods;
```

## 🎯 Cách Sử Dụng

### **ModuleService**

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

### **ViewService**

```php
use Saola\Core\Services\ViewService;
use Saola\Core\Support\Methods\CacheMethods;

class CustomViewService extends ViewService
{
    use CacheMethods;
}
```

## 📚 Tài Liệu Liên Quan

- [SERVICE_ARCHITECTURE_ANALYSIS.md](./SERVICE_ARCHITECTURE_ANALYSIS.md) - Phân tích kiến trúc Service
- [SERVICE_ARCHITECTURE_EXAMPLES.md](./SERVICE_ARCHITECTURE_EXAMPLES.md) - Ví dụ sử dụng
- [RESPONSE_METHODS_USAGE.md](./RESPONSE_METHODS_USAGE.md) - Hướng dẫn ResponseMethods
- [VIEW_CONTEXT_MANAGER_GUIDE.md](./VIEW_CONTEXT_MANAGER_GUIDE.md) - Hướng dẫn ViewContextManager

## ✅ Đã Cập Nhật

- ✅ Namespace chuẩn hóa thành `Saola\Core\Support\Methods`
- ✅ Method `viewInit()` đã đổi thành `initView()`
- ✅ Cấu trúc mới đã được phản ánh trong tài liệu
