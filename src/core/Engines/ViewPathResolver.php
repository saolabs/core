<?php

namespace Saola\Core\Engines;

use Saola\Core\Contracts\OctaneCompatible;

/**
 * ViewPathResolver - Quản lý các base directory cho view
 * 
 * Class này quản lý các base directory paths cho view system với khả năng:
 * - Dễ dàng thay đổi base dir trong runtime
 * - Hỗ trợ multiple base dirs với priority
 * - Tuân thủ chuẩn Octane (không có static state leak)
 * 
 * @package Saola\Core\Engines
 */
class ViewPathResolver implements OctaneCompatible
{
    /**
     * @var array Danh sách các base directories với priority
     * Format: ['name' => ['path' => 'path', 'priority' => 100]]
     */
    protected $baseDirectories = [];

    /**
     * @var array Cache các paths đã resolve (reset sau mỗi request)
     */
    protected $resolvedPaths = [];

    /**
     * @var string Base directory mặc định
     */
    protected $defaultBase = 'web';

    /**
     * @var string Context hiện tại (admin, web, api, etc.)
     */
    protected $context = '';

    /**
     * @var string View folder
     */
    protected $viewFolder = null;

    /**
     * Constructor
     * 
     * @param array $config Cấu hình ban đầu
     */
    public function __construct(array $config = [])
    {
        $this->defaultBase = $config['defaultBase'] ?? 'web';
        $this->context = $config['context'] ?? '';
        $this->viewFolder = $config['viewFolder'] ?? null;
        
        // Khởi tạo base directories mặc định
        $this->initializeDefaultDirectories();
    }

    /**
     * Khởi tạo các base directories mặc định
     * 
     * @return void
     */
    protected function initializeDefaultDirectories(): void
    {
        $basePath = $this->buildBasePath();
        
        $this->baseDirectories = [
            'base' => [
                'path' => $basePath,
                'priority' => 100,
            ],
            'module' => [
                'path' => $basePath . '.modules',
                'priority' => 90,
            ],
            'page' => [
                'path' => $basePath . '.pages',
                'priority' => 80,
            ],
            'component' => [
                'path' => $basePath . '.components',
                'priority' => 70,
            ],
            'template' => [
                'path' => $basePath . '.templates',
                'priority' => 60,
            ],
            'layout' => [
                'path' => $basePath . '.layouts',
                'priority' => 50,
            ],
            'pagination' => [
                'path' => $basePath . '.pagination',
                'priority' => 40,
            ],
        ];
    }

    /**
     * Build base path từ context và viewFolder
     * 
     * @return string
     */
    protected function buildBasePath(): string
    {
        $parts = [];
        
        if ($this->context) {
            $parts[] = $this->context;
        }
        
        if ($this->viewFolder) {
            $parts[] = $this->viewFolder;
        }
        
        return $parts ? implode('.', $parts) : $this->defaultBase;
    }

