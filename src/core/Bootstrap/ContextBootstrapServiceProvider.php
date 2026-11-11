<?php

declare(strict_types=1);

namespace Saola\Core\Bootstrap;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Saola\Core\Http\Middleware\ApplyTheme;

final class ContextBootstrapServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../templates/bootstrap', 'saola-bootstrap');

        if (!$this->app->routesAreCached()) {
            $this->app->booted(function (): void {
                foreach (array_keys(config('sao.bootstrap.contexts', [])) as $context) {
                    if (!preg_match('/^[a-z][a-z0-9_-]*$/D', (string) $context)) {
                        continue;
                    }

                    $settings = ContextBootstrap::settings($context);
                    if ($settings === null) {
                        continue;
                    }

                    Route::get('/saola/'.$context.'/bootstrap.js', ContextBootstrapController::class)
                        ->middleware($settings['public'] ? [] : ['web'])
                        ->withoutMiddleware(ApplyTheme::class)
                        ->defaults('context', $context)
                        ->name(ContextBootstrap::ROUTE_PREFIX.$context);
                }
            });
        }
    }
}
