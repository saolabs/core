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
    protected ViewContextRegistry $registry;

    /**
     * View directory overrides that only live for the current request/job.
     */
    protected array $contextViewOverrides = [];

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
    public function __construct(?ViewContextRegistry $registry = null)
    {
        $this->registry = $registry ?? new ViewContextRegistry();
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
        $basePath = rtrim((string) ($directories['base'] ?? $name), '.');
        $directories['base'] = $basePath;

        $contextConfig = $this->registry->get($name);

        if ($contextConfig !== null) {
            // Đăng ký lại với base mới phải kéo theo các thư mục con.
            //
            // Trước đây chỗ này chỉ array_merge, nên gọi
            // registerContext('web', ['base' => "themes.{$slug}"]) lên một
            // context đã khai đủ key sẽ chỉ đổi mỗi 'base': 'modules' vẫn là
            // 'web.modules'. Tệ hơn, biến đại diện BÊN DƯỚI lại được suy từ
            // base mới — '__module__' thành 'themes.{$slug}.modules.' trong khi
            // resolvePath() vẫn trỏ 'web.modules'. Hai nửa của cùng một hàm đi
            // hai hướng, và không có lỗi nào phát ra.
            $directories = array_merge(
                $this->rebaseDirectories($contextConfig['directories'] ?? [], $basePath),
                $directories,
            );
        }

        // Type không khai thì KHÔNG điền sẵn: resolvePath() và mọi caller của
        // getBaseDirectory() đều đã có nhánh `?? "{base}.{type}"`, điền vào chỉ
        // làm config phình mà không đổi hành vi.
        $defaultVariables = [
            '__system__' => '_system.',
            '__base__' => $basePath . '.',
            '__component__' => ($directories['components'] ?? "{$basePath}.components") . '.',
            '__template__' => ($directories['templates'] ?? "{$basePath}.templates") . '.',
            '__partial__' => ($directories['partials'] ?? "{$basePath}.partials") . '.',
            '__layout__' => ($directories['layouts'] ?? "{$basePath}.layouts") . '.',
            '__module__' => ($directories['modules'] ?? "{$basePath}.modules") . '.',
            '__page__' => ($directories['pages'] ?? "{$basePath}.pages") . '.',
        ];

        // $variables === null nghĩa là "suy hết từ directories". Nhánh cũ rơi
        // vào array_merge($defaultVariables, null) khi $directories rỗng — lỗi
        // kiểu ở PHP 8.
        $variables = $variables === null
            ? $defaultVariables
            : array_merge($defaultVariables, $variables);

        if ($contextConfig !== null) {
            $contextConfig['directories'] = $directories;
            $contextConfig['variables'] = array_merge($contextConfig['variables'] ?? [], $variables);
        } else {
            $contextConfig = [
                'directories' => $directories,
                'variables' => $variables,
                'routeViews' => [], // Lưu cache route => view path nếu cần
            ];
        }
        $this->registry->put($name, $contextConfig);

        // Set context đầu tiên làm mặc định nếu chưa có
        if (!$this->registry->getDefaultContext()) {
            $this->registry->setDefaultContext($name);
        }

        return $this;
    }

    /**
     * Đưa các thư mục con của một base cũ sang base mới.
     *
     * Chỉ dời những key CHỈ NHẮC LẠI quy ước của base cũ (`web` → `web.modules`).
     * Key trỏ ra ngoài base — components dùng chung giữa các theme chẳng hạn —
     * là lựa chọn có chủ đích, giữ nguyên.
     *
     * @param array<string,string> $directories
     * @return array<string,string>
     */
    protected function rebaseDirectories(array $directories, string $newBase): array
    {
        $oldBase = rtrim((string) ($directories['base'] ?? ''), '.');
        if ($oldBase === '' || $oldBase === $newBase) {
            return $directories;
        }

        $fromOld = $this->makeDirectories($oldBase);
        $toNew = $this->makeDirectories($newBase);

        foreach ($directories as $type => $path) {
            if ($type !== 'base' && isset($fromOld[$type]) && $path === $fromOld[$type]) {
                $directories[$type] = $toNew[$type];
            }
        }
        $directories['base'] = $newBase;

        return $directories;
    }

    public function registerContextViewByRoute(string $context, string $route, string|array $viewPath, ?string $shortcut = null): self
    {
        $contextConfig = $this->registry->get($context);
        if ($contextConfig !== null) {
            $contextConfig['routeViews'][$route] = [
                'view' => $viewPath,
                'shortcut' => $shortcut,
            ];
            $this->registry->put($context, $contextConfig);
        }
        return $this;
    } 

    public function getViewPathByRoute(string $context, string $route, string $type = 'view'): ?string
    {
        $config = $this->registry->get($context);
        if ($config === null || !isset($config['routeViews'][$route])) {
            return null;
        }
        if($type === 'shortcut' && isset($config['routeViews'][$route]['shortcut'])) {
            return $config['routeViews'][$route]['shortcut'];
        }
        $registered = $config['routeViews'][$route]['view'] ?? null;
        if (is_array($registered)) {
            return $this->resolveRouteComponent($context, $registered);
        }
        return $registered;
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
        return $this->getContextDirectories($context)[$type] ?? null;
    }

    /**
     * Lấy tất cả base directories của một context
     * 
     * @param string $context Tên context
     * @return array|null
     */
    public function getContextDirectories(string $context): ?array
    {
        return $this->contextViewOverrides[$context]['directories']
            ?? $this->registry->get($context)['directories']
            ?? null;
    }

    /**
     * Lấy các biến đại diện (variables) của một context
     * 
     * @param string $context Tên context
     * @return array|null
     */
    public function getContextVariables(string $context): ?array
    {
        return $this->contextViewOverrides[$context]['variables']
            ?? $this->registry->get($context)['variables']
            ?? null;
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
        return $this->getContextVariables($context)[$variable] ?? null;
    }

    /**
     * Lấy toàn bộ cấu hình của một context
     * 
     * @param string $context Tên context
     * @return array|null
     */
    public function getContextConfig(string $context): ?array
    {
        $config = $this->registry->get($context);
        if ($config === null) {
            return null;
        }

        if (isset($this->contextViewOverrides[$context])) {
            $config = array_merge($config, $this->contextViewOverrides[$context]);
        }

        return $config;
    }

    /**
     * Kiểm tra context có tồn tại không
     * 
     * @param string $context Tên context
     * @return bool
     */
    public function hasContext(string $context): bool
    {
        return $this->registry->has($context);
    }

    /**
     * Lấy tất cả contexts
     * 
     * @return array
     */
    public function getAllContexts(): array
    {
        return $this->registry->names();
    }

    /**
     * Set context mặc định
     * 
     * @param string $context
     * @return $this
     */
    public function setDefaultContext(string $context): self
    {
        $this->registry->setDefaultContext($context);
        return $this;
    }

    /**
     * Lấy context mặc định
     * 
     * @return string
     */
    public function getDefaultContext(): string
    {
        return $this->registry->getDefaultContext();
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
        $config = $this->registry->get($context);
        if ($config !== null) {
            $config['variables'] = array_merge(
                $config['variables'] ?? [],
                $variables
            );
            $this->registry->put($context, $config);
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
        $config = $this->registry->get($context);
        if ($config !== null) {
            $config['directories'] = array_merge(
                $config['directories'] ?? [],
                $directories
            );
            $this->registry->put($context, $config);

            // Tự động cập nhật variables từ directories['base'] nếu không có
            if ($variables === null) {
                $this->regenerateVariablesFromDirectories($context);
            } else {
                $config['variables'] = array_merge(
                    $config['variables'] ?? [],
                    $variables
                );
                $this->registry->put($context, $config);
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
        $config = $this->registry->get($context);
        if ($config === null) {
            return;
        }

        $directories = $config['directories'];
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
        $config['variables'] = array_merge(
            $config['variables'] ?? [],
            $newVariables
        );
        $this->registry->put($context, $config);
    }

    /**
     * Select compiled views for one or more contexts for the current request.
     *
     * Accepted values include a Blade base ("themes.storefront"), a relative
     * path ("themes/storefront"), or the matching Sao source directory
     * ("resources/saola/themes/storefront/views"). Source paths are converted
     * to the compiled Blade namespace; Sao files are never compiled here.
     *
     * @param string|array<string,string> $context
     */
    public function setContextViews(string|array $context, string|array|null $views = null): self
    {
        if (is_array($context)) {
            foreach ($context as $contextName => $contextViews) {
                $this->setContextViews($contextName, $contextViews);
            }
            return $this;
        }

        if (!$this->hasContext($context)) {
            throw new \InvalidArgumentException("View context [{$context}] is not registered.");
        }

        if ($views === null) {
            throw new \InvalidArgumentException("Views path for context [{$context}] is required.");
        }

        $this->contextViewOverrides[$context] = [
            'directories' => $directories = $this->normalizeDirectories($views, $context),
            'variables' => $this->makeVariables($directories),
        ];

        return $this;
    }

    /**
     * Chuẩn hoá lựa chọn view của MỘT context thành đủ bộ thư mục.
     *
     * Nhận hai dạng, và mỗi context dùng dạng nào tuỳ ý — chúng hoàn toàn độc
     * lập vì mỗi context có một ô riêng trong `$contextViewOverrides`:
     *
     *   'themes.aurora'                      → suy cả 7 thư mục từ base
     *   ['base' => 'themes.aurora',          → custom từng thư mục; khoá nào
     *    'components' => 'shared.components'] //  không khai thì suy từ base
     *
     * @param string|array<string,string> $views
     * @return array<string,string>
     */
    protected function normalizeDirectories(string|array $views, string $context): array
    {
        if (is_string($views)) {
            return $this->makeDirectories($this->normalizeViewsBase($views));
        }

        $base = $this->normalizeViewsBase((string) ($views['base'] ?? $context));
        $directories = $this->makeDirectories($base);

        foreach ($directories as $type => $default) {
            if ($type === 'base' || !isset($views[$type])) {
                continue;
            }
            if (!is_string($views[$type]) || $views[$type] === '') {
                throw new \InvalidArgumentException(
                    "Views path for [{$context}.{$type}] must be a non-empty string."
                );
            }
            $directories[$type] = $this->normalizeViewsBase($views[$type]);
        }

        return $directories;
    }

    /**
     * Remove the request-scoped view selection and fall back to the registry.
     */
    public function clearContextViews(?string $context = null): self
    {
        if ($context === null) {
            $this->contextViewOverrides = [];
        } else {
            unset($this->contextViewOverrides[$context]);
        }

        return $this;
    }

    public function getContextViews(string $context): ?string
    {
        return $this->getBaseDirectory($context, 'base');
    }

    protected function normalizeViewsBase(string $views): string
    {
        $views = str_replace('\\', '/', trim($views));

        if (preg_match('#(?:^|/)resources/saola/(.+?)/views/?$#', $views, $matches)) {
            $views = $matches[1];
        } elseif (preg_match('#(?:^|/)resources/views/(.+?)/?$#', $views, $matches)) {
            $views = $matches[1];
        }

        $base = trim(preg_replace('#[/.]+#', '.', $views) ?? '', '.');
        if ($base === '' || !preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/', $base)) {
            throw new \InvalidArgumentException("Invalid context views path [{$views}].");
        }

        return $base;
    }

    protected function makeDirectories(string $base): array
    {
        return [
            'base' => $base,
            'components' => "{$base}.components",
            'templates' => "{$base}.templates",
            'partials' => "{$base}.partials",
            'modules' => "{$base}.modules",
            'layouts' => "{$base}.layouts",
            'pages' => "{$base}.pages",
        ];
    }

    protected function makeVariables(array $directories): array
    {
        $base = $directories['base'];

        return [
            '__system__' => '_system.',
            '__base__' => $base . '.',
            '__component__' => $directories['components'] . '.',
            '__template__' => $directories['templates'] . '.',
            '__partial__' => $directories['partials'] . '.',
            '__layout__' => $directories['layouts'] . '.',
            '__module__' => $directories['modules'] . '.',
            '__page__' => $directories['pages'] . '.',
        ];
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
        $validTypes = ['', 'base', 'modules', 'pages', 'components', 'partials', 'layouts', 'templates', 'route', 'raw'];

        // Validate và normalize type
        if (!in_array($type, $validTypes, true)) {
            $type = '';
        }
        if($type === 'route') {
            return $blade;
        }

        if($type === 'raw') {
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

    /**
     * Materialize a worker-stable logical route descriptor for this request.
     * Automatic descriptors are resolved in priority order against the active
     * context views, without ever mutating the shared route registry.
     */
    public function resolveRouteComponent(string $context, string|array|null $component): ?string
    {
        if ($component === null || $component === '') {
            return null;
        }

        if (is_string($component)) {
            if (str_starts_with($component, '@')) {
                return $this->resolveLogicalRouteCandidate($context, $component);
            }

            // Backward compatibility for route registries created before
            // logical descriptors: rebase "web.*" onto the active request
            // context, but leave explicit raw registry keys untouched.
            $registeredBase = $this->registry->get($context)['directories']['base'] ?? $context;
            $activeBase = $this->getBaseDirectory($context, 'base') ?? $registeredBase;
            if ($component === $registeredBase) {
                return $this->themedComponentOrBase($activeBase, $component);
            }
            if (str_starts_with($component, $registeredBase . '.')) {
                return $this->themedComponentOrBase(
                    $activeBase . substr($component, strlen($registeredBase)),
                    $component,
                );
            }

            return $component;
        }

        $candidates = $component['candidates'] ?? [];
        if (!$candidates && isset($component['logical'])) {
            $candidates = [$component['logical']];
        }

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            $resolved = $this->resolveLogicalRouteCandidate($context, $candidate);
            if ($this->viewExistsStrict($resolved)) {
                return $resolved;
            }

            // Theme không đè view này ⇒ thử bản ở base. Thiếu bước này thì route
            // `kind: auto` trả null và BIẾN MẤT khỏi bảng route của client:
            // "[Router] No route matched" cho mọi trang theme không đụng tới.
            $fallback = $this->fallbackViewName($resolved);
            if ($fallback !== null && $this->viewExistsStrict($fallback)) {
                return $fallback;
            }
        }

        if (($component['kind'] ?? null) === 'auto') {
            return null;
        }

        $logical = $component['logical'] ?? ($candidates[0] ?? null);
        if (!is_string($logical) || $logical === '') {
            return null;
        }
        $resolved = $this->resolveLogicalRouteCandidate($context, $logical);
        $base = $this->fallbackViewName($resolved);

        return $base === null ? $resolved : $this->themedComponentOrBase($resolved, $base);
    }

    /**
     * Khoá component gửi cho client: chỉ dùng khoá của theme khi theme THẬT SỰ
     * mang view đó.
     *
     * SSR có finder rơi về base nên vẫn ra HTML đúng, nhưng registry JS thì chỉ
     * có khoá của những view theme thực sự compile. Trả khoá theme cho một view
     * theme không đè = client không tìm thấy view để hydrate.
     */
    protected function themedComponentOrBase(string $themed, string $base): string
    {
        if ($themed === $base) {
            return $base;
        }
        if ($this->viewExistsStrict($themed)) {
            return $themed;
        }

        // Theme không đè view này mà base có ⇒ trả khoá base, đúng thứ registry
        // JS đang có. Cả hai đều không có thì giữ khoá theme: không có thông tin
        // nào tốt hơn, và hạ cấp lúc đó chỉ làm sai lệch context đang chạy.
        return $this->viewExistsStrict($base) ? $base : $themed;
    }

    /**
     * Cặp (base theme → base gốc) để client tự rơi khi tra registry hụt.
     *
     * @return array{__view_fallback_from__?:string,__view_fallback_to__?:string}
     */
    protected function viewFallbackPair(string $context): array
    {
        $activeBase = $this->contextViewOverrides[$context]['directories']['base'] ?? null;
        $registeredBase = $this->registry->get($context)['directories']['base'] ?? $context;

        if (!$activeBase || $activeBase === $registeredBase) {
            return [];
        }

        return [
            '__view_fallback_from__' => $activeBase,
            '__view_fallback_to__' => $registeredBase,
        ];
    }

    /**
     * Khoá view gửi xuống client (SSR boot, bảng route).
     *
     * SSR có thể render một view mang TÊN của theme nhưng nội dung lấy từ base,
     * nhờ đường rơi của ThemeAwareViewFinder. Registry JS thì không có đường rơi
     * đó — nó chỉ có khoá của những view đã compile. Hàm này trả đúng khoá mà
     * registry đang có.
     */
    public function resolveClientViewKey(string $view): string
    {
        $base = $this->fallbackViewName($view);

        return $base === null ? $view : $this->themedComponentOrBase($view, $base);
    }

    /**
     * View có file THẬT không — không tính đường rơi về base của
     * {@see \Saola\Core\View\Finders\ThemeAwareViewFinder}.
     */
    protected function viewExistsStrict(string $view): bool
    {
        // PHẢI lấy finder của chính view factory, không phải app('view.finder'):
        // hai chỗ đó có thể là hai instance khác nhau (factory giữ instance đã
        // resolve trước khi provider extend binding), và location thêm bằng
        // View::getFinder()->addLocation() chỉ nằm ở cái của factory. Lấy nhầm
        // thì mọi view đều "không tồn tại".
        $finder = app()->bound('view') ? app('view')->getFinder() : null;
        if ($finder instanceof \Saola\Core\View\Finders\ThemeAwareViewFinder) {
            return $finder->existsWithoutFallback($view);
        }

        return view()->exists($view);
    }

    protected function resolveLogicalRouteCandidate(string $context, string $candidate): string
    {
        if (preg_match('/^@module[\.:](.+)$/i', $candidate, $matches)) {
            $parts = explode('.', $matches[1]);
            $blade = array_pop($parts);
            return $this->resolvePath($context, implode('.', $parts), $blade, 'modules');
        }

        return $this->resolvePathByAlias($context, '', $candidate);
    }

    /**
     * Tên view của theme → tên view tương ứng ở base gốc, hoặc null nếu tên
     * này không thuộc theme nào đang bật.
     *
     * Đây là chỗ duy nhất biết "themes.aurora.partials.head" thật ra là
     * "web.partials.head" khi theme không đè file đó. Nhờ vậy một theme chỉ cần
     * mang những view nó muốn đổi, thay vì chép cả 59 view chỉ để đổi một trang.
     */
    public function fallbackViewName(string $view): ?string
    {
        foreach ($this->contextViewOverrides as $context => $override) {
            $activeBase = $override['directories']['base'] ?? null;
            $registeredBase = $this->registry->get($context)['directories']['base'] ?? $context;

            if (!$activeBase || $activeBase === $registeredBase) {
                continue;
            }
            if ($view === $activeBase) {
                return $registeredBase;
            }
            if (str_starts_with($view, $activeBase . '.')) {
                return $registeredBase . substr($view, strlen($activeBase));
            }
        }

        return null;
    }

    /**
     * Stable fingerprint of the effective request-scoped view selection.
     */
    public function getContextViewRevision(string $context): string
    {
        $payload = [
            'context' => $context,
            'directories' => $this->getContextDirectories($context) ?? [],
            'variables' => $this->getContextVariables($context) ?? [],
        ];

        return substr(hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES)), 0, 16);
    }

    public function exportContextState(string $context): array
    {
        return [
            'context' => $context,
            'views' => $this->getContextViews($context),
            'revision' => $this->getContextViewRevision($context),
            'systemData' => array_merge(
                $this->getContextVariables($context) ?? [],
                ['__context__' => $context],
                // Đường rơi theme → base cho client. Server có
                // ThemeAwareViewFinder, client thì không: `__layout__` là tiền
                // tố của CẢ context nên `@extends(__layout__ + "workspace")`
                // sinh khoá `themes.{slug}.layouts.workspace` ngay cả khi theme
                // không mang layout đó. Không có cặp này thì client báo
                // `View "..." not found in registry` và trang trắng.
                $this->viewFallbackPair($context),
            ),
        ];
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
                    'contextView' => 'pages.' . $blade,
                ];
            }
            if (view()->exists($path = $this->resolvePath($context, '', $blade, 'base'))) {
                return [
                    'shortcut' => '@BASE:' . $blade,
                    'view' => $path,
                    'contextView' => $blade,
                ];
            }
            return [];
        }

        $module = implode('.', $parts);

        if (view()->exists($path = $this->resolvePath($context, $module, $blade, 'modules'))) {
            return [
                'shortcut' => '@MODULE:' . ($module . '.' . $blade),
                'view' => $path,
                'contextView' => 'modules.' . $module . '.' . $blade,
            ];
        }

        $p = $module . '.' . $blade;
        if (view()->exists($path  = $this->resolvePath($context, '', $p, 'pages'))) {
            return ['shortcut' => '@PAGE:' . $p, 'view' => $path, 'contextView' => 'pages.' . $p];
        }
        if (view()->exists($path = $this->resolvePath($context, '', $p, 'base'))) {
            return ['shortcut' => '@BASE:' . $p, 'view' => $path, 'contextView' => 'base.' . $p];
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
     * Registry state is worker-wide. View overrides and shared data belong to
     * the current request and must never survive a scoped lifecycle.
     * 
     * @return void
     */
    public function resetInstanceState(): void
    {
        $this->contextViewOverrides = [];
        $this->sharedData = [];
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
