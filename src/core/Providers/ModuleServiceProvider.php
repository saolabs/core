<?php
namespace Saola\Core\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Điểm khai báo của một module.
 *
 * - `define()`: cây module trên route builder — route, và mọi thứ gắn vào
 *   module (`->menu()->list()->form()` của saola/admin, permission của route).
 * - `menus()`: mục menu không gắn với module nào của mình.
 * - `permissions()`: quyền không gắn với route nào.
 *
 * Cả ba đều tuỳ chọn. `routes()` là tên cũ của `define()`, vẫn chạy.
 *
 * Không có hook `views()`/`migrations()`: `loadViewsFrom()` và
 * `loadMigrationsFrom()` của Laravel đã làm đúng việc đó, gói lại chỉ thêm một
 * lớp vỏ.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Khai báo module trên route builder:
     * `System::context('admin')->module('users')->menu([...])->list([...])->form([...])->routes(fn ($m) => ...)`.
     */
    public function define(): void
    {
        $this->routes();
    }

    /** @deprecated Tên cũ của define(); module mới override define(). */
    public function routes()
    {
    }

    /**
     * Mục menu không suy ra được từ module — nhóm, link ngoài, trang của package khác.
     */
    public function menus(): void
    {
    }

    /**
     * Permission không gắn với route nào — quyền theo dữ liệu, quyền của job.
     * Permission khai báo trên route/module đã được System::permissions() gom.
     */
    public function permissions(): void
    {
    }

    public function boot()
    {
        $this->define();
        $this->menus();
        $this->permissions();
    }

}
