<?php

declare(strict_types=1);

namespace Saola\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Saola\Core\Language\EmptyLanguageStore;
use Saola\Core\Language\LanguageSources;
use Saola\Core\Language\LanguageStore;
use Saola\Core\Language\TextResolver;
use Saola\Core\Language\ClientCatalogs;

final class LanguageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LanguageSources::class);
        $this->app->bindIf(LanguageStore::class, EmptyLanguageStore::class);
        $this->app->scoped(TextResolver::class, fn ($app) => new TextResolver(
            $app->make(LanguageStore::class),
            fn () => $app->getLocale(),
            fn () => $app['config']->get('app.fallback_locale', 'en'),
        ));
        $this->app->scoped(ClientCatalogs::class, fn ($app) => new ClientCatalogs(
            $app->make(TextResolver::class), $app['config']->get('sao.i18n.client_scopes', []),
        ));
    }

    public function boot(): void
    {
        $sources = $this->app->make(LanguageSources::class);
        $files = $this->app['config']->get('sao.i18n.source_files', ['saola']);
        if ($files !== []) {
            $sources->register('app', $this->app->langPath(), $files);
        }
    }
}
