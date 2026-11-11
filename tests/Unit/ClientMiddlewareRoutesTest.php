<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Saola\Core\Http\Middleware\Next;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Support\SPA;
use Saola\Core\System;
use Tests\TestCase;

class ClientMiddlewareRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        System::reset(); SPA::resetRoutes(); SPA::active();
        (new SaolaServiceProvider($this->app))->register();
        Route::aliasMiddleware('next', Next::class);
    }

    protected function tearDown(): void
    {
        System::reset(); SPA::resetRoutes(); SPA::inactive();
        parent::tearDown();
    }

    public function test_client_middleware_is_inherited_and_exported_without_changing_server_middleware(): void
    {
        System::context('web', ['as'=>'web','prefix'=>'/'])->clientMiddleware('locale')
            ->module(['slug'=>'account','prefix'=>'account'])->as('account')->clientMiddleware(['auth:web'])
            ->group(function ($module) {
                $module->get('/', fn ()=>'ok')->name('show')->middleware('auth:web')
                    ->clientMiddleware('verified', 'profile:complete')->view('web.account');
                $module->module(['slug'=>'nested','prefix'=>'nested'])->as('nested')->clientMiddleware('audit')
                    ->group(fn ($nested) => $nested->get('/', fn ()=>'ok')->name('index')->view('web.nested'));
            });
        System::context('web')->pushLaravelRoute();
        Route::getRoutes()->refreshNameLookups();
        $routes = array_column(SPA::getRoutes('web'), null, 'name');
        $this->assertSame(['locale','auth:web','verified','profile:complete'], $routes['web.account.show']['meta']['middleware']);
        $this->assertSame(['locale','auth:web','audit'], $routes['web.account.nested.index']['meta']['middleware']);
        $server = Route::getRoutes()->getByName('web.account.show')->gatherMiddleware();
        $this->assertContains('auth:web', $server);
        $this->assertNotContains('locale', $server);
        $this->assertNotContains('verified', $server);
        $this->assertSame($routes['web.account.show']['meta'], SPA::getComponentRoutes('web')[0]['meta']);
    }

    public function test_existing_routes_without_client_middleware_keep_the_same_export_shape(): void
    {
        SPA::addRoute('web', 'web.home', '/', 'web.home');
        $this->assertSame(['context'=>'web','name'=>'web.home','path'=>'/','params'=>[],'component'=>'web.home'], SPA::getRoutes('web')[0]);
    }

    public function test_rejects_php_callbacks_instead_of_exposing_server_implementation_to_the_browser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        System::context('web')->clientMiddleware([fn () => true]);
    }
}