    /**
     * Thêm hoặc cập nhật một base directory
     * 
     * @param string $name Tên của base directory
     * @param string $path Đường dẫn
     * @param int $priority Độ ưu tiên (cao hơn = ưu tiên hơn)
     * @return $this
     */
    public function setBaseDirectory(string $name, string $path, int $priority = 100): self
    {
        $this->baseDirectories[$name] = [
            'path' => $path,
            'priority' => $priority,
        ];
        
        // Sắp xếp lại theo priority
        uasort($this->baseDirectories, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        // Clear cache
        $this->resolvedPaths = [];
        
        return $this;
    }

    /**
     * Lấy path của một base directory
     * 
     * @param string $name Tên base directory
     * @return string|null
     */
    public function getBaseDirectory(string $name): ?string
    {
        return $this->baseDirectories[$name]['path'] ?? null;
    }

    /**
     * Xóa một base directory
     * 
     * @param string $name Tên base directory
     * @return $this
     */
    public function removeBaseDirectory(string $name): self
    {
        unset($this->baseDirectories[$name]);
        $this->resolvedPaths = [];
        return $this;
    }

    /**
     * Thay đổi context
     * 
     * @param string $context Context mới
     * @return $this
     */
    public function setContext(string $context): self
    {
        if ($this->context !== $context) {
            $this->context = $context;
            $this->initializeDefaultDirectories();
            $this->resolvedPaths = [];
        }
        return $this;
    }

    /**
     * Thay đổi view folder
     * 
     * @param string|null $viewFolder View folder mới
     * @return $this
     */
    public function setViewFolder(?string $viewFolder): self
    {
        if ($this->viewFolder !== $viewFolder) {
            $this->viewFolder = $viewFolder;
            $this->initializeDefaultDirectories();
            $this->resolvedPaths = [];
        }
        return $this;
    }

    /**
     * Resolve view path từ alias hoặc path
     * 
     * Hỗ trợ các format:
     * - @base.home => {basePath}home
     * - @module.index => {modulePath}index
     * - @page.about => {pagePath}about
     * - @component.button => {componentPath}button
     * 
     * @param string $path Path hoặc alias
     * @return string Path đã được resolve
     */
    public function resolve(string $path): string
    {
        // Kiểm tra cache
        if (isset($this->resolvedPaths[$path])) {
            return $this->resolvedPaths[$path];
        }

        // Kiểm tra alias format: @name.path
        if (preg_match('/^@([a-zA-Z0-9_]+)([\.\:])(.+)$/i', $path, $matches)) {
            $alias = strtolower($matches[1]);
            $separator = $matches[2];
            $remaining = $matches[3];

            if (isset($this->baseDirectories[$alias])) {
                $basePath = rtrim($this->baseDirectories[$alias]['path'], '.');
                $resolved = $basePath . '.' . $remaining;
                $this->resolvedPaths[$path] = $resolved;
                return $resolved;
            }
        }

        // Nếu không phải alias, sử dụng base directory mặc định
        $basePath = $this->getBaseDirectory('base') ?? $this->defaultBase;
        $basePath = rtrim($basePath, '.');
        $resolved = $basePath . '.' . $path;
        $this->resolvedPaths[$path] = $resolved;
        
        return $resolved;
    }

    /**
     * Lấy tất cả các base directories
     * 
     * @return array
     */
    public function getAllBaseDirectories(): array
    {
        return $this->baseDirectories;
    }

    /**
     * Lấy default view data với các paths đã resolve
     * 
     * @param array $additionalData Dữ liệu bổ sung
     * @return array
     */
    public function getDefaultViewData(array $additionalData = []): array
    {
        $basePath = rtrim($this->getBaseDirectory('base') ?? $this->defaultBase, '.');
        
        return array_merge([
            '__system__' => '_system.',
            '__base__' => $basePath . '.',
            '__component__' => rtrim($this->getBaseDirectory('component') ?? $basePath . '.components', '.') . '.',
            '__template__' => rtrim($this->getBaseDirectory('template') ?? $basePath . '.templates', '.') . '.',
            '__pagination__' => rtrim($this->getBaseDirectory('pagination') ?? $basePath . '.pagination', '.') . '.',
            '__layout__' => rtrim($this->getBaseDirectory('layout') ?? $basePath . '.layouts', '.') . '.',
            '__module__' => rtrim($this->getBaseDirectory('module') ?? $basePath . '.modules', '.') . '.',
            '__page__' => rtrim($this->getBaseDirectory('page') ?? $basePath . '.pages', '.') . '.',
        ], $additionalData);
    }

    /**
     * Reset trạng thái tĩnh (Octane compatibility)
     * 
     * @return void
     */
    public static function resetStaticState(): void
    {
        // Không có static properties cần reset
    }

    /**
     * Reset trạng thái của instance (Octane compatibility)
     * 
     * @return void
     */
    public function resetInstanceState(): void
    {
        // Clear cache
        $this->resolvedPaths = [];
        
        // Giữ lại base directories nhưng có thể reset nếu cần
        // $this->initializeDefaultDirectories();
    }

    /**
     * Lấy danh sách các thuộc tính tĩnh (Octane compatibility)
     * 
     * @return array
     */
    public static function getStaticProperties(): array
    {
        // Không có static properties
        return [];
    }

    /**
     * Clone instance để tránh shared state
     * 
     * @return self
     */
    public function clone(): self
    {
        $clone = new self([
            'defaultBase' => $this->defaultBase,
            'context' => $this->context,
            'viewFolder' => $this->viewFolder,
        ]);
        
        // Copy base directories
        $clone->baseDirectories = $this->baseDirectories;
        
        return $clone;
    }
}

