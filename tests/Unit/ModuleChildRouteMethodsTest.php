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

    public function test_module_config_va_group_callback_giu_dung_kieu_va_route(): void
    {
        $module = System::context('admin')->module(['slug' => 'providers', 'prefix' => 'providers'])
            ->controller(self::class)->title('Provider')->displayName('Provider');

        $this->assertInstanceOf(\Saola\Core\Routing\Module::class, $module);
        $result = $module->group(function (\Saola\Core\Routing\Module $m) use ($module) {
            $this->assertSame($module, $m);
            foreach (['get', 'post', 'put', 'delete'] as $method) {
                $m->{$method}('/{id}', 'edit')->name($method)->viewModule('form');
            }
            $m->module(['slug' => 'nested', 'prefix' => 'nested'])
                ->group(fn ($nested) => $nested->get('/', 'index')->name('index'));
        });

        $this->assertSame($module, $result);
        $data = $module->toArray();
        $this->assertSame('providers', $data['slug']);
        $this->assertSame('providers', $data['prefix']);
        $this->assertCount(5, $data['children']);
        $this->assertSame(['get', 'post', 'put', 'delete'], array_column(array_slice($data['children'], 0, 4), 'method'));
        $this->assertCount(1, $data['children'][4]['children']);
    }

    public function test_module_ho_tro_slug_callback_va_tra_cuu_module_cu(): void
    {
        $context = System::context('web');
        $module = $context->module('site', fn ($m) => $m->get('/', 'index'));
        $this->assertSame($module, $context->module('site'));
        $this->assertCount(1, $module->toArray()['children']);
        $other = $context->module('other', ['prefix' => '/custom']);
        $this->assertSame('/custom', $other->toArray()['prefix']);
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
