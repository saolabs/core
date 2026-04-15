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

### Versioning — Semantic Versioning

```
MAJOR.MINOR.PATCH
  │      │     └── Bug fixes, no API change
  │      └──────── New features, backward compatible
  └─────────────── Breaking changes
```

Hiện tại: `v0.1.0-alpha` (pre-release)

---

## Roadmap

### Phase 0 — Rebrand & Foundation ✅ (Sprint hiện tại)

- [x] Chuyển sang repository `saolabs/core`
- [x] Rebrand package: `saola/core`
- [x] Namespace: `Saola\Core\` + `Saola\Core\Framework\`
- [x] Tách cấu trúc: `src/core/Framework` song song với `src/core` (giữ namespace `Saola\\Core\\App`)
- [x] Service providers: `SaolaServiceProvider`, `SaolaAppServiceProvider`
- [x] Tài liệu kiến trúc `ARCHITECTURE.md`

---

### Phase 1 — Core Stabilization 🔄 (Tiếp theo)

#### 1.1 Testing & QA
- [ ] Viết unit tests cho: Repository base, Magic classes (Arr, Str, Any), Cache engine, Event system
- [ ] PHPUnit coverage ≥ 70% cho core modules
- [ ] Octane compatibility test suite

#### 1.2 Service Providers
- [ ] `SaolaServiceProvider` — đăng ký đủ singleton/bindings cần thiết
- [ ] `SaolaAppServiceProvider` — clean up ViewFactory dead ref
- [ ] `BladeDirectiveServiceProvider` — register `.sao`-compiled directives
- [ ] Facade `Saola::` hoàn thiện

#### 1.3 Repository Layer
- [ ] `BaseRepository` — verify Saola\Core\Framework\Database\Repositories\BaseRepository
- [ ] Support PostgreSQL (đã có `REPOSITORY_POSTGRESQL_SUPPORT.md` - cần implement)
- [ ] `RepositoryTap` error handling đầy đủ

#### 1.4 Configuration
- [ ] `saola.php` config file — clean up, document tất cả keys
- [ ] Publish artisan command `saola:publish` (thay thế `one:publish`)

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
- [ ] `SaolaHydration` trait — tự động chuẩn bị hydration data từ Repository
- [ ] API endpoints convention: `/_saola/state/{component}` 
- [ ] WebSocket/SSE support cho reactive updates (optional, v2)

---

### Phase 4 — Developer Experience

- [ ] `saola new` CLI command (scaffold Laravel + saola stack)
- [ ] Stubs cho: Model, Repository, Service, `.sao` Component
- [ ] `saola:make:component` Artisan command
- [ ] VSCode extension: `saola-language-support` (đã có repo)
- [ ] Hot reload development workflow

---

### Phase 5 — Production Hardening

- [ ] Octane: verify tất cả singletons safe
- [ ] Cache: Redis-first, file fallback
- [ ] Security: input sanitization trong Str, Arr, Validators
- [ ] Rate limiting integration
- [ ] Audit logging (`AuditableInterface` implement)

---

## Domain Model

### Core Abstractions

```
┌──────────────────────────────────────────────────────────┐
│                    DOMAIN LAYERS                          │
│                                                           │
│  HTTP Layer                                               │
│    Controller (Saola\Core\Framework\Http\Controllers)           │
│         │                                                 │
│         ▼                                                 │
│  Service Layer                                            │
│    Service (Saola\Core\Framework\Services\BaseService)          │
│    ModuleService (Saola\Core\Framework\Routing\ModuleService)   │
│         │                                                 │
│         ▼                                                 │
│  Repository Layer                                         │
│    Repository (Saola\Core\Repositories\BaseRepository)    │
│         │ uses filters, masks, cache                      │
│         ▼                                                 │
│  Data Layer                                               │
│    Model (Saola\Core\Models\*)   ← Eloquent               │
│    Database (MySQL / PostgreSQL)                          │
│                                                           │
│  Cross-cutting Concerns                                   │
│    Events (Saola\Core\Events)                             │
│    Cache  (Saola\Core\Engines)                            │
│    Validators (Saola\Core\Validators)                     │
│    Masks  (Saola\Core\Masks)     ← Data transformation    │
└──────────────────────────────────────────────────────────┘
```

### Module System

```
Module = Route Group + Service + Repository

Saola\Core\Framework\Routing\Module      — Định nghĩa module
Saola\Core\Framework\Routing\Router      — Đăng ký routes cho module
Saola\Core\Framework\Routing\Action      — Action handler trong module
Saola\Core\Framework\Routing\ModuleService — Service layer cho module
```

---

## Công việc ngay bây giờ (Sprint backlog)

| Priority | Task | File/Area |
|---|---|---|
| 🔴 High | Clean up `SaolaAppServiceProvider` dead ViewFactory ref | `src/core/Framework/Providers/SaolaAppServiceProvider.php` |
| 🔴 High | Implement `OctaneCompatible` contract (Saola namespace) | `src/core/Contracts/` |
| 🔴 High | Rename `PublishSaolaMigrationsCommand` command string | `src/core/Console/Commands/` |
| 🟡 Medium | Viết test cho `BaseRepository` | `tests/Unit/` |
| 🟡 Medium | Clean `saola.php` config — document tất cả keys | `src/config/saola.php` |
| 🟡 Medium | Fix `namespace Saola\Core\atabase` typo | `src/core/Database/` |
| 🟢 Low | Update `document.txt` docs sang brand Saola | `document.txt` |
| 🟢 Low | Đổi tên `@oneview/compiler` → `@saola/compiler` (compiler repo) | `compiler/package.json` |
