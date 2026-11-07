# Saola Core v2.9.1

Removes the PHP 8.4 "Implicitly marking parameter as nullable" deprecations printed on every artisan and test run: `FilterAction::setResourceClass/setCollectionClass` and `ViewStorageManager::addEventListener/addEventQuickHandle/subscribeState` now declare `?string`. `MenuLink::addIcon` drops two defaults that PHP already ignored because they come before a required parameter. No behaviour change.

Validation: 210 tests, 732 assertions under PHP 8.4.1; `php -l` across `src/` reports no deprecations.

# Saola Core v2.8.0

Adds public auth snapshots shared by SSR and SPA page-data, and an opt-in session Auth API independent of view contexts. Includes password login/logout, CSRF/session rotation, throttling, and stateful Google/GitHub OAuth through optional Socialite with an application-owned identity resolver. Public users default to id, name and username.

Pairs with @saolabs/client 1.6.0 and saola/compiler 3.3.0 for reactive auth views. Existing applications opt in through sao.auth_api; see docs/AUTH_API.md for provider credentials and resolver setup. No application migrations are included.

Validation: 207 tests, 724 assertions; Composer validation. OAuth drivers tested with simulated provider responses.

# Saola Core v2.7.1

Page data no longer re-sends language dictionaries the browser already holds. A running client declares them in `X-Saola-Language: <scope>;<locale>=<revision>;…` and `ClientCatalogs::withoutHeld()` leaves those catalogs out of the response's `i18n`; a changed scope or revision still travels. Without the header (first page load, older clients, tests, API calls) responses are unchanged.

Validation: 194 tests, 619 assertions (new `ClientCatalogsHeldTest`), and saola/admin's 104 tests against this core. In Gehihi admin, client-side page navigations dropped from 52–61 kB to 9.5–16.6 kB. Pairs with @saolabs/client 1.5.2; either may be deployed first.

# Saola Core v2.7.0

Adds conventional context sources and package manifests, safe theme installation and context-aware theme namespaces, per-context CSS manifests and registration of the Blade directives needed by plain contexts. Context/module child routes support the HTTP methods covered by the new regression tests.

Validation: 191 tests, 610 assertions. Existing language APIs are retained; applications upgrading theme paths should rebuild their context views and assets. Released as a Composer Git tag; no application database migration is included.

# Chưa phát hành (sau v2.5.0)

- **ModuleServiceProvider**: hook chính đổi tên `routes()` → `define()` (khai cả route lẫn menu/list/form, quyền gắn module); `routes()` thành tuỳ chọn, `define()` mặc định gọi nó nên module cũ chạy nguyên. Ba hook `define/menus/permissions` đều tuỳ chọn. `make:module` sinh `define()`.

# Chưa phát hành (sau v2.4.0)

Repository, service và model dùng thẳng được cho module ứng dụng, không cần lớp bọc:

- **ModuleService** nạp repository từ `$repositoryClass` (trước đây chỉ ghi tên lớp); CRUD nhận id UUID; thêm `find`, `findOrFail`; ghi không còn trả `false` âm thầm khi lỗi; `restoreFromTrash` gọi đúng `restore`; đọc lỗi luôn được `report()`; `EmptyCollection`/`EmptyMask` không còn abstract nên giá trị rỗng dựng được.
- **Repository**: `findOrFail`, `firstOrFail`; `update/restore/erase/forceDelete` tới được bản ghi đã xoá mềm, một truy vấn, vẫn áp owner_id; `update()` nhận model đã nạp; `@trashed` hoạt động với soft delete (`'all'` = withTrashed); điều kiện trên cột chưa khai lọc đúng (`__get_columns`), cột sai tên ném lỗi; request chỉ lọc theo field khai báo; `query()` luôn dọn điều kiện nối chuỗi.
- **Service**: gọi hàm không tồn tại ném `BadMethodCallException` (trước đây trả `$this`).
- **Model**: concern `Uuid` bỏ kiểu trả về của `getKeyType/getIncrementing`, model core dùng được `HasUuids`.
- **Route builder**: `Router::macro(name, closure)` cho gói thêm phương thức vào module (saola/admin: `menu/list/form`); `routes()` là alias của `group()`; gọi phương thức không tồn tại (có tham số) ném `BadMethodCallException` thay vì im lặng trả `$this`.
- **make:module** sinh Model core, Repository chỉ khai `$model`, Service `extends ModuleService` với `$repositoryClass`, controller inject service; không interface/bind.

Thay đổi hành vi cần lưu ý khi nâng cấp: cột sai tên trong điều kiện code và gọi hàm không tồn tại trên Service hoặc route builder giờ ném lỗi; request không lọc theo cột không khai báo.

# Saola Core v1.0.3

Authentication models now share the system model capabilities used by ordinary Saola models. Applications can extend `Saola\Core\Models\Authenticatable` and keep Laravel authentication/password reset together with system metadata, UUID configuration, lifecycle hooks, file helpers, and BaseRepository integration.

## Changes

- Add `HasSystemModel` and `CommonMethods`, shared by `Model` and the new `Authenticatable` base class.
- Resolve the SoftDeletes/ModelEventMethods trait collision and let Eloquent boot model concerns once.
- Apply soft-delete scopes only when the model explicitly enables `$deleteMode = 'soft'` or `1`; ordinary authentication tables do not require `deleted_at`.
- Use Laravel's SoftDeletes implementation behind Saola lifecycle hooks and guards.
- Preserve model array/object casts during repository writes by reading `getCasts()`.
- Document the shared model protocol in `docs/MODELS.md` and add database-backed regression coverage.

## Compatibility

PHP ^8.2; Laravel ^12 or ^13 (the selected Laravel dependency may require a newer PHP).

## Validation

Composer validate and audit passed; 128 tests, 348 assertions. Consumer integration with Laravel 13 passed 48 tests and 228 assertions against this core source.

## Release scope

Applications using the new authentication base must install this version or newer. UUID and soft-delete columns remain application schema decisions; this release does not migrate consumer databases. Lifecycle `can*` methods are model guards and do not replace authorization by actor or policy. Existing release tags are preserved.
