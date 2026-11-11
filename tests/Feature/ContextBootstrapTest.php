<?php

namespace Tests\Feature;

use Saola\Core\Bootstrap\ContextBootstrap;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Support\SPA;
use Tests\TestCase;

class ContextBootstrapTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [SaolaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sao.bootstrap', [
            'external' => true,
            'contexts' => [
                'mobile' => ['proxy_ttl' => 60],
                'unregistered' => ['scope' => 'mobile'],
            ],
        ]);
        $app['config']->set('sao.i18n.client_scopes.mobile', ['keys' => ['mobile'], 'public' => true]);
    }

    public function test_a_configured_context_uses_the_shared_bootstrap(): void
    {
        app(ViewContextManager::class)->registerContext('mobile', ['base' => 'mobile']);
        SPA::resetRoutes();
        SPA::active();
        SPA::addRoute('mobile', 'mobile.home', '/mobile/home', 'mobile.pages.home');
        SPA::addRoute('web', 'web.home', '/web/home', 'web.pages.home');

        try {
            $bootstrap = app(ContextBootstrap::class);
            $page = $bootstrap->forPage(request(), 'mobile');
            $this->assertSame('/saola/mobile/bootstrap.js', $page['url']);
            $this->assertSame('/saola/mobile/bootstrap.js', route('saola.bootstrap.mobile', [], false));
            $script = $this->get($page['url'])->assertOk()
                ->assertSee($page['revision'], false)
                ->assertSee('mobile.home', false)
                ->assertDontSee('web.home', false)
                ->assertHeaderMissing('Set-Cookie');
            $this->assertStringContainsString('s-maxage=60', $script->headers->get('Cache-Control'));
            $this->get($page['url'], ['If-None-Match' => $script->headers->get('ETag')])->assertNotModified();
            $retry = $this->get($page['url'].'?revision='.$page['revision'])->assertOk();
            $this->assertStringNotContainsString('s-maxage', $retry->headers->get('Cache-Control'));
            $this->get('/saola/unregistered/bootstrap.js')->assertNotFound();
        } finally {
            SPA::resetRoutes();
            SPA::inactive();
        }
    }
}
