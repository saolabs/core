<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Saola\Core\Http\Middleware\Next;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Routing\Module;
use Saola\Core\System;
use Tests\TestCase;

/** Packages add builder methods with Router::macro(); routes() reads as group(); an unknown method is an error, not a silent no-op. */
class RouterMacroTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        System::reset();
        (new SaolaServiceProvider($this->app))->register();
        Route::aliasMiddleware('next', Next::class);
    }

    protected function tearDown(): void
    {
        System::reset();
        parent::tearDown();
    }

    public function test_macro_routes_alias_and_unknown_method(): void
    {
        Module::macro('tagWith', function (string $tag) { $this->data['description'] = $tag; return $this; });
        $module = System::context('web', ['as' => 'web', 'prefix' => '/'])->module('shop')->as('shop')
            ->tagWith('catalog')
            ->routes(fn ($m) => $m->get('/', fn () => 'ok')->name('index'));
        $this->assertSame('catalog', $module->getRouteDataValue('description'));

        System::context('web')->pushLaravelRoute();
        Route::getRoutes()->refreshNameLookups();
        $this->assertTrue(Route::has('web.shop.index'));

        $this->expectException(\BadMethodCallException::class);
        $module->lisst(['typo']);
    }
}
