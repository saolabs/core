<?php

namespace Tests;

use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Octane;
use Saola\Core\System\System;
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

        if (property_exists(System::class, '_appinfo')) {
            System::$_appinfo = ['test' => 'data'];
        }

        $this->app['events']->dispatch(new RequestTerminated($this->app, $this->app, request(), new \Illuminate\Http\Response()));

        if (property_exists(System::class, '_appinfo')) {
            $this->assertNull(System::$_appinfo);
        }
    }
}
