<?php

namespace Tests\Unit;

use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Engines\ViewContextRegistry;
use Saola\Core\Providers\SaolaServiceProvider;
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
}
