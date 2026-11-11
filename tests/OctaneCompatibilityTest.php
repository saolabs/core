<?php

namespace Tests;

use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\WorkerStarting;
use Laravel\Octane\Octane;
use Saola\Core\Engines\ViewDataEngine;
use Saola\Core\Engines\ViewManager;
use Saola\Core\Providers\OctaneServiceProvider;
use Tests\TestCase;

class OctaneCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('octane', new \stdClass());
        $provider = new OctaneServiceProvider($this->app);
        $provider->register();
        $provider->boot();
    }

    public function test_octane_service_provider_registers_correctly()
    {
        $this->assertTrue(class_exists(OctaneServiceProvider::class));
        $provider = new OctaneServiceProvider($this->app);
        $this->assertInstanceOf(OctaneServiceProvider::class, $provider);
    }

    public function test_no_state_leakage_when_registering_octane_service_provider()
    {
        if (!class_exists(Octane::class)) {
            $this->markTestSkipped('Laravel Octane is not installed.');
            return;
        }

        ViewManager::$shared = true;
        $this->app['events']->dispatch(new RequestTerminated($this->app, $this->app, request(), new \Illuminate\Http\Response()));
        $this->assertFalse(ViewManager::$shared);
    }

    public function test_static_state_is_properly_reset()
    {
        if (!class_exists(Octane::class)) {
            $this->markTestSkipped('Laravel Octane is not installed.');
            return;
        }

        ViewManager::$themeFolder = 'themes/storefront';
        ViewDataEngine::$shared = true;

        $this->app['events']->dispatch(new RequestTerminated($this->app, $this->app, request(), new \Illuminate\Http\Response()));

        $this->assertSame('', ViewManager::$themeFolder);
        $this->assertFalse(ViewDataEngine::$shared);
    }

    public function test_request_shared_view_data_does_not_survive_normal_or_error_responses()
    {
        $view = $this->app['view'];
        $view->share('worker_brand', 'SaoLabs');
        $this->app['events']->dispatch(new WorkerStarting($this->app));

        foreach ([200, 500] as $status) {
            $request = \Illuminate\Http\Request::create('/next');
            $this->app['events']->dispatch(new RequestReceived($this->app, $this->app, $request));
            ViewManager::share('request_customer', 'previous-user');
            $view->share('worker_brand', 'request-override');
            $this->app['events']->dispatch(new RequestTerminated(
                $this->app, $this->app, $request, new \Illuminate\Http\Response('', $status)
            ));

            $this->assertNull($view->shared('request_customer'));
            $this->assertSame('SaoLabs', $view->shared('worker_brand'));
        }
    }
}
