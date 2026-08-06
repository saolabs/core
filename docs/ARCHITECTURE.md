# Saola Core — Architecture

## 1. Tổng quan hệ sinh thái Saola

Saola là framework full-stack cho phép xây dựng ứng dụng Laravel + TypeScript phản ứng nhanh bằng template ngôn ngữ `.sao`.

```
┌───────────────────────────────────────────────────────────────────────┐
│                        SAOLA ECOSYSTEM                                │
│                                                                       │
│  ┌──────────────────┐   .sao files   ┌─────────────────────────────┐ │
│  │  saola-compiler  │───────────────▶│  saola/core (PHP/Laravel)   │ │
│  │  (Node + Python) │                │                             │ │
│  │                  │  Blade output  │  - Repository Pattern       │ │
│  │  .sao ──────────▶│──────────────▶│  - Service Layer            │ │
│  │       └──────────│── JS output    │  - Magic Classes            │ │
│  │                  │                │  - Module Routing           │ │
│  └──────────────────┘       │        │  - View Context Manager     │ │
│                             │        │  - Cache / Event Systems    │ │
│                             ▼        └───────────────┬─────────────┘ │
│                   ┌──────────────┐                   │ SSR HTML       │
│                   │ saola-client │◀──────────────────┘               │
│                   │ (TypeScript) │                                   │
│                   │              │                                   │
│                   │ - Reactive   │                                   │
│                   │   views      │                                   │
│                   │ - State mgmt │                                   │
│                   │ - Hydration  │                                   │
│                   └──────────────┘                                   │
└───────────────────────────────────────────────────────────────────────┘
```

### Vai trò từng package

| Package | Language | Vai trò |
|---|---|---|
| `saola/core` | PHP 8.2+ | Laravel backend core: repository, service, routing, views, events, caching |
| `saola-compiler` | Node.js + Python | Biên dịch `.sao` templates → Blade (SSR) + JavaScript (CSR) |
| `saola-client` | TypeScript | Runtime client: reactive views, state, hydration, SPA lifecycle |

---

## 2. Cấu trúc thư mục `saola/core`

```
saola/core/
├── composer.json
├── src/
│   ├── config/                        # Published configuration (saola.php)
│   ├── core/                          # Saola\Core namespace (PSR-4)
│   │   ├── Async/                     # Async/parallel utilities
│   │   ├── Concerns/                  # Model traits (HasUuid, HasSlug, ...)
│   │   ├── Console/                   # Core Artisan commands
│   │   ├── Contracts/                 # Core interfaces
│   │   ├── Crawlers/                  # Web crawlers
│   │   ├── Database/                  # DB utilities, query helpers
│   │   ├── Engines/                   # ShortCode, Cache, DCrypt, ViewContextManager
│   │   ├── Events/                    # Event system (EventMethods)
│   │   ├── Exceptions/                # Custom exceptions
│   │   ├── Files/                     # File management
│   │   ├── Html/                      # HTML, Form, Menu builders
│   │   ├── Http/                      # HTTP client, CURL, promises
│   │   ├── Languages/                 # i18n, locale management
│   │   ├── Laravel/                   # Laravel-specific integrations
│   │   ├── Magic/                     # Arr, Str, Any magic classes
│   │   ├── Mailer/                    # Mailer & alert system
│   │   ├── Masks/                     # Data masking & transformation
│   │   ├── Models/                    # Base Eloquent models
│   │   ├── Promise/                   # Promise/async HTTP
│   │   ├── Providers/                 # SaolaServiceProvider
│   │   ├── Queues/                    # Queue utilities
│   │   ├── Repositories/              # BaseRepository & CRUD/Filter actions
│   │   ├── Routing/                   # Module routing (Action, Module, Router)
│   │   ├── Services/                  # BaseService, ModuleService, ViewService
│   │   ├── Support/                   # Utility helpers & Methods (ViewMethods, etc.)
│   │   ├── System/                    # System-level utilities
│   │   └── Validators/                # Validation extensions
│   ├── helpers/                       # Global helpers (__loader__.php, helpers.php)
│   └── templates/                     # Default templates
└── tests/
    ├── TestCase.php
    ├── Feature/
    └── Unit/
```

---

## 3. Namespace Map

### Chuẩn hóa Namespace

| Trước (Legacy) | Hiện tại (Chuẩn) |
|---|---|
| `One\Core\` | `Saola\Core\` |
| `OneServiceProvider` | `Saola\Core\Providers\SaolaServiceProvider` |
| Config tag: `one-config` | Config tag: `saola-config` |
| Package: `one/core` | Package: `saola/core` |

### `composer.json` Autoload Configuration

```json
{
  "autoload": {
    "psr-4": {
      "Saola\\Core\\": "src/core/"
    },
    "files": [
      "src/helpers/__loader__.php"
    ],
    "classmap": [
      "src/config/"
    ]
  }
}
```

---

## 4. Luồng dữ liệu chính

### Request → Response (SSR & CSR Hydration Mode)

```
HTTP Request
    │
    ▼
Laravel Router
    │
    ▼
Saola\Core\Routing\Router                   ← Module routing
    │
    ▼
Saola\Core\Services\ModuleService          ← Business logic layer
    │
    ├── Saola\Core\Repositories\BaseRepository ← Data layer
    │       └── Saola\Core\Models\*        ← Eloquent
    │
    └── Saola\Core\Support\Methods\ResponseMethods ← Response decision
            │
            ├── (X-Saola-Response: json / Accept: json) ──▶ JSON Response + ViewContext
            │
            └── (Browser Request) ──▶ Blade View Render
                    │
                    ▼
                .sao compiled Blade view    ← Output từ saola-compiler
                    │
                    ▼
                HTTP Response (HTML + embedded JSON state)
                    │
                    ▼
                saola-client hydrates       ← TypeScript runtime
```

### Template Lifecycle

```
developer writes:         user.sao
                               │
                    saola-compiler compiles
                         ┌────┴────┐
                         ▼         ▼
                    user.blade.php  user.js
                         │         │
                Laravel renders    saola-client loads
                   SSR HTML        reactive state
                         │         │
                         └────┬────┘
                              ▼
                         Hydrated SPA
```

---

## 5. Design Principles

1. **Zero Config by Default** — Service provider tự đăng ký qua Laravel Auto-Discovery
2. **Octane-Safe** — Hỗ trợ `Saola\Core\Contracts\OctaneCompatible` để dọn dẹp state giữa các request
3. **PSR compliant** — PSR-4 autoloading chuẩn mực
4. **Decorator Pattern** — Magic classes (`Arr`, `Str`) bọc native PHP types với phương thức linh hoạt
5. **Repository Pattern** — Tách dữ liệu khỏi domain logic, tích hợp caching & filtering tự động
6. **Module-based Routing** — Tự động tổ chức router theo modules với context quản lý view
