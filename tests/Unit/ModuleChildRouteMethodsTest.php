<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Saola\Core\Http\Middleware\Next;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\System;
use Tests\TestCase;

/**
 * any()/match()/defaults() trên route con của module.
 *
 * any() từng là method thật nên alias addChild của module không bao giờ chạy:
 * nó ghi đè dữ liệu của chính module. match() không vào được nhánh HTTP và không
 * bật is_match. defaults([...]) bị đẩy nguyên mảng vào Route::defaults($key, $value).
 */
class ModuleChildRouteMethodsTest extends TestCase
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

    private function push(callable $routes): void
    {
        System::context('web', ['as' => 'web', 'prefix' => '/'])
            ->module(['slug' => 'site', 'prefix' => '/', 'priority' => 1])
            ->as('site')
            ->group($routes);
        System::context('web')->pushLaravelRoute();
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_any_tren_module_tao_route_con_nhan_moi_method(): void
    {
        $this->push(function ($module) {
            $module->any('/hook', fn () => 'any-ok')->name('hook');
        });

        $route = Route::getRoutes()->getByName('web.site.hook');
        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route->methods());
        $this->post('/hook')->assertOk()->assertSee('any-ok');
        $this->delete('/hook')->assertOk();
        // Module vẫn là module, không bị biến thành route.
        $this->assertSame('module', System::context('web')->toArray()['children'][0]['type']);
    }

    public function test_match_tren_module_chi_nhan_cac_method_khai_bao(): void
    {
        $this->push(function ($module) {
            $module->match(['GET', 'POST'], '/form', fn () => 'match-ok')->name('form');
        });

        $this->assertSame(['GET', 'POST', 'HEAD'], Route::getRoutes()->getByName('web.site.form')->methods());
        $this->post('/form')->assertOk()->assertSee('match-ok');
        $this->put('/form')->assertStatus(405);
    }

    public function test_defaults_dang_mang_toi_duoc_route_laravel(): void
    {
        $this->push(function ($module) {
            $module->get('/go/{page?}', fn (string $page, string $tone) => "$page:$tone")->name('go')
                ->defaults(['page' => 'home'])->defaults('tone', 'calm');
        });

        $this->assertSame(['page' => 'home', 'tone' => 'calm'], Route::getRoutes()->getByName('web.site.go')->defaults);
        $this->get('/go')->assertOk()->assertSee('home:calm');
        $this->get('/go/faq')->assertOk()->assertSee('faq:calm');
    }
}
