<?php
namespace Saola\Core\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Điểm khai báo của một module.
 *
 * `routes()` từng là hook DUY NHẤT, nên mọi thứ phi-routing của module phải
 * nhét vào route attribute mới có chỗ sống. Hai hook dưới đây cho module khai
 * báo phần không suy ra được từ cây route; cả hai đều tuỳ chọn nên module cũ
 * không phải đổi gì.
 *
 * Không có hook `views()`/`migrations()`: `loadViewsFrom()` và
 * `loadMigrationsFrom()` của Laravel đã làm đúng việc đó, gói lại chỉ thêm một
 * lớp vỏ.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Được giữ lại để tương thích ngược.
     * Mọi logic đã được hợp nhất vào SaolaServiceProvider.
     */
    abstract function routes();

    /**
     * Mục menu không suy ra được từ route — link ngoài, trang của package khác.
     * Menu của chính các module đã tự sinh từ cây, xem System::menu().
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
        $this->routes();
        $this->menus();
        $this->permissions();
    }

}
