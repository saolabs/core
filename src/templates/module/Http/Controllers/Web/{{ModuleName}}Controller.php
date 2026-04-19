<?php

namespace {{Namespace}}\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

/**
 * {{ModuleName}} Web Controller
 * 
 * Sử dụng convention-based routing:
 * - index() → viewIndexPage() - Hiển thị trang chính
 * - show($id) → viewDetailPage($id) - Chi tiết
 * 
 * Service được tự động inject thông qua SmartInit trait
 * dựa trên module name: {{module_name}}
 * 
 * @property \One\App\Services\ModuleService $service
 */
class {{ModuleName}}Controller extends WebController
{
    /**
     * Module name - dùng để tự động inject service và generate routes
     */
    protected $module = '{{module_name}}';
    
    // Controller sẽ tự động có các methods:
    // - index() → viewIndexPage() - Tự động gọi service->getResults()
    // - show($id) → viewDetailPage($id) - Tự động gọi service->detail()
    
    // Nếu cần custom methods, thêm vào đây:
    // public function featuredContent(Request $request): View|JsonResponse
    // {
    //     $content = $this->service->getFeaturedContent();
    //     return $this->jsonSuccess($content);
    // }
}
