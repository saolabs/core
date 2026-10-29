<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Services\{BundleManifest, ClientShare, ContextSource, ThemeService};
use Tests\TestCase;

class ContextSourceTest extends TestCase
{
    private BundleManifest $bundles;

    protected function setUp(): void
    {
        parent::setUp();
        (new SaolaServiceProvider($this->app))->register();
        app(ViewContextManager::class)->registerContext('web', ['base' => 'web']);
        app(ViewContextManager::class)->registerContext('admin', ['base' => 'admin']);
        $this->bundles = new class extends BundleManifest {
            public array $records = [];
            public array $entries = [];
            public bool $dev = false;
            public function contextBuild(string $context): array { return $this->records[$context] ?? []; }
            public function manifest(string $context): array { return $this->entries[$context] ?? []; }
            public function isDev(): bool { return $this->dev; }
        };
        $this->app->instance(BundleManifest::class, $this->bundles);
        config(['saola-contexts' => ['default' => 'auto', 'modes' => [], 'themes' => []], 'cache.default' => 'array']);
    }

    public function test_standalone_uses_context_views_and_ignores_an_installed_theme_without_deactivating_it(): void
    {
        config(['saola-contexts.modes.web' => 'standalone']);
        $this->bundles->records['web'] = ['mode' => 'standalone'];
        Cache::put(ThemeService::CACHE_PREFIX.'web', 'aurora');
        app(ViewContextManager::class)->setContextViews('web', 'web.aurora');
        $this->assertTrue(app(ThemeService::class)->apply('web'));
        $this->assertSame('web', app(ViewContextManager::class)->getBaseDirectory('web', 'base'));
        $this->assertSame('admin', app(ViewContextManager::class)->getBaseDirectory('admin', 'base'));
        $this->assertSame('aurora', Cache::get(ThemeService::CACHE_PREFIX.'web'));
        $this->assertSame('standalone', app(ClientShare::class)->export(Request::create('/'))['saola.context.web']['mode']);
        $this->assertSame([], $this->bundles->bundleUrls('web'));
        $this->assertSame([], $this->bundles->bundleKinds('web'));
    }

    public function test_local_theme_uses_the_built_namespace_as_context_base_and_exports_the_choice(): void
    {
        $this->bundles->records['web'] = ['mode' => 'theme', 'theme' => 'paper', 'namespace' => 'web.themes.paper.views'];
        $this->assertTrue(app(ThemeService::class)->apply('web'));
        $this->assertSame('web.themes.paper.views', app(ViewContextManager::class)->getBaseDirectory('web', 'base'));
        $this->assertSame('web.themes.paper.views.modules', app(ViewContextManager::class)->getBaseDirectory('web', 'modules'));
        $this->assertSame('paper', app(ClientShare::class)->export(Request::create('/'))['saola.context.web']['theme']);
    }

    public function test_invalid_built_namespace_is_rejected_before_applying_it(): void
    {
        $this->bundles->records['web'] = ['mode' => 'standalone', 'namespace' => '../outside'];
        $this->expectException(\InvalidArgumentException::class);
        app(ContextSource::class)->apply('web');
    }

    public function test_changed_environment_mode_requires_a_matching_rebuild(): void
    {
        $this->bundles->records['web'] = ['mode' => 'theme', 'theme' => 'default'];
        config(['saola-contexts.modes.web' => 'standalone']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('rebuild');
        app(ContextSource::class)->definition('web');
    }

    public function test_changed_environment_theme_requires_a_matching_rebuild(): void
    {
        $this->bundles->records['web'] = ['mode' => 'theme', 'theme' => 'default'];
        config(['saola-contexts.modes.web' => 'theme', 'saola-contexts.themes.web' => 'paper']);
        $this->expectException(\RuntimeException::class);
        app(ContextSource::class)->definition('web');
    }

    public function test_global_mode_and_context_override_are_independent(): void
    {
        config(['saola-contexts.default' => 'standalone', 'saola-contexts.modes.admin' => 'package']);
        $this->assertSame('standalone', app(ContextSource::class)->definition('web')['mode']);
        $this->assertSame('package', app(ContextSource::class)->definition('admin')['mode']);
    }

    public function test_local_stylesheet_is_resolved_without_a_host_css_setting(): void
    {
        $logical = 'resources/saola/web/assets/css/app.css';
        $generated = 'resources/js/saola/_sources/web/assets/css/app.css';
        $this->bundles->records['web'] = ['mode' => 'standalone', 'defaultCss' => $logical, 'css' => [$logical => $generated]];
        $this->bundles->entries['web'] = [$generated => ['file' => 'assets/site.css']];
        $this->assertSame(asset('static/saola/web/assets/site.css'), $this->bundles->cssUrl('web'));
    }

    public function test_a_minimal_local_context_does_not_request_a_nonexistent_default_stylesheet_in_dev(): void
    {
        $this->bundles->dev = true;
        $this->bundles->records['web'] = ['mode' => 'standalone', 'css' => []];
        $this->assertNull($this->bundles->cssUrl('web'));
    }

    public function test_mode_config_is_serializable_for_laravel_config_cache(): void
    {
        $config = require __DIR__.'/../../src/config/contexts.php';
        $this->assertSame($config, unserialize(serialize($config)));
        $this->assertArrayHasKey('default', $config);
        config(['saola-contexts.modes.web' => 'invalid']);
        $this->expectException(\InvalidArgumentException::class);
        app(ContextSource::class)->definition('web');
    }
}
