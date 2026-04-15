<?php

/**
 * ViewPathResolver - Ví Dụ Sử Dụng
 * 
 * File này minh họa các cách sử dụng ViewPathResolver
 */

namespace Examples;

use One\Core\Support\Methods\ViewMethods;
use One\Core\Engines\ViewPathResolver;

// ============================================
// Ví Dụ 1: Sử Dụng Cơ Bản
// ============================================

class BasicService
{
    use ViewMethods;

    public function __construct()
    {
        // Bật ViewPathResolver
        $this->useViewPathResolver(true);
        
        // Thiết lập context
        $this->context = 'web';
        
        // Khởi tạo view
        $this->initView();
    }

    public function renderHomePage()
    {
        // Sử dụng alias
        return $this->render('@page.home', [
            'title' => 'Home Page',
        ]);
    }

    public function renderModuleList()
    {
        // Sử dụng alias module
        return $this->render('@module.list', [
            'items' => [],
        ]);
    }
}

// ============================================
// Ví Dụ 2: Thay Đổi Base Directory
// ============================================

class AdminService
{
    use ViewMethods;

    public function __construct()
    {
        $this->context = 'admin';
        $this->useViewPathResolver(true);
        $this->initView();
    }

    public function switchToV2()
    {
        // Chuyển sang version 2 của admin views
        $this->setBaseDirectory('base', 'admin.v2', 100);
        $this->setBaseDirectory('module', 'admin.v2.modules', 90);
        $this->setBaseDirectory('page', 'admin.v2.pages', 80);
        
        // Re-init để sync
        $this->initView();
    }

    public function useCustomComponentPath()
    {
        // Sử dụng component path tùy chỉnh
        $this->setBaseDirectory('component', 'admin.shared.components', 100);
        $this->initView();
    }
}

// ============================================
// Ví Dụ 3: Multi-Tenant Support
// ============================================

class TenantService
{
    use ViewMethods;

    protected $tenant;

    public function __construct()
    {
        $this->useViewPathResolver(true);
    }

    public function setTenant(string $tenant)
    {
        $this->tenant = $tenant;
        
        // Mỗi tenant có base directory riêng
        $this->setBaseDirectory('base', "tenants.{$tenant}", 100);
        $this->setBaseDirectory('module', "tenants.{$tenant}.modules", 90);
        $this->setBaseDirectory('page', "tenants.{$tenant}.pages", 80);
        $this->setBaseDirectory('layout', "tenants.{$tenant}.layouts", 70);
        
        $this->initView();
    }

    public function renderTenantPage(string $page)
    {
        return $this->render("@page.{$page}", [
            'tenant' => $this->tenant,
        ]);
    }
}

// ============================================
// Ví Dụ 4: Theme Support
// ============================================

class ThemeService
{
    use ViewMethods;

    protected $theme;

    public function __construct()
    {
        $this->useViewPathResolver(true);
        $this->context = 'web';
    }

    public function setTheme(string $theme)
    {
        $this->theme = $theme;
        
        // Theme có priority cao để override base
        $this->setBaseDirectory('base', "themes.{$theme}", 150);
        $this->setBaseDirectory('layout', "themes.{$theme}.layouts", 140);
        $this->setBaseDirectory('component', "themes.{$theme}.components", 130);
        $this->setBaseDirectory('template', "themes.{$theme}.templates", 120);
        
        $this->initView();
    }

    public function renderThemedPage(string $page)
    {
        return $this->render("@page.{$page}", [
            'theme' => $this->theme,
        ]);
    }
}

// ============================================
// Ví Dụ 5: Sử Dụng setViewConfig
// ============================================

class ConfigurableService
{
    use ViewMethods;

    public function __construct()
    {
        $this->useViewPathResolver(true);
    }

    public function configureViews(array $config)
    {
        // Cấu hình tất cả base directories cùng lúc
        $this->setViewConfig([
            'context' => $config['context'] ?? 'web',
            'viewFolder' => $config['viewFolder'] ?? null,
            'baseDirectories' => [
                'base' => [
                    'path' => $config['basePath'] ?? 'web',
                    'priority' => 100,
                ],
                'module' => [
                    'path' => ($config['basePath'] ?? 'web') . '.modules',
                    'priority' => 90,
                ],
                'page' => [
                    'path' => ($config['basePath'] ?? 'web') . '.pages',
                    'priority' => 80,
                ],
                'component' => [
                    'path' => ($config['basePath'] ?? 'web') . '.components',
                    'priority' => 70,
                ],
            ],
        ]);
        
        $this->initView();
    }
}

