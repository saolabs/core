<?php
namespace Saola\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Saola\Core\Routing\Context;
use Saola\Core\System;

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
 * 
 * 
 * @method void admin(Context $context) khai báo các routes hoặc menu, list hoặc form cho các modules của admin
 * @method void web(Context $context) khai báo các routes cho các modules của web
 * @method void api(Context $context) khai báo các routes cho các modules của api
 * @method void mobile(Context $context) khai báo các routes cho các modules của mobile
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

        if(method_exists($this, 'admin')){
            $context = System::context('admin');
            $this->admin($context);
        }

        if(method_exists($this, 'web')){
            $context = System::context('web');
            $this->web($context);
        }

        if(method_exists($this, 'api')){
            $context = System::context('api');
            $this->api($context);
        }

        if(method_exists($this, 'mobile')){
            $context = System::context('mobile');
            $this->mobile($context);
        }
        
    }

}
