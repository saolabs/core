<?php

namespace Saola\Core\Engines;

use Saola\Core\Contracts\OctaneCompatible;

/**
 * ViewContextManager - Quản lý các view context (admin, web, ...)
 * 
 * Mỗi context có các base directories:
 * - components
 * - modules
 * - layouts
 * - templates
 * - pages
 */
class ViewContextManager implements OctaneCompatible
{
    /**
     * @var array Danh sách các context và cấu hình của chúng
     * Format: [
     *   'admin' => [
     *     'directories' => [
     *       'components' => 'admin.components',
     *       'modules' => 'admin.modules',
     *       'layouts' => 'admin.layouts',
     *       'templates' => 'admin.templates',
     *       'pages' => 'admin.pages',
     *     ],
     *     'variables' => [
     *       '__component__' => 'admin.components.',
     *       '__module__' => 'admin.modules.',
     *       '__layout__' => 'admin.layouts.',
     *       '__template__' => 'admin.templates.',
     *       '__page__' => 'admin.pages.',
     *       '__base__' => 'admin.',
     *     ],
     *   ],
     *   ...
     * ]
     */
    protected $contexts = [];

    /**
     * @var string Context mặc định
     */
    protected $defaultContext = '';

    /**
     * @var array Shared data cho mỗi context
     * Format: [
     *   'admin' => ['key' => 'value', ...],
     *   'web' => ['key' => 'value', ...],
     * ]
     */
    protected $sharedData = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Không tự động đăng ký context
        // Phía web sẽ tự đăng ký
    }

    /**
     * Đăng ký một context mới
     * 
     * @param string $name Tên context
     * @param array $directories Các base directories
     *   - 'base': Base path (bắt buộc để suy diễn variables)
     *   - 'components', 'modules', 'layouts', 'templates', 'pages': Optional
     * @param array|null $variables Các biến đại diện cho các dir (optional)
     *   Nếu null, sẽ tự động suy diễn từ Directories['base']
     * @return $this
     */
    public function registerContext(string $name, array $directories, ?array $variables = null): self
    {
        $basePath = $directories['base'] ?? $name;
        $basePath = rtrim($basePath, '.');

        // Tạo variables mặc định từ base
        $defaultVariables = [
            '__system__' => '_system.',
            '__base__' => $basePath . '.',
            '__component__' => ($directories['components'] ?? "{$basePath}.components") . '.',
            '__template__' => ($directories['templates'] ?? "{$basePath}.templates") . '.',
            '__partial__' => ($directories['partials'] ?? "{$basePath}.partials") . '.',
            // '__pagination__' => $basePath . '.pagination.',
            '__layout__' => ($directories['layouts'] ?? "{$basePath}.layouts") . '.',
            '__module__' => ($directories['modules'] ?? "{$basePath}.modules") . '.',
            '__page__' => ($directories['pages'] ?? "{$basePath}.pages") . '.',
        ];
        // Tự động tạo variables từ directories['base'] nếu không có
        if ($variables === null && !empty($directories)) {
            $variables = $defaultVariables;
        } else {
            // Merge với variables mặc định (giữ lại các variables tùy chỉnh nếu có)
            $variables = array_merge($defaultVariables, $variables);
        }
        if (isset($this->contexts[$name])) {
            // Nếu context đã tồn tại, merge directories và variables
            $this->contexts[$name]['directories'] = array_merge(
                $this->contexts[$name]['directories'] ?? [],
                $directories
            );
            $this->contexts[$name]['variables'] = array_merge(
                $this->contexts[$name]['variables'] ?? [],
                $variables
            );
        } else {
            // Nếu context chưa tồn tại, tạo mới
            $this->contexts[$name] = [
                'directories' => $directories,
                'variables' => $variables ?? [],
                'routeViews' => [], // Lưu cache route => view path nếu cần
            ];
        }
        // Set context đầu tiên làm mặc định nếu chưa có
        if (!$this->defaultContext) {
            $this->defaultContext = $name;
        }

        return $this;
    }

    public function registerContextViewByRoute(string $context, string $route, string $viewPath, ?string $shortcut = null): self
    {
        if (isset($this->contexts[$context])) {
            $this->contexts[$context]['routeViews'][$route] = [
                'view' => $viewPath,
                'shortcut' => $shortcut,
            ];
        }
        return $this;
    } 

    public function getViewPathByRoute(string $context, string $route, string $type = 'view'): ?string
    {
        if (!isset($this->contexts[$context]) || !isset($this->contexts[$context]['routeViews'][$route])) {
            return null;
        }
        if($type === 'shortcut' && isset($this->contexts[$context]['routeViews'][$route]['shortcut'])) {
            return $this->contexts[$context]['routeViews'][$route]['shortcut'];
        }
        return $this->contexts[$context]['routeViews'][$route]['view'] ?? null;
    }

    /**
     * Lấy base directory của một context
     * 
     * @param string $context Tên context
     * @param string $type Loại directory (base, components, modules, layouts, templates, pages)
     * @return string|null
     */
    public function getBaseDirectory(string $context, string $type): ?string
    {
        return $this->contexts[$context]['directories'][$type] ?? null;
    }

    /**
     * Lấy tất cả base directories của một context
     * 
     * @param string $context Tên context
     * @return array|null
     */
    public function getContextDirectories(string $context): ?array
    {
        return $this->contexts[$context]['directories'] ?? null;
    }

    /**
     * Lấy các biến đại diện (variables) của một context
     * 
     * @param string $context Tên context
     * @return array|null
     */
    public function getContextVariables(string $context): ?array
    {
        return $this->contexts[$context]['variables'] ?? null;
    }

    /**
     * Lấy một biến cụ thể của context
     * 
     * @param string $context Tên context
     * @param string $variable Tên biến (vd: '__component__', '__module__')
     * @return string|null
     */
    public function getContextVariable(string $context, string $variable): ?string
    {
        return $this->contexts[$context]['variables'][$variable] ?? null;
    }

    /**
     * Lấy toàn bộ cấu hình của một context
     * 
     * @param string $context Tên context
     * @return array|null
     */
    public function getContextConfig(string $context): ?array
    {
        return $this->contexts[$context] ?? null;
    }

    /**
     * Kiểm tra context có tồn tại không
     * 
     * @param string $context Tên context
     * @return bool
     */
    public function hasContext(string $context): bool
    {
        return isset($this->contexts[$context]);
    }

    /**
     * Lấy tất cả contexts
     * 
     * @return array
     */
    public function getAllContexts(): array
    {
        return array_keys($this->contexts);
    }

    /**
     * Set context mặc định
     * 
     * @param string $context
     * @return $this
     */
    public function setDefaultContext(string $context): self
    {
        $this->defaultContext = $context;
        return $this;
    }

    /**
     * Lấy context mặc định
     * 
     * @return string
     */
    public function getDefaultContext(): string
    {
        return $this->defaultContext;
    }

    /**
     * Cập nhật variables của một context
     * 
     * @param string $context Tên context
     * @param array $variables Các biến mới
     * @return $this
     */
    public function updateContextVariables(string $context, array $variables): self
    {
        if (isset($this->contexts[$context])) {
            $this->contexts[$context]['variables'] = array_merge(
                $this->contexts[$context]['variables'] ?? [],
                $variables
            );
        }
        return $this;
    }

    /**
     * Cập nhật directories của một context
     * 
     * @param string $context Tên context
     * @param array $directories Các directories mới
     * @param array|null $variables Variables mới (optional)
     *   Nếu null, sẽ tự động suy diễn từ Directories['base']
     * @return $this
     */
    public function updateContextDirectories(string $context, array $directories, ?array $variables = null): self
    {
        if (isset($this->contexts[$context])) {
            $this->contexts[$context]['directories'] = array_merge(
                $this->contexts[$context]['directories'] ?? [],
                $directories
            );

            // Tự động cập nhật variables từ directories['base'] nếu không có
            if ($variables === null) {
                $this->regenerateVariablesFromDirectories($context);
            } else {
                $this->contexts[$context]['variables'] = array_merge(
                    $this->contexts[$context]['variables'] ?? [],
                    $variables
                );
            }
        }
        return $this;
    }

    /**
     * Cập nhật toàn bộ context (directories và variables)
     * 
     * @param string $context Tên context
     * @param array $directories Các directories mới
     * @param array|null $variables Variables mới (optional)
     *   Nếu null, sẽ tự động suy diễn từ Directories['base']
     * @return $this
     */
    public function updateContext(string $context, array $directories, ?array $variables = null): self
    {
        if (isset($this->contexts[$context])) {
            $this->updateContextDirectories($context, $directories, $variables);
        }
        return $this;
    }

    /**
     * Tái tạo variables từ directories['base']
     * 
     * @param string $context Tên context
     * @return void
     */
    protected function regenerateVariablesFromDirectories(string $context): void
    {
        if (!isset($this->contexts[$context])) {
            return;
        }

        $directories = $this->contexts[$context]['directories'];
        $basePath = $directories['base'] ?? $context;
        $basePath = rtrim($basePath, '.');

        // Tạo variables mới từ directories['base']
        $newVariables = [
            '__system__' => '_system.',
            '__base__' => $basePath . '.',
            '__component__' => ($directories['components'] ?? "{$basePath}.components") . '.',
            '__template__' => ($directories['templates'] ?? "{$basePath}.templates") . '.',
            '__partial__' => ($directories['partials'] ?? "{$basePath}.partials") . '.',
            // '__pagination__' => $basePath . '.pagination.',
            '__layout__' => ($directories['layouts'] ?? "{$basePath}.layouts") . '.',
            '__module__' => ($directories['modules'] ?? "{$basePath}.modules") . '.',
            '__page__' => ($directories['pages'] ?? "{$basePath}.pages") . '.',
        ];

        // Merge với variables hiện có (giữ lại các variables tùy chỉnh)
        $this->contexts[$context]['variables'] = array_merge(
            $newVariables,
            $this->contexts[$context]['variables'] ?? []
        );
    }

    /**
     * Resolve view path từ context, module, blade, type
     * 
     * Logic:
     * - Nếu type rỗng hoặc 'base' → render từ base: context.blade
     * - Nếu type = 'modules' và module rỗng → render từ base: context.blade
     * - Nếu type = 'modules' và module không rỗng → render module: context.modules.module.blade
     * - Nếu type khác (pages, components, layouts, templates) → render từ type: context.type.blade
     * - Nếu type = 'route' → render từ đã 
     * 
     * @param string $context Tên context
     * @param string $module Tên module (có thể rỗng để render từ base)
     * @param string $blade Tên blade
     * @param string $type Loại view (base, modules, pages, components, layouts, templates, route, hoặc '' để render từ base)
     * @return string View path đã được resolve
     */
    public function resolvePath(string $context, string $module, string $blade, string $type = ''): string
    {
        // Danh sách type hợp lệ
        $validTypes = ['', 'base', 'modules', 'pages', 'components', 'partials', 'layouts', 'templates', 'route'];

        // Validate và normalize type
        if (!in_array($type, $validTypes, true)) {
            $type = '';
        }
        if($type === 'route') {
            return $blade;
        }
        // Lấy base directory của context (dùng nhiều lần nên cache lại)
        $base = $this->getBaseDirectory($context, 'base') ?? $context;

        // Trường hợp 1: Type rỗng hoặc 'base' → render từ base
        if (empty($type) || $type === 'base') {
            return "{$base}.{$blade}";
        }

        // Trường hợp 2: Type = 'modules' nhưng không có module → render từ base
        if ($type === 'modules' && empty($module)) {
            return "{$base}.{$blade}";
        }

        // Trường hợp 3: Lấy directory cho type từ context
        $baseDir = $this->getBaseDirectory($context, $type);

        // Nếu không có directory cho type → fallback về base.type
        if (!$baseDir) {
            $baseDir = "{$base}.{$type}";
        }

        // Trường hợp 4: Type = 'modules' và có module → render module view
        if ($type === 'modules' && $module) {
            return "{$baseDir}.{$module}.{$blade}";
        }

        // Trường hợp 5: Các type khác (pages, components, layouts, templates) → render từ type directory
        return "{$baseDir}.{$blade}";
    }

    /**
     * Resolve view path từ alias
     * 
     * @param string $context Tên context
     * @param string $module Tên module (có thể rỗng để render từ base)
     * @param string $blade Tên blade
     * @return string View path đã được resolve
     * @example @module.index => {modulePath}index
     * @example @page.about => {pagePath}about
     * @example @base.home => {basePath}home
     * @example @component.button => {componentPath}button
     * @example @layout.main => {layoutPath}main
     * @example @template.default => {templatePath}default
     * @example @pagination.default => {paginationPath}default
     * @example @pagination.default => {paginationPath}default
     */
    public function resolvePathByAlias(string $context, string $module, string $blade): string
    {
        if (preg_match('/^@([a-zA-Z0-9_]+)([\.\:])(.+)$/i', $blade, $matches)) {
            $type = strtolower($matches[1]);
            if (in_array($type, ['module', 'page', 'base', 'component', 'partial', 'layout', 'template'], true)) {
                $type .= 's';
            }
            $bladeName = $matches[3];

            return $this->resolvePath($context, $module, $bladeName, $type);
        }

        return $this->resolvePath($context, $module, $blade, '');
    }

    public function resolvePathByRoute(string $context, string $route): string
    {
        $parts = explode('.', $route);
        $count = count($parts);


        if ($count < 2) {
            // Nếu route không có đủ phần (ít nhất phải có context và blade), fallback về base
            return '';
        }
        $ctxRoute = array_shift($parts);
        $blade = array_pop($parts);
        if ($ctxRoute !== $context) {
            // Nếu context trong route không khớp với context hiện tại, fallback về base
            return '';
        }
        if ($count === 2) {
            if (view()->exists($path = $this->resolvePath($context, '', $blade, 'pages'))) {
                return '@PAGE:' . $blade;
            }
            if (view()->exists($path = $this->resolvePath($context, '', $blade, 'base'))) {
                return '@BASE:' . $blade;
            }
            return '';
        }

        $module = implode('.', $parts);

        if (view()->exists($path = $this->resolvePath($context, $module, $blade, 'modules'))) {
            return '@MODULE:' . ($module . '.' . $blade);
        }

        $p = $module . '.' . $blade;
        if (view()->exists($path = $this->resolvePath($context, '', $p, 'pages'))) {
            return '@PAGE:' . $p;
        }
        if (view()->exists($path = $this->resolvePath($context, '', $p, 'base'))) {
            return '@BASE:' . $p;
        }
        return '';
    }


    public function routeToViewPathConfig(string $context, string $route): array
    {
        $parts = explode('.', $route);
        $count = count($parts);


        if ($count < 2) {
            // Nếu route không có đủ phần (ít nhất phải có context và blade), fallback về base
            return [];
        }
        $ctxRoute = array_shift($parts);
        $blade = array_pop($parts);
        if ($ctxRoute !== $context) {
            // Nếu context trong route không khớp với context hiện tại, fallback về base
            return [];
        }
        if ($count === 2) {
            if (view()->exists($path = $this->resolvePath($context, '', $blade, 'pages'))) {
                return [
                    'shortcut' => '@PAGE:' . $blade,
                    'view' => $path,
                ];
            }
            if (view()->exists($path = $this->resolvePath($context, '', $blade, 'base'))) {
                return [
                    'shortcut' => '@BASE:' . $blade,
                    'view' => $path,
                ];
            }
            return [];
        }

        $module = implode('.', $parts);

        if (view()->exists($path = $this->resolvePath($context, $module, $blade, 'modules'))) {
            return [
                'shortcut' => '@MODULE:' . ($module . '.' . $blade),
                'view' => $path,
            ];
        }

        $p = $module . '.' . $blade;
        if (view()->exists($path  = $this->resolvePath($context, '', $p, 'pages'))) {
            return ['shortcut' => '@PAGE:' . $p, 'view' => $path];
        }
        if (view()->exists($path = $this->resolvePath($context, '', $p, 'base'))) {
            return ['shortcut' => '@BASE:' . $p, 'view' => $path];
        }
        return [];
    }
    /**
     * Share data cho một context
     * 
     * Data được share sẽ được merge vào mọi view của context đó
     * Nhưng KHÔNG ghi đè các biến của Directories (variables)
     * 
     * @param string $context Tên context
     * @param array $data Data cần share
     * @return $this
     */
    public function share(string $context, array $data): self
    {
        if (!isset($this->sharedData[$context])) {
            $this->sharedData[$context] = [];
        }

        // Merge với shared data hiện có
        $this->sharedData[$context] = array_merge($this->sharedData[$context], $data);

        return $this;
    }

    /**
     * Lấy shared data của một context
     * 
     * @param string $context Tên context
     * @return array
     */
    public function getSharedData(string $context): array
    {
        return $this->sharedData[$context] ?? [];
    }

    /**
     * Clear shared data của một context
     * 
     * @param string $context Tên context
     * @return $this
     */
    public function clearSharedData(string $context): self
    {
        unset($this->sharedData[$context]);
        return $this;
    }

    /**
     * Clear tất cả shared data
     * 
     * @return $this
     */
    public function clearAllSharedData(): self
    {
        $this->sharedData = [];
        return $this;
    }

    /**
     * Render view
     * 
     * @param string $context Tên context
     * @param string $module Tên module (có thể rỗng để render từ base)
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @param string $type Loại view (base, modules, pages, components, layouts, templates, hoặc '' để render từ base)
     * @return \Illuminate\Contracts\View\View
     */
    public function render(string $context, string $module, string $blade, array $data = [], string $type = '')
    {
        $viewPath = $this->resolvePath($context, $module, $blade, $type);

        // Lấy variables từ context (KHÔNG được ghi đè)
        $variables = $this->getContextVariables($context);

        if (!$variables) {
            // Fallback nếu context chưa được đăng ký
            $variables = [
                '__system__' => '_system.',
                '__base__' => $context . '.',
                '__component__' => $context . '.components.',
                '__template__' => $context . '.templates.',
                '__partial__' => $context . '.partials.',
                // '__pagination__' => $context . '.pagination.',
                '__layout__' => $context . '.layouts.',
                '__module__' => $context . '.modules.',
                '__page__' => $context . '.pages.',
            ];
        }

        // Lấy shared data của context
        $sharedData = $this->getSharedData($context);

        // Loại bỏ các key trùng với variables từ shared data và data
        // Đảm bảo variables KHÔNG bị ghi đè
        $sharedDataFiltered = array_diff_key($sharedData, $variables);
        $dataFiltered = array_diff_key($data, $variables);

        // Merge data theo thứ tự ưu tiên:
        // 1. Variables (từ Directories) - KHÔNG được ghi đè
        // 2. Shared data (đã loại bỏ keys trùng với variables)
        // 3. Module info
        // 4. Data từ render() (đã loại bỏ keys trùng với variables) - có thể ghi đè shared data
        $viewData = array_merge(
            $variables, // Variables từ Directories - ưu tiên cao nhất, KHÔNG bị ghi đè
            $sharedDataFiltered, // Shared data (không có keys trùng với variables)
            [
                'module_slug' => $module,
                '__context__' => $context,
            ],
            $dataFiltered // Data từ render() (không có keys trùng với variables)
        );

        return view($viewPath, $viewData);
    }

    /**
     * Render module view
     * 
     * @param string $context Tên context
     * @param string $module Tên module
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderModule(string $context, string $module, string $blade, array $data = [])
    {
        return $this->render($context, $module, $blade, $data, 'modules');
    }

    /**
     * Render page view
     * 
     * @param string $context Tên context
     * @param string $module Tên module (không dùng trong path, chỉ để merge vào data)
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderPage(string $context, string $module, string $blade, array $data = [])
    {
        return $this->render($context, '', $blade, $data, 'pages');
    }

    /**
     * Render component view
     * 
     * @param string $context Tên context
     * @param string $component Tên component
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderComponent(string $context, string $component, array $data = [])
    {
        return $this->render($context, '', $component, $data, 'components');
    }

    /**
     * Render layout view
     * 
     * @param string $context Tên context
     * @param string $layout Tên layout
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderLayout(string $context, string $layout, array $data = [])
    {
        return $this->render($context, '', $layout, $data, 'layouts');
    }

    /**
     * Render template view
     * 
     * @param string $context Tên context
     * @param string $template Tên template
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderTemplate(string $context, string $template, array $data = [])
    {
        return $this->render($context, '', $template, $data, 'templates');
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
     * LƯU Ý: KHÔNG reset contexts vì chúng cần được giữ lại giữa các requests
     * Contexts được đăng ký từ phía web và có thể được cập nhật động (ví dụ: khi đổi theme)
     * 
     * Shared data được reset sau mỗi request (request-specific state)
     * 
     * @return void
     */
    public function resetInstanceState(): void
    {
        // KHÔNG reset contexts - giữ lại để có thể update động
        // Contexts là persistent state, không phải request-specific state

        // Reset shared data sau mỗi request
        // $this->sharedData = [];
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