// ============================================
// Ví Dụ 6: Truy Cập Trực Tiếp ViewPathResolver
// ============================================

class AdvancedService
{
    use ViewMethods;

    public function __construct()
    {
        $this->useViewPathResolver(true);
        $this->initView();
    }

    public function getResolverInfo()
    {
        $resolver = $this->getViewPathResolver();
        
        // Lấy tất cả base directories
        $allDirs = $resolver->getAllBaseDirectories();
        
        // Lấy base path cụ thể
        $basePath = $resolver->getBaseDirectory('base');
        $modulePath = $resolver->getBaseDirectory('module');
        
        // Resolve path thủ công
        $resolvedPath = $resolver->resolve('@module.index');
        
        // Lấy default view data
        $viewData = $resolver->getDefaultViewData([
            'module_slug' => $this->module,
            'module_name' => $this->moduleName,
        ]);
        
        return [
            'all_directories' => $allDirs,
            'base_path' => $basePath,
            'module_path' => $modulePath,
            'resolved_path' => $resolvedPath,
            'view_data' => $viewData,
        ];
    }

    public function addCustomDirectory()
    {
        $resolver = $this->getViewPathResolver();
        
        // Thêm base directory tùy chỉnh
        $resolver->setBaseDirectory('widget', 'web.widgets', 60);
        
        // Sử dụng trong render
        return $this->render('@widget.sidebar', []);
    }
}

// ============================================
// Ví Dụ 7: Dynamic Context Switching
// ============================================

class DynamicContextService
{
    use ViewMethods;

    public function __construct()
    {
        $this->useViewPathResolver(true);
    }

    public function switchContext(string $context)
    {
        // Thay đổi context
        $this->context = $context;
        
        // Cập nhật resolver
        $resolver = $this->getViewPathResolver();
        $resolver->setContext($context);
        
        // Re-init
        $this->initView();
    }

    public function renderForContext(string $context, string $view)
    {
        // Lưu context hiện tại
        $oldContext = $this->context;
        
        // Chuyển context
        $this->switchContext($context);
        
        // Render
        $result = $this->render($view, []);
        
        // Khôi phục context
        $this->switchContext($oldContext);
        
        return $result;
    }
}

// ============================================
// Ví Dụ 8: Package/Module Mode
// ============================================

class PackageService
{
    use ViewMethods;

    protected $package = 'my-package';

    public function __construct()
    {
        $this->useViewPathResolver(true);
        $this->mode = 'package';
        $this->initView();
    }

    public function renderPackageView(string $view)
    {
        // ViewPathResolver vẫn hoạt động với package mode
        $resolver = $this->getViewPathResolver();
        $resolvedPath = $resolver->resolve("@module.{$view}");
        
        // Sử dụng với package prefix
        return $this->render("{$this->package}:{$resolvedPath}", []);
    }
}

// ============================================
// Ví Dụ 9: Octane Compatibility
// ============================================

class OctaneAwareService
{
    use ViewMethods;

    public function __construct()
    {
        $this->useViewPathResolver(true);
        $this->initView();
    }

    public function handleRequest()
    {
        // ViewPathResolver tự động reset sau mỗi request trong Octane
        // Không cần lo lắng về state leak
        
        return $this->render('@page.index', []);
    }

    public function resetState()
    {
        // Nếu cần reset thủ công (thường không cần)
        $this->resetViewState();
    }
}

// ============================================
// Ví Dụ 10: Hybrid Usage (Cũ + Mới)
// ============================================

class HybridService
{
    use ViewMethods;

    public function __construct()
    {
        // Có thể không bật ViewPathResolver, code cũ vẫn hoạt động
        // $this->useViewPathResolver(true);
        
        $this->context = 'web';
        $this->initView();
    }

    public function renderOldWay()
    {
        // Sử dụng cách cũ
        return $this->render('index', []);
    }

    public function renderNewWay()
    {
        // Bật ViewPathResolver khi cần
        $this->useViewPathResolver(true);
        $this->initView();
        
        // Sử dụng alias
        return $this->render('@module.index', []);
    }
}

