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
│  └──────────────────┘       │        │  - Routing / Modules        │ │
│                             │        │  - Cache / Event Systems    │ │
│                             ▼        │  - Blade Directives         │ │
│                   ┌──────────────┐   └───────────────┬─────────────┘ │
│                   │ saola-client │                   │ SSR HTML       │
│                   │ (TypeScript) │◀──────────────────┘               │
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
| `saola/core` | PHP 8.1+ | Laravel backend core: repository, service, routing, views, events, caching |
| `saola-compiler` | Node.js + Python | Biên dịch `.sao` templates → Blade (SSR) + JavaScript (CSR) |
| `saola-client` | TypeScript | Runtime client: reactive views, state, hydration, SPA lifecycle |

---

## 2. Cấu trúc thư mục `saola/core`

```
saola/core/
├── composer.json
├── src/
│   ├── App/                           # Saola\Core\App namespace
│   │   ├── Console/Commands/          # Artisan commands
│   │   ├── Contracts/                 # App-level interfaces
│   │   ├── Database/
│   │   │   ├── Repositories/          # App repository implementations
│   │   │   └── Services/              # App service implementations
│   │   ├── Exceptions/                # Exception handlers
│   │   ├── Http/
│   │   │   ├── Controllers/           # Base controllers
│   │   │   └── Middleware/            # HTTP middleware
│   │   ├── Providers/                 # Laravel service providers
│   │   ├── Routing/                   # Module routing (Action, Module, Router)
│   │   ├── Services/                  # Base service classes
│   │   ├── Support/                   # Helpers, traits, validation rules
│   │   └── View/                      # View composers, compilers, services
│   │
│   ├── core/                          # Saola\Core namespace
│   │   ├── Async/                     # Async/parallel utilities
│   │   ├── Concerns/                  # Model traits (HasUuid, HasSlug, ...)
│   │   ├── Console/                   # Core Artisan commands
│   │   ├── Contracts/                 # Core interfaces
│   │   ├── Crawlers/                  # Web crawlers
│   │   ├── Database/                  # DB utilities, query helpers
│   │   ├── Engines/                   # ShortCode, Cache, DCrypt, JSON engines
│   │   ├── Events/                    # Event system
│   │   ├── Files/                     # File management
│   │   ├── Html/                      # HTML builder, form builder, DOM
│   │   ├── Http/                      # HTTP client, CURL, promises
│   │   ├── Languages/                 # i18n, locale management
│   │   ├── Laravel/                   # Laravel-specific integrations
│   │   ├── Magic/                     # Arr, Str, Any magic classes
│   │   ├── Mailer/                    # Mail system
│   │   ├── Masks/                     # Data masking
│   │   ├── Models/                    # Base Eloquent models
│   │   ├── Promise/                   # Promise/async HTTP
│   │   ├── Providers/                 # Core service providers
│   │   ├── Queues/                    # Queue utilities
│   │   ├── Repositories/              # Repository pattern base
│   │   ├── Services/                  # Core service base classes
│   │   ├── Support/                   # Core utility methods
│   │   ├── System/                    # System-level utilities
│   │   └── Validators/                # Validation extensions
│   │
│   ├── config/
│   │   └── saola.php                  # Published config (từ one.php)
│   │
│   └── helpers/
│       ├── __loader__.php             # Auto-loader
│       ├── helpers.php                # Global helper functions
│       └── utils.php                  # Utility functions
│
└── tests/
    ├── TestCase.php
    ├── Feature/
    └── Unit/
```

---

## 3. Namespace Map

### Trước → Sau

| Trước | Sau |
|---|---|
| `One\Core\` | `Saola\Core\` |
| `One\App\` | `Saola\Core\Framework\` |
| `One\Core\Providers\OneServiceProvider` | `Saola\Core\Providers\SaolaServiceProvider` |
| `One\App\Providers\OneServiceProvider` | `Saola\Core\Framework\Providers\SaolaAppServiceProvider` |

### composer.json autoload

```json
{
  "autoload": {
    "psr-4": {
      "Saola\\Core\\App\\": "src/core/Framework/",
      "Saola\\Core\\": "src/core/"
    },
    "files": [
      "src/helpers/__loader__.php"
    ]
  }
}
```

> `Saola\Core\Framework\` tự động resolve vì `App/` nằm trong `src/core/`.

---

## 4. Luồng dữ liệu chính

### Request → Response (SSR mode)

```
HTTP Request
    │
    ▼
Laravel Router
    │
    ▼
Saola\Core\Framework\Routing\Router       ← Module routing
    │
    ▼
Saola\Core\Framework\Http\Controllers\*   ← Base controller
    │
    ├── Saola\Core\Repositories\*   ← Data layer
    │       └── Saola\Core\Models\* ← Eloquent
    │
    ├── Saola\Core\Framework\Services\*   ← Business logic
    │
    └── Saola\Core\Framework\View\*       ← View rendering
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

1. **Zero Config by Default** — Service providers tự đăng ký đủ thứ
2. **Octane-Safe** — Không có static state; dùng request-scoped bindings
3. **PSR compliant** — PSR-4 autoload, PSR-7/15 HTTP interfaces
4. **Decorator Pattern** — Magic classes (Arr, Str) bọc native PHP types
5. **Repository over ActiveRecord** — Tách data access khỏi domain logic
6. **Module-based Routing** — Routes được tổ chức theo module, không file route khổng lồ
