# Saola Core — Roadmap & Workflow

## Mô hình làm việc

### Quy trình phát triển

```
┌─────────────────────────────────────────────────────────────┐
│                    SAOLA DEV WORKFLOW                        │
│                                                              │
│  feature/bugfix branch                                       │
│       │                                                      │
│       ├── 1. Viết code + tests                               │
│       ├── 2. composer test  (PHPUnit)                        │
│       ├── 3. PR vào develop                                  │
│       ├── 4. Code review                                     │
│       └── 5. Merge → develop                                 │
│                    │                                         │
│                    ├── Tích hợp test E2E (saola ecosystem)   │
│                    └── Merge → main (release)                │
└─────────────────────────────────────────────────────────────┘
```

### Git branching

| Branch | Mục đích |
|---|---|
| `main` | Stable releases, tagged versions |
| `develop` | Integration branch |
| `feature/*` | Tính năng mới |
| `fix/*` | Bug fixes |
| `release/x.y` | Release preparation |

---

## Roadmap

### Phase 0 — Rebrand & Foundation ✅

- [x] Chuyển sang repository `saolabs/core`
- [x] Rebrand package: `saola/core`
- [x] Namespace: `Saola\Core\`
- [x] Service provider: `SaolaServiceProvider`
- [x] Tài liệu kiến trúc `ARCHITECTURE.md`

---

### Phase 1 — Core Stabilization 🔄

#### 1.1 Testing & QA
- [ ] Viết unit tests cho: Repository base, Magic classes (Arr, Str, Any), Cache engine, Event system
- [ ] PHPUnit coverage ≥ 70% cho core modules
- [ ] Octane compatibility test suite

#### 1.2 Service Providers & Commands
- [ ] `SaolaServiceProvider` — hoàn thiện đăng ký singleton/bindings
- [ ] `BladeDirectiveServiceProvider` — đăng ký các `.sao`-compiled directives
- [ ] Facade `Saola::` hoàn thiện
- [ ] Publish commands và config (`saola-config`, `saola-migrations`)

#### 1.3 Repository Layer
- [ ] `BaseRepository` — tối ưu `Saola\Core\Repositories\BaseRepository`
- [ ] Support PostgreSQL (`REPOSITORY_POSTGRESQL_SUPPORT.md`)
- [ ] `RepositoryTap` error handling đầy đủ

#### 1.4 Configuration
- [ ] `saola.php` config file — clean up, document tất cả keys

---

### Phase 2 — Compiler Integration 🔄

**Mục tiêu:** `saola-compiler` → `saola/core` seamless integration

- [ ] Blade directives cho `.sao` compiled output:
  - `@saolaState(...)` — emit JSON state cho client hydration  
  - `@saolaComponent(...)` — component mount point
  - `@saolaScript(...)` — inject saola-client JS
- [ ] `ViewContextManager` — gắn state với compiled views
- [ ] `SaolaViewService` — render `.sao`-compiled Blade + auto-inject state
- [ ] API contract: PHP ↔ JS state transfer format (JSON schema)

---

### Phase 3 — Client Integration 🔄

**Mục tiêu:** `saola-client` ↔ `saola/core` full-stack reactive loop

- [ ] Server-sent state format: chuẩn hoá JSON payload
- [ ] `ViewContextManager` hydration integration
- [ ] API endpoints convention: `/_saola/state/{component}` 

---

### Phase 4 — Developer Experience

- [ ] Stubs cho: Model, Repository, Service, `.sao` Component
- [ ] VSCode extension integration

---

### Phase 5 — Production Hardening

- [ ] Octane: verify tất cả singletons safe với `OctaneCompatible`
- [ ] Cache: Redis-first, file fallback
- [ ] Security: input sanitization trong Str, Arr, Validators

---

## Domain Model

### Core Abstractions

```
┌──────────────────────────────────────────────────────────┐
│                    DOMAIN LAYERS                          │
│                                                           │
│  HTTP / Controller Layer                                  │
│    Controllers / Routes                                   │
│         │                                                 │
│         ▼                                                 │
│  Service Layer                                            │
│    BaseService (Saola\Core\Services\BaseService)          │
│    ModuleService (Saola\Core\Services\ModuleService)      │
│         │                                                 │
│         ▼                                                 │
│  Repository Layer                                         │
│    BaseRepository (Saola\Core\Repositories\BaseRepository)│
│         │ uses filters, masks, cache                      │
│         ▼                                                 │
│  Data Layer                                               │
│    Model (Saola\Core\Models\*)   ← Eloquent               │
│    Database (MySQL / PostgreSQL)                          │
│                                                           │
│  Cross-cutting Concerns                                   │
│    Events (Saola\Core\Events)                             │
│    Engines (Saola\Core\Engines)                           │
│    Validators (Saola\Core\Validators)                     │
│    Support (Saola\Core\Support\Methods)                   │
└──────────────────────────────────────────────────────────┘
```

### Module System

```
Module = Route Group + Service + Repository

Saola\Core\Routing\Module        — Định nghĩa module
Saola\Core\Routing\Router        — Đăng ký routes cho module
Saola\Core\Routing\Action        — Action handler trong module
Saola\Core\Routing\ModuleService — Service layer cho module
```
