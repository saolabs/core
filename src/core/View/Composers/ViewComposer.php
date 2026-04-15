<?php

namespace Saola\Core\View\Composers;

use Saola\Core\View\Services\ViewHelperService;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ViewComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Lấy tên view từ view name
        $viewName = $view->getName();
        
        // Tự động thêm các biến view vào mọi view
        $view->with('__VIEW_ID__', $viewId = uniqid());
        $view->with('__VIEW_PATH__', $viewName);
        $view->with('__VIEW_NAME__', $viewName);
        $view->with('__VIEW_TYPE__', 'view');
        $helper = app(ViewHelperService::class);
        $helper->registerView($viewName, $viewId);
    }
}
