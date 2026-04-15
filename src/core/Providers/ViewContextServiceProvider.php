<?php

namespace Saola\Core\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Saola\Core\View\Services\ViewContextService;
use Saola\Core\View\Services\ViewHelperService;
use Saola\Core\Engines\ViewContextManager;

/**
 * ViewContextServiceProvider
 * 
 * Quản lý View Context Engine sử dụng ViewContextManager từ onelaravel/core
 * Đăng ký các contexts (admin, web, api) và inject variables vào views
 */
class ViewContextServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Đăng ký ViewContextService
        $this->app->singleton(ViewContextService::class, fn() => new ViewContextService());
        
        // Đăng ký ViewContextManager như singleton
        $this->app->singleton(ViewContextManager::class, fn() => new ViewContextManager());
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        $viewContextManager = $this->app->make(ViewContextManager::class);
        
        // Đăng ký các contexts với directories và variables
        $this->registerContexts($viewContextManager);
        
        // Đăng ký View Composer để inject variables vào views
        $this->registerViewComposer($viewContextManager);
    }

    /**
     * Đăng ký các contexts (admin, web, api)
     * 
     * @param ViewContextManager $manager
     * @return void
     */
    protected function registerContexts(ViewContextManager $manager): void
    {
        // Đăng ký Web Context
        $manager->registerContext('web', [
            'base' => 'web',
            'components' => 'web.components',
            'templates' => 'web.templates',
            'partials' => 'web.partials',
            'modules' => 'web.modules',
            'layouts' => 'web.layouts',
            'pages' => 'web.pages',
        ]);

        // Đăng ký Admin Context
        $manager->registerContext('admin', [
            'base' => 'admin',
            'components' => 'admin.components',
            'partials' => 'admin.partials',
            'modules' => 'admin.modules',
            'layouts' => 'admin.layouts',
            'templates' => 'admin.templates',
            'pages' => 'admin.pages',
        ]);


        // Set default context là 'web'
        $manager->setDefaultContext('web');
    }

    /**
     * Đăng ký View Composer
     * 
     * Logic cũ được giữ nguyên, ViewContextManager chỉ bổ sung thêm variables
     * 
     * @param ViewContextManager $manager
     * @return void
     */
    protected function registerViewComposer(ViewContextManager $manager): void
    {
        // ===== COMPOSER 1: Logic cũ (ViewHelperService) - GIỮ NGUYÊN =====
        View::composer('*', function ($view) {
            $viewName = $view->getName();
            $viewId = uniqid();
            $helper = app(ViewHelperService::class);
            
            $helper->registerView($viewName, $viewId);

            // ===== CÁC CÁCH LẤY DATA TRONG VIEW COMPOSER =====
            $cps = explode('.', $viewName);
            $FILE = array_pop($cps);
            $__VIEW_NAMESPACE__ = count($cps) > 0 ? implode('.', $cps) . '.' : '';
            // 1. Lấy data từ controller: view('test', ['user' => []])
            $viewData = $view->getData();
            $parentViewName = $viewData['__PARENT_VIEW_PATH__'] ?? null;
            $parentViewId = $viewData['__PARENT_VIEW_ID__'] ?? null;
            $parentName = $viewData['__PARENT_VIEW_NAME__'] ?? null;
            if ($parentViewName && $parentViewId) {
                $helper->setParentView($viewName, $viewId, $parentViewName, $parentViewId);
                $helper->addChildrenView($parentViewName, $parentViewId, $viewName, $viewId);
            }
        
            // Set biến cơ bản (logic cũ - GIỮ NGUYÊN)
            $view->with([
                '__VIEW_ID__' => $viewId,
                '__VIEW_PATH__' => $viewName,
                '__VIEW_NAME__' => $viewName,
                '__VIEW_NAMESPACE__' => $__VIEW_NAMESPACE__,
                '__VIEW_TYPE__' => 'view',
                '__VIEW_SUBSCRIBE_INDEX__' => 0,
                '__VIEW_INCLUDE_INDEX__' => 0,
                '__VIEW_INCLUDEIF_INDEX__' => 0,
                '__VIEW_INCLUDEWHEN_INDEX__' => 0,
                '__PARENT_NAME__' => $parentName,
                '__PARENT_VIEW_NAME__' => $viewName,
                '__PARENT_VIEW_PATH__' => $viewName,
                '__PARENT_VIEW_ID__' => $viewId,
            ]);
        });

    
    }

    /**
     * Xác định context từ view path
     * 
     * @param string $viewPath
     * @return string
     */
    protected function detectContextFromViewPath(string $viewPath): string
    {
        // Kiểm tra view path có chứa context không
        if (str_starts_with($viewPath, 'admin.')) {
            return 'admin';
        }
        
        if (str_starts_with($viewPath, 'web.')) {
            return 'web';
        }
        
        if (str_starts_with($viewPath, 'api.')) {
            return 'api';
        }
        
        // Kiểm tra từ request route
        $request = request();
        if ($request) {
            $route = $request->route();
            if ($route) {
                $uri = $request->getRequestUri();
                if (str_starts_with($uri, '/admin')) {
                    return 'admin';
                }
                if (str_starts_with($uri, '/api')) {
                    return 'api';
                }
            }
        }
        
        // Default context
        return 'web';
    }
}