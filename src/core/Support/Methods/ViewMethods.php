<?php

namespace Saola\Core\Support\Methods;

use Saola\Core\Engines\ViewContextManager;
use Illuminate\Support\Facades\App;

/**
 * ViewMethods - Trait quản lý view với ViewContextManager
 * 
 * Service sử dụng trait này cần có:
 * - $context: Tên context (admin, web, ...)
 * - $module: Tên module
 * - $moduleName: Tên hiển thị của module (optional)
 * 
 * ViewContextManager được lấy từ service container (singleton)
 */
trait ViewMethods
{
    /**
     * @var string $context Context của service
     */
    protected $context = '';

    /**
     * @var string $module Tên module (cũng là tên thư mục view)
     */
    protected $module = 'test';

    /**
     * @var string $moduleName Tên hiển thị của module
     */
    protected $moduleName = '';

    /**
     * Lấy ViewContextManager từ container
     * 
     * @return ViewContextManager
     */
    protected function getViewContextManager(): ViewContextManager
    {
        return App::make(ViewContextManager::class);
    }

    protected function getModuleActionKey(string $action = ''): string
    {
        return $this->context . ($this->module ? '.' . $this->module : '') . ($action ? '.' . $action : '');
    }

    /**
     * Render view
     * 
     * Nếu có module: render module view (context.modules.module.blade)
     * Nếu không có module: render từ base (context.blade)
     * 
     * @param string $blade Tên blade
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function render(string $blade, array $data = [])
    {
        $context = $this->context ?: 'web';
        $contextManager = $this->getViewContextManager();

        // Merge với module info
        $mergedData = array_merge([
            'module_slug' => $this->module,
            '__route__' => $this->getModuleActionKey(),
            'package' => $this->package ?? null,
        ], $data);

        // Nếu không có module, render từ base: context.blade
        // Ví dụ: context='web', blade='abc' => 'web.abc'
        return $contextManager->render($context, '', $blade, $mergedData, '');
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
        $context = $this->context ?: 'web';
        $contextManager = $this->getViewContextManager();

        $mergedData = array_merge([
            'module_slug' => $this->module,
            '__route__' => $this->getModuleActionKey(),
        ], $data);

        return $contextManager->renderModule($context, $this->module, $blade, $mergedData);
    }

    /**
     * Render page view
     * 
     * @param string $page Tên page
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderPage(string $page, array $data = [])
    {
        $context = $this->context ?: 'web';
        $contextManager = $this->getViewContextManager();

        $mergedData = array_merge([
            'module_slug' => $this->module,
            '__route__' => $this->getModuleActionKey(),
        ], $data);

        return $contextManager->renderPage($context, $this->module, $page, $mergedData);
    }

    /**
     * Render component view
     * 
     * @param string $component Tên component
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderComponent(string $component, array $data = [])
    {
        $context = $this->context ?: 'web';
        $contextManager = $this->getViewContextManager();

        return $contextManager->renderComponent($context, $component, $data);
    }

    /**
     * Render layout view
     * 
     * @param string $layout Tên layout
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderLayout(string $layout, array $data = [])
    {
        $context = $this->context ?: 'web';
        $contextManager = $this->getViewContextManager();

        return $contextManager->renderLayout($context, $layout, $data);
    }

    /**
     * Render template view
     * 
     * @param string $template Tên template
     * @param array $data Dữ liệu
     * @return \Illuminate\Contracts\View\View
     */
    public function renderTemplate(string $template, array $data = [])
    {
        $context = $this->context ?: 'web';
        $contextManager = $this->getViewContextManager();

        return $contextManager->renderTemplate($context, $template, $data);
    }

    /**
     * Resolve view path từ alias
     * 
     * @param string $blade Tên blade
     * @return string View path đã được resolve
     */
    public function resolvePathByAlias(string $blade): string
    {
        $contextManager = $this->getViewContextManager();
        return $contextManager->resolvePathByAlias($this->context, $this->module, $blade);
    }

    public function resolvePathByRoute(string $route): string
    {
        $contextManager = $this->getViewContextManager();
        return ($shortcut = $contextManager->resolvePathByRoute($this->context, $route)) ? $shortcut : '';
    }

    public function getBladeViewRenderConfig(string $blade): array
    {
        if (preg_match('/^@([a-zA-Z0-9_]+)([\.\:])(.+)$/i', $blade, $matches)) {
            $alias = strtolower($matches[1]);
            $separator = $matches[2];
            $remaining = $matches[3];
            $method = 'render';
            if ($alias == 'module') {
                $method = 'renderModule';
            } elseif ($alias == 'page') {
                $method = 'renderPage';
            } elseif ($alias == 'component') {
                $method = 'renderComponent';
            } elseif ($alias == 'layout') {
                $method = 'renderLayout';
            } elseif ($alias == 'template') {
                $method = 'renderTemplate';
            } elseif ($alias == 'contextview') {
                $method = 'render';
            } elseif ($alias == 'raw') {
                $method = 'render';
            } else {
                // Nếu alias không khớp với bất kỳ loại nào, trả về method mặc định
                $method = 'render';
            }

            return [
                'method' => $method,
                'view' => $remaining,
            ];
        }
        return [
            'method' => 'render',
            'view' => $blade,
        ];
    }
}
