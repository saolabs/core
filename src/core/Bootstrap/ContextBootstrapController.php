<?php

declare(strict_types=1);

namespace Saola\Core\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Language\ClientCatalogs;
use Saola\Core\Services\ThemeService;

final class ContextBootstrapController
{
    public function __invoke(
        Request $request,
        string $context,
        ContextBootstrap $bootstrap,
        ViewContextManager $contexts,
        ClientCatalogs $catalogs,
        ThemeService $themes,
    ): Response {
        $settings = ContextBootstrap::settings($context);
        abort_unless($settings && $contexts->hasContext($context), 404);

        if ($settings['public']) {
            $cacheControl = 'public, max-age=0, must-revalidate';
            if ($settings['proxy_ttl'] > 0 && !$request->query->has('revision')) {
                $cacheControl .= ', s-maxage='.$settings['proxy_ttl'];
            }
        } else {
            ContextBootstrap::authorize($request, $settings, $catalogs->definition($settings['scope']));
            $cacheControl = 'private, no-cache';
        }

        $themes->apply($context);
        $script = $bootstrap->script($context);
        $response = response($script, 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $cacheControl,
        ]);
        if (!$settings['public']) {
            $response->setVary(['Cookie', 'Authorization'], false);
        }
        $response->setEtag(hash('sha256', $script), true);
        $response->isNotModified($request);

        return $response;
    }
}
