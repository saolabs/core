<?php

namespace Tests\Feature;

use Saola\Core\Contracts\SiteProvider;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Support\SPA;
use Tests\TestCase;

class ViewContextEndpointTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [SaolaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('sao.view_context.contexts', ['web' => [], 'staff' => ['ability' => 'staff']]);
    }

    public function test_listed_context_exports_state_with_site_and_others_are_hidden(): void
    {
        $views = app(ViewContextManager::class);
        foreach (['web', 'staff', 'secret'] as $context) {
            $views->registerContext($context, ['base' => $context]);
        }
        $views->provideSite(new class implements SiteProvider {
            public function version(): string { return '3'; }
            public function data(string $locale): array { return ['general' => ['website_name' => 'Gehihi', 'locale' => $locale]]; }
        });
        SPA::resetRoutes();
        SPA::active();
        SPA::addRoute('web', 'web.home', '/', 'web.pages.home');

        try {
            $response = $this->getJson('/_saola/context/web')->assertOk();
            $this->assertSame('Gehihi', $response->json('systemData.__site.general.website_name'));
            $this->assertSame('web', $response->json('systemData.__context__'));
            $this->assertSame('web.home', $response->json('routes.0.name'));
            $this->assertSame(app(ViewContextManager::class)->getContextViewRevision('web'), $response->headers->get('X-Saola-View-Revision'));
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));

            $this->getJson('/_saola/context/staff')->assertUnauthorized();
            $this->getJson('/_saola/context/secret')->assertNotFound();
            $this->getJson('/_saola/context/missing')->assertNotFound();
        } finally {
            SPA::resetRoutes();
            SPA::inactive();
        }
    }
}
