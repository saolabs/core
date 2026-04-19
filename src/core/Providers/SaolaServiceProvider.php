<?php
namespace Saola\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\View\Services\ViewHelperService;
use Saola\Core\View\Services\ViewStorageManager;

class SaolaServiceProvider extends ServiceProvider
{
    protected const CONFIG_NAMESPACES = ['sao', 'saola'];

    /**
     * Đăng ký service, helper và repository nếu môi trường cho phép.
     */
    public function register()
    {
        if (!$this->app) {
            return;
        }

        $this->registerConfigAliases();

        // Đăng ký ViewContextManager như singleton
        // Đảm bảo contexts được giữ lại giữa các requests trong Octane
        // Contexts có thể được cập nhật động (ví dụ: khi admin đổi theme)
        $this->app->singleton(ViewContextManager::class, function ($app) {
            return new ViewContextManager();
        });

        $this->app->singleton(ViewStorageManager::class, function ($app) {
            return new ViewStorageManager();
        });

        $this->app->singleton(ViewHelperService::class, function ($app) {
            return new ViewHelperService($app->make(ViewStorageManager::class));
        });

        $this->app->register(ViewContextServiceProvider::class);

        // Đăng ký OctaneServiceProvider nếu Laravel Octane được phát hiện
        // Nếu project có cài Laravel Octane thì tự động đăng ký provider OctaneServiceProvider để hỗ trợ Octane.
        if (class_exists('Laravel\Octane\Octane')) {
            $this->app->register(OctaneServiceProvider::class);
        }

        // Bind repository vào container (nếu Laravel đang chạy)
        if ($this->app->bound('config')) {
            // $this->app->bind(
            //     \Saola\Core\Contracts\UserRepositoryInterface::class,
            //     \Saola\Core\epositories\UserRepository::class
            // );
        }
    }

    /**
        * Boot các thành phần của Saola.
     */
    public function boot()
    {
        if (!$this->app) {
            return;
        }

        // Load translations nếu có
        // Cho phép package sử dụng các file ngôn ngữ (lang) của ứng dụng nếu có.
        if (is_dir(base_path('resources/lang'))) {
            $this->loadTranslationsFrom(base_path('resources/lang'), 'saola');
        }

        // Load views nếu có
        // Cho phép package sử dụng các file view của ứng dụng nếu có.
        if (is_dir(base_path('resources/views'))) {
            $this->loadViewsFrom(base_path('resources/views'), 'saola');
        }

        if (!$this->app->runningInConsole()) {
            return;
        }

        // Load migrations từ thư viện
        // Cho phép Laravel tự động nhận diện và chạy các file migration của package khi chạy artisan migrate.
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Load migrations từ ứng dụng nếu có (backward compatibility)
        // Nếu thư mục migrations của app tồn tại thì cũng nạp migrations ở đó (giữ tương thích cũ hoặc cho phép override migrations).
        if (is_dir(base_path('database/migrations'))) {
            $this->loadMigrationsFrom(base_path('database/migrations'));
        }

        // Register console commands
        // Đăng ký command tùy chỉnh cho artisan.
        $this->commands([
            \Saola\Core\Console\Commands\PublishSaolaMigrationsCommand::class,
        ]);

        // Publish config file
        // Cho phép người dùng copy file cấu hình mặc định của package ra thư mục config của app để tùy chỉnh.
        $this->publishes([
            $this->resolvePackageConfigPath() => config_path('saola.php'),
        ], 'saola-config');

        // Publish migrations
        // Cho phép người dùng copy các file migration của package ra thư mục database/migrations của app để tùy chỉnh.
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'saola-migrations');
    }

    protected function registerConfigAliases(): void
    {
        if (!$this->app->bound('config')) {
            return;
        }

        $config = $this->app['config'];
        $mergedConfig = $this->loadPackageConfig();

        foreach (self::CONFIG_NAMESPACES as $namespace) {
            $existingConfig = $config->get($namespace, []);
            if (is_array($existingConfig) && $existingConfig !== []) {
                $mergedConfig = array_replace_recursive($mergedConfig, $existingConfig);
            }
        }

        foreach (self::CONFIG_NAMESPACES as $namespace) {
            $config->set($namespace, $mergedConfig);
        }
    }

    protected function loadPackageConfig(): array
    {
        $config = require $this->resolvePackageConfigPath();

        return is_array($config) ? $config : [];
    }

    protected function resolvePackageConfigPath(): string
    {
        foreach (['saola.php', 'sao.php'] as $file) {
            $path = __DIR__ . '/../../config/' . $file;

            if (is_file($path)) {
                return $path;
            }
        }

        return __DIR__ . '/../../config/sao.php';
    }
}