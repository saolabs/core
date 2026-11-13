<?php

declare(strict_types=1);

namespace Saola\Core\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Saola\Core\Bootstrap\ContextBootstrap;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Language\ClientCatalogs;
use Saola\Core\Services\ThemeService;
use Saola\Core\View\Services\ViewHelperService;

/**
 * `GET /_saola/context/{context}`: trạng thái view context (routes, systemData
 * gồm `__site`, sharedData). Client chỉ gọi khi header `X-Saola-View-Revision`
 * của một response page-data khác bản nó đang giữ, nên body JSON page-data
 * không phải mang khối này.
 *
 * Quyền: context có cấu hình bootstrap thì theo đúng luật của bootstrap.js;
 * không thì phải được liệt kê ở `sao.view_context.contexts`. Còn lại 404,
 * để không lộ bảng route của context không công khai.
 */
final class ViewContextController
{
    public function __invoke(
        Request $request,
        string $context,
        ViewContextManager $contexts,
        ViewHelperService $views,
        ThemeService $themes,
        ClientCatalogs $catalogs,
    ): JsonResponse {
        abort_unless($contexts->hasContext($context), 404);

        if ($settings = ContextBootstrap::settings($context)) {
            if (!$settings['public']) {
                ContextBootstrap::authorize($request, $settings, $catalogs->definition($settings['scope']));
            }
        } else {
            $definition = config('sao.view_context.contexts', [])[$context] ?? null;
            abort_unless(is_array($definition), 404);
            if (isset($definition['ability'])) {
                $user = $request->user($definition['guard'] ?? null);
                abort_unless($user, 401);
                abort_unless(\Illuminate\Support\Facades\Gate::forUser($user)->allows($definition['ability']), 403);
            }
        }

        $themes->apply($context);
        $state = $views->exportViewContext($context);

        return response()->json([
            'context' => $context,
            'revision' => $state['revision'],
            'views' => $state['views'],
            'systemData' => $state['systemData'],
            'sharedData' => $state['sharedData'],
            'routes' => $state['routes'],
        ], 200, [
            'X-Saola-View-Revision' => $state['revision'],
            // Theo phiên đăng nhập và locale: không cho proxy dùng chung.
            'Cache-Control' => 'private, no-cache',
            'Vary' => 'Cookie, Accept-Language',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
