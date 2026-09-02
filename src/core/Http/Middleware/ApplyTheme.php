<?php

namespace Saola\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Saola\Core\Routing\Registry;
use Saola\Core\Services\ThemeService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Áp view base đang active cho context của request.
 *
 * Được `SaolaServiceProvider` tự đẩy vào nhóm middleware `web`, KHÔNG phải một
 * alias route phải tự nhớ gắn: alias thì route nào quên là render sai base mà
 * không lỗi gì — đúng cái bẫy mà middleware `webview` cũ đã mắc.
 *
 * MỌI context đều đặt được view base riêng, nên context phải suy từ chính
 * request. Nguồn tin cậy nhất là segment đầu của route name (`admin.users.index`
 * → `admin`) — đúng quy ước mà routeToViewPathConfig() đang dùng để tách module.
 * `spa_scope` chỉ là đường lùi vì SPAScopeMiddleware không được gắn mặc định.
 */
class ApplyTheme
{
    public function handle(Request $request, Closure $next, ?string $context = null): Response
    {
        $context ??= $this->contextFor($request);

        // ResponseMethods::jsonResponse() có nhánh đọc `spa_scope`, nhưng
        // SPAScopeMiddleware không được gắn ở đâu nên nhánh đó chưa từng chạy.
        // Đặt ở đây cho nó sống, và để cả hai chỗ dùng CÙNG một context.
        $request->attributes->set('spa_scope', $context);

        app(ThemeService::class)->apply($context);

        return $next($request);
    }

    protected function contextFor(Request $request): string
    {
        $routeName = $request->route()?->getName();
        if (is_string($routeName) && str_contains($routeName, '.')) {
            $candidate = strstr($routeName, '.', true);
            // Chỉ nhận khi đó thật sự là một context đã đăng ký; route tên
            // `password.request` của Laravel không được hiểu thành context.
            if ($candidate !== false && Registry::getContext($candidate) !== null) {
                return $candidate;
            }
        }

        $scope = $request->attributes->get('spa_scope');

        return is_string($scope) && $scope !== '' ? $scope : Registry::WEB;
    }
}
