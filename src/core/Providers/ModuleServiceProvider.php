<?php
namespace Saola\Core\Providers;

use Closure;
use Illuminate\Support\ServiceProvider;
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Được giữ lại để tương thích ngược.
     * Mọi logic đã được hợp nhất vào SaolaServiceProvider.
     */
    abstract function routes();

    public function boot()
    {
        $this->routes();
    }

}