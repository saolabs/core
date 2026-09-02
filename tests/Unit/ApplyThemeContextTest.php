<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Saola\Core\Http\Middleware\ApplyTheme;
use Saola\Core\Routing\Registry;
use Tests\TestCase;

/**
 * MỌI context đều đặt được view base riêng, nên middleware phải áp đúng context
 * của request — không mặc định `web` cho tất cả.
 *
 * Nguồn tin cậy là segment đầu của route name (`admin.users.index` → `admin`),
 * đúng quy ước mà routeToViewPathConfig() dùng để tách module. `spa_scope` chỉ
 * là đường lùi: SPAScopeMiddleware có trong core nhưng KHÔNG được gắn ở đâu cả.
 */
class ApplyThemeContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Registry::reset();
        Registry::context('web', ['as' => 'web']);
        Registry::context('admin', ['as' => 'admin']);
    }

    protected function tearDown(): void
    {
        Registry::reset();
        parent::tearDown();
    }

    private function contextFor(?string $routeName, ?string $spaScope = null): string
    {
        $request = Request::create('/x', 'GET');
        if ($spaScope !== null) {
            $request->attributes->set('spa_scope', $spaScope);
        }
        if ($routeName !== null) {
            $route = (new Route(['GET'], '/x', fn () => ''))->name($routeName);
            $request->setRouteResolver(fn () => $route);
        }

        $middleware = new class extends ApplyTheme {
            public function pick(Request $request): string
            {
                return $this->contextFor($request);
            }
        };

        return $middleware->pick($request);
    }

    public function test_lay_context_tu_segment_dau_cua_route_name(): void
    {
        $this->assertSame('web', $this->contextFor('web.abc.index'));
        $this->assertSame('admin', $this->contextFor('admin.users.index'));
        $this->assertSame('admin', $this->contextFor('admin.catalog.brands.index'));
    }

    public function test_tien_to_khong_phai_context_thi_khong_nhan(): void
    {
        // Route của Laravel/package: `password.request` không phải context nào.
        $this->assertSame('web', $this->contextFor('password.request'));
        $this->assertSame('web', $this->contextFor('sanctum.csrf-cookie'));
    }

    public function test_khong_co_route_name_thi_lui_ve_spa_scope_roi_web(): void
    {
        $this->assertSame('admin', $this->contextFor(null, 'admin'));
        $this->assertSame('web', $this->contextFor(null));
        $this->assertSame('web', $this->contextFor('khongcodauCham'));
    }
}
