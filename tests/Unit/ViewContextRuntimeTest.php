<?php

namespace Tests\Unit;

use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Engines\ViewContextRegistry;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Support\SPA;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\View\Services\ViewHelperService;
use Saola\Core\View\Services\ViewStorageManager;
use Tests\TestCase;

class ViewContextRuntimeTest extends TestCase
{
    public function test_it_selects_theme_views_without_mutating_the_registered_context(): void
    {
        $registry = new ViewContextRegistry();
        $request = new ViewContextManager($registry);
        $request->registerContext('web', ['base' => 'web']);

        $request->setContextViews(
            'web',
            '/var/www/app/resources/saola/themes/storefront/views'
        );

        $this->assertSame('themes.storefront', $request->getContextViews('web'));
        $this->assertSame(
            'themes.storefront.modules.todo.index',
            $request->resolvePath('web', 'todo', 'index', 'modules')
        );
        $this->assertSame(
            'themes.storefront.layouts.',
            $request->getContextVariable('web', '__layout__')
        );

        $nextRequest = new ViewContextManager($registry);

        $this->assertSame('web', $nextRequest->getContextViews('web'));
        $this->assertSame(
            'web.modules.todo.index',
            $nextRequest->resolvePath('web', 'todo', 'index', 'modules')
        );
    }

    public function test_it_accepts_a_context_to_views_map_for_the_current_request(): void
    {
        $manager = new ViewContextManager();
        $manager->registerContext('web', ['base' => 'web']);
        $manager->registerContext('admin', ['base' => 'admin']);

        $manager->setContextViews([
            'web' => 'themes/storefront',
            'admin' => 'themes.control-panel',
        ]);

        $this->assertSame('themes.storefront.pages.about', $manager->resolvePath('web', '', 'about', 'pages'));
        $this->assertSame('themes.control-panel.layouts.main', $manager->resolvePath('admin', '', 'main', 'layouts'));
    }

    public function test_reset_only_clears_request_state_and_keeps_registry_state(): void
    {
        $manager = new ViewContextManager();
        $manager->registerContext('web', ['base' => 'web']);
        $manager->registerContextViewByRoute('web', 'web.home', 'web.pages.home');
        $manager->setContextViews('web', 'themes.storefront');
        $manager->share('web', ['tenant' => 10]);

        $manager->resetInstanceState();

        $this->assertSame('web', $manager->getContextViews('web'));
        $this->assertSame([], $manager->getSharedData('web'));
        $this->assertSame('web.pages.home', $manager->getViewPathByRoute('web', 'web.home'));
    }

    public function test_laravel_container_creates_a_fresh_manager_for_each_scope(): void
    {
        (new SaolaServiceProvider($this->app))->register();

        $first = $this->app->make(ViewContextManager::class);
        $first->registerContext('web', ['base' => 'web']);
        $first->setContextViews('web', 'themes.first');

        $this->app->forgetScopedInstances();

        $second = $this->app->make(ViewContextManager::class);

        $this->assertNotSame($first, $second);
        $this->assertSame('web', $second->getContextViews('web'));
    }

    public function test_view_storage_reset_clears_hydration_system_data(): void
    {
        $storage = new ViewStorageManager();
        $storage->setSystemData(['theme' => 'storefront']);

        $storage->reset();

        $this->assertSame([], $storage->exportSystemData());
    }

    public function test_static_route_descriptors_are_materialized_per_request_context(): void
    {
        SPA::resetRoutes();
        SPA::active();
        SPA::addRoute('web', 'web.todo.index', '/todo', [
            'kind' => 'explicit',
            'logical' => '@MODULE:todo.index',
            'candidates' => ['@MODULE:todo.index'],
        ]);
        SPA::inactive();

        $registry = new ViewContextRegistry();
        $first = new ViewContextManager($registry);
        $first->registerContext('web', ['base' => 'web']);
        $first->setContextViews('web', 'themes.first');
        $firstRoutes = (new ViewHelperService(new ViewStorageManager(), $first))
            ->exportComponentRoutes('web');

        $second = new ViewContextManager($registry);
        $second->setContextViews('web', 'themes.second');
        $secondRoutes = (new ViewHelperService(new ViewStorageManager(), $second))
            ->exportComponentRoutes('web');

        $this->assertSame('@MODULE:todo.index', SPA::getComponentRoutes('web')[0]['component']['logical']);
        $this->assertSame('web', $firstRoutes[0]['context']);
        $this->assertSame('@MODULE:todo.index', $firstRoutes[0]['logicalComponent']);
        $this->assertSame('themes.first.modules.todo.index', $firstRoutes[0]['component']);
        $this->assertSame('themes.second.modules.todo.index', $secondRoutes[0]['component']);
        $this->assertNotSame(
            $first->getContextViewRevision('web'),
            $second->getContextViewRevision('web')
        );
    }

    public function test_legacy_concrete_route_paths_are_rebased_without_mutating_registry(): void
    {
        $registry = new ViewContextRegistry();
        $manager = new ViewContextManager($registry);
        $manager->registerContext('web', ['base' => 'web']);
        $manager->registerContextViewByRoute('web', 'web.home', 'web.pages.home');
        $manager->setContextViews('web', 'themes.storefront');

        $this->assertSame(
            'themes.storefront.pages.home',
            $manager->resolveRouteComponent('web', 'web.pages.home')
        );
        $this->assertSame('web.pages.home', $registry->get('web')['routeViews']['web.home']['view']);
    }

    public function test_json_response_only_sends_materialized_routes_when_revision_changes(): void
    {
        $manager = $this->app->make(ViewContextManager::class);
        if (!$manager->hasContext('web')) {
            $manager->registerContext('web', ['base' => 'web']);
        }
        $manager->setContextViews('web', 'themes.storefront');
        $this->app->instance(ViewContextManager::class, $manager);
        $this->app->instance(
            ViewHelperService::class,
            new ViewHelperService(new ViewStorageManager(), $manager)
        );

        SPA::resetRoutes();
        SPA::active();
        SPA::addRoute('web', 'web.home', '/', [
            'kind' => 'explicit',
            'logical' => '@PAGE:home',
            'candidates' => ['@PAGE:home'],
        ]);
        SPA::inactive();

        $route = (new \Illuminate\Routing\Route(['GET'], '/', fn () => null))->name('web.home');
        $request = \Illuminate\Http\Request::create('/', 'GET', server: [
            'HTTP_X_SAOLA_VIEW_REVISION' => 'stale-revision',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $controller = new class {
            use ResponseMethods;
            protected string $context = 'web';
        };
        $changed = $controller->response(['ok' => true], null, ['forceJson' => true]);
        $payload = $changed->getData(true);

        $this->assertTrue($payload['viewContext']['changed']);
        $this->assertSame('themes.storefront.pages.home', $payload['viewContext']['component']);
        $this->assertSame('themes.storefront.pages.home', $payload['viewContext']['routes'][0]['component']);
        $this->assertSame('themes.storefront.pages.home', $payload['view']);

        $request->headers->set('X-Saola-View-Revision', $payload['viewContext']['revision']);
        $unchanged = $controller->response(['ok' => true], null, ['forceJson' => true]);
        $unchangedPayload = $unchanged->getData(true);

        $this->assertFalse($unchangedPayload['viewContext']['changed']);
        $this->assertArrayNotHasKey('routes', $unchangedPayload['viewContext']);
        $this->assertArrayNotHasKey('systemData', $unchangedPayload['viewContext']);
    }
}
