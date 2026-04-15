<?php

namespace Saola\Core\Engines;

use Saola\Core\Contracts\OctaneCompatible;

/**
 * ViewEngine - Engine quản lý render view với context, module, blade, data
 */
class ViewEngine implements OctaneCompatible
{
    /**
     * @var ViewContextManager
     */
    protected $contextManager;

    /**
     * @var string Context hiện tại
     */
    protected $context = 'web';

    /**
     * @var string Module hiện tại
     */
    protected $module = '';

    /**
     * @var array Cache resolved paths (reset sau mỗi request)
     */
    protected $resolvedPaths = [];

    /**
     * Constructor
     * 
     * @param ViewContextManager|null $contextManager
     */
    public function __construct(?ViewContextManager $contextManager = null)
    {
        $this->contextManager = $contextManager ?? new ViewContextManager();
    }

    /**
     * Set context
     * 
     * @param string $context
     * @return $this
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        $this->resolvedPaths = []; // Clear cache
        return $this;
    }

    /**
     * Set module
     * 
     * @param string $module
     * @return $this
     */
    public function setModule(string $module): self
    {
        $this->module = $module;
        $this->resolvedPaths = []; // Clear cache
        return $this;
    }

    /**
     * Lấy context hiện tại
     * 
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Lấy module hiện tại
     * 
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Resolve view path từ blade name
     * 
     * @param string $blade Tên blade (vd: 'index', 'list', 'form')
     * @param string|null $type Loại view (modules, pages, components, layouts, templates)
     * @return string View path đã được resolve
     */
    public function resolvePath(string $blade, ?string $type = null): string
    {
        $cacheKey = "{$this->context}.{$this->module}.{$blade}.{$type}";
        
        if (isset($this->resolvedPaths[$cacheKey])) {
            return $this->resolvedPaths[$cacheKey];
        }

        // Nếu không có type, mặc định là modules
        if (!$type) {
            $type = 'modules';
        }

        // Lấy base directory từ context
        $baseDir = $this->contextManager->getBaseDirectory($this->context, $type);
        
        if (!$baseDir) {
            // Fallback về context mặc định
            $baseDir = $this->contextManager->getBaseDirectory(
                $this->contextManager->getDefaultContext(),
                $type
            ) ?? "{$this->context}.{$type}";
        }

        // Nếu có module, thêm vào path
        if ($this->module && $type === 'modules') {
            $path = "{$baseDir}.{$this->module}.{$blade}";
        } else {
            $path = "{$baseDir}.{$blade}";
        }

        $this->resolvedPaths[$cacheKey] = $path;
        return $path;
    }

    /**
     * Render view
     * 
     * @param string $blade Tên blade
     * @param array $data Dữ liệu truyền vào view
     * @param string|null $type Loại view (modules, pages, components, layouts, templates)
     * @return \Illuminate\Contracts\View\View
     */
    public function render(string $blade, array $data = [], ?string $type = null)
    {
        $viewPath = $this->resolvePath($blade, $type);

        // Chuẩn bị default view data
        $defaultData = $this->getDefaultViewData();
        
        // Merge data
        $viewData = array_merge($defaultData, $data);

        return view($viewPath, $viewData);
    }

    /**
     * Render module view
     * 
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderModule(string $blade, array $data = [])
    {
        return $this->render($blade, $data, 'modules');
    }

    /**
     * Render page view
     * 
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderPage(string $blade, array $data = [])
    {
        return $this->render($blade, $data, 'pages');
    }

    /**
     * Render component view
     * 
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderComponent(string $blade, array $data = [])
    {
        return $this->render($blade, $data, 'components');
    }

    /**
     * Render layout view
     * 
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderLayout(string $blade, array $data = [])
    {
        return $this->render($blade, $data, 'layouts');
    }

    /**
     * Render template view
     * 
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderTemplate(string $blade, array $data = [])
    {
        return $this->render($blade, $data, 'templates');
    }

    /**
     * Lấy default view data
     * Sử dụng các biến đã đăng ký trong context
     * 
     * @return array
     */
    protected function getDefaultViewData(): array
    {
        // Lấy variables từ context đã đăng ký
        $variables = $this->contextManager->getContextVariables($this->context);
        
        // Nếu không có variables, fallback về default
        if (!$variables) {
            $defaultContext = $this->contextManager->getDefaultContext();
            if ($defaultContext) {
                $variables = $this->contextManager->getContextVariables($defaultContext);
            }
        }

        // Nếu vẫn không có, tạo từ directories
        if (!$variables) {
            $baseDir = $this->contextManager->getBaseDirectory($this->context, 'modules');
            if (!$baseDir) {
                $baseDir = "{$this->context}.modules";
            }

            $basePath = rtrim(str_replace('.modules', '', $baseDir), '.');
            if (!$basePath) {
                $basePath = $this->context;
            }

            $variables = [
                '__system__' => '_system.',
                '__base__' => $basePath . '.',
                '__component__' => ($this->contextManager->getBaseDirectory($this->context, 'components') ?? "{$this->context}.components") . '.',
                '__template__' => ($this->contextManager->getBaseDirectory($this->context, 'templates') ?? "{$this->context}.templates") . '.',
                '__pagination__' => $basePath . '.pagination.',
                '__layout__' => ($this->contextManager->getBaseDirectory($this->context, 'layouts') ?? "{$this->context}.layouts") . '.',
                '__module__' => ($this->contextManager->getBaseDirectory($this->context, 'modules') ?? "{$this->context}.modules") . '.',
                '__page__' => ($this->contextManager->getBaseDirectory($this->context, 'pages') ?? "{$this->context}.pages") . '.',
            ];
        }

        // Thêm module và context info
        return array_merge($variables, [
            'module_slug' => $this->module,
            'context' => $this->context,
        ]);
    }

    /**
     * Lấy ViewContextManager
     * 
     * @return ViewContextManager
     */
    public function getContextManager(): ViewContextManager
    {
        return $this->contextManager;
    }

    /**
     * Reset trạng thái tĩnh (Octane compatibility)
     * 
     * @return void
     */
    public static function resetStaticState(): void
    {
        // Không có static properties
    }

    /**
     * Reset trạng thái instance (Octane compatibility)
     * 
     * @return void
     */
    public function resetInstanceState(): void
    {
        // Clear cache
        $this->resolvedPaths = [];
    }

    /**
     * Lấy danh sách static properties (Octane compatibility)
     * 
     * @return array
     */
    public static function getStaticProperties(): array
    {
        return [];
    }
}

