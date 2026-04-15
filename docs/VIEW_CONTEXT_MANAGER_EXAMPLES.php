<?php

/**
 * View Context Manager - Ví Dụ Đăng Ký Context
 * 
 * File này minh họa cách đăng ký context từ phía web (application)
 */

// ============================================
// Ví Dụ 1: Đăng Ký Context Cơ Bản
// ============================================

// Trong AppServiceProvider::boot()

use One\Core\Engines\ViewContextManager;

$contextManager = app(ViewContextManager::class);

// Đăng ký context 'admin'
$contextManager->registerContext('admin', [
    'directories' => [
        'components' => 'admin.components',
        'modules' => 'admin.modules',
        'layouts' => 'admin.layouts',
        'templates' => 'admin.templates',
        'pages' => 'admin.pages',
    ],
]);

// ============================================
// Ví Dụ 2: Đăng Ký Với Variables Tùy Chỉnh
// ============================================

$contextManager->registerContext('web', [
    'directories' => [
        'components' => 'web.components',
        'modules' => 'web.modules',
        'layouts' => 'web.layouts',
        'templates' => 'web.templates',
        'pages' => 'web.pages',
    ],
    'variables' => [
        '__system__' => '_system.',
        '__base__' => 'web.',
        '__component__' => 'web.components.',
        '__template__' => 'web.templates.',
        '__pagination__' => 'web.pagination.',
        '__layout__' => 'web.layouts.',
        '__module__' => 'web.modules.',
        '__page__' => 'web.pages.',
        // Có thể thêm variables tùy chỉnh
        '__widget__' => 'web.widgets.',
        '__partial__' => 'web.partials.',
    ],
]);

// ============================================
// Ví Dụ 3: Context Với Cấu Trúc Phức Tạp
// ============================================

$contextManager->registerContext('api', [
    'directories' => [
        'components' => 'api.shared.components',
        'modules' => 'api.v1.modules',
        'layouts' => 'api.layouts',
        'templates' => 'api.v1.templates',
        'pages' => 'api.pages',
    ],
    'variables' => [
        '__component__' => 'api.shared.components.',
        '__module__' => 'api.v1.modules.',
        '__layout__' => 'api.layouts.',
        '__template__' => 'api.v1.templates.',
        '__page__' => 'api.pages.',
        '__base__' => 'api.',
        '__version__' => 'v1.',
    ],
]);

// ============================================
// Ví Dụ 4: Multi-Tenant Context
// ============================================

// Đăng ký context động cho từng tenant
foreach ($tenants as $tenant) {
    $contextManager->registerContext("tenant.{$tenant}", [
        'directories' => [
            'components' => "tenants.{$tenant}.components",
            'modules' => "tenants.{$tenant}.modules",
            'layouts' => "tenants.{$tenant}.layouts",
            'templates' => "tenants.{$tenant}.templates",
            'pages' => "tenants.{$tenant}.pages",
        ],
    ]);
}

// ============================================
// Ví Dụ 5: Cập Nhật Variables Sau Khi Đăng Ký
// ============================================

// Đăng ký context
$contextManager->registerContext('admin', [
    'directories' => [
        'components' => 'admin.components',
        'modules' => 'admin.modules',
        'layouts' => 'admin.layouts',
        'templates' => 'admin.templates',
        'pages' => 'admin.pages',
    ],
]);

// Sau đó cập nhật thêm variables
$contextManager->updateContextVariables('admin', [
    '__widget__' => 'admin.widgets.',
    '__modal__' => 'admin.modals.',
    '__form__' => 'admin.forms.',
]);

// ============================================
// Ví Dụ 6: Sử Dụng Trong Service
// ============================================

class UserService extends Service
{
    use ViewMethods;

    protected $context = 'admin';  // Context đã được đăng ký
    protected $module = 'users';

    public function __construct()
    {
        parent::__construct();
        $this->initView();
    }

    public function index()
    {
        // Render: admin.modules.users.index
        // Variables như __component__, __module__ sẽ tự động có sẵn
        return $this->render('index', [
            'users' => $this->getUsers(),
        ]);
    }
}

// ============================================
// Ví Dụ 7: Lấy Context Manager Từ Service
// ============================================

class SomeService extends Service
{
    use ViewMethods;

    public function registerCustomContext()
    {
        $contextManager = $this->getViewContextManager();
        
        $contextManager->registerContext('custom', [
            'directories' => [
                'components' => 'custom.components',
                'modules' => 'custom.modules',
                'layouts' => 'custom.layouts',
                'templates' => 'custom.templates',
                'pages' => 'custom.pages',
            ],
        ]);
    }
}

// ============================================
// Ví Dụ 8: Kiểm Tra Context
// ============================================

$contextManager = app(ViewContextManager::class);

// Kiểm tra context có tồn tại không
if ($contextManager->hasContext('admin')) {
    // Lấy directories
    $directories = $contextManager->getContextDirectories('admin');
    
    // Lấy variables
    $variables = $contextManager->getContextVariables('admin');
    
    // Lấy một variable cụ thể
    $componentPath = $contextManager->getContextVariable('admin', '__component__');
    
    // Lấy toàn bộ config
    $config = $contextManager->getContextConfig('admin');
}

// ============================================
// Ví Dụ 9: Đăng Ký Trong Config File
// ============================================

// config/view-contexts.php
return [
    'admin' => [
        'directories' => [
            'components' => 'admin.components',
            'modules' => 'admin.modules',
            'layouts' => 'admin.layouts',
            'templates' => 'admin.templates',
            'pages' => 'admin.pages',
        ],
        'variables' => [
            '__component__' => 'admin.components.',
            '__module__' => 'admin.modules.',
            '__layout__' => 'admin.layouts.',
            '__base__' => 'admin.',
        ],
    ],
    'web' => [
        'directories' => [
            'components' => 'web.components',
            'modules' => 'web.modules',
            'layouts' => 'web.layouts',
            'templates' => 'web.templates',
            'pages' => 'web.pages',
        ],
    ],
];

// Trong AppServiceProvider
$contexts = config('view-contexts', []);
$contextManager = app(ViewContextManager::class);

foreach ($contexts as $name => $config) {
    $contextManager->registerContext($name, $config);
}

