<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\View;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\Support\Methods\ViewMethods;
use Tests\TestCase;

/**
 * Path controller truyền vào response() phải ra CÙNG một view ở cả hai nhánh.
 *
 * Nhánh HTML đi getBladeViewRenderConfig() → render*(); nhánh JSON trước đây đi
 * resolvePathByAlias() — hai đường khác nhau, và chúng lệch ở `@RAW:`: nhánh
 * JSON trả `modules.ping.index` trần, không có base nào cả.
 */
class ResponseViewPathTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        (new SaolaServiceProvider($this->app))->register();
        app(ViewContextManager::class)->registerContext('web', ['base' => 'web']);

        $this->root = sys_get_temp_dir() . '/sao-rvp-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/web/modules/roster', 0777, true);
        mkdir($this->root . '/themes/aurora/modules/ping', 0777, true);
        file_put_contents($this->root . '/web/modules/roster/index.blade.php', 'base');
        file_put_contents($this->root . '/themes/aurora/modules/ping/index.blade.php', 'theme');
        View::getFinder()->addLocation($this->root);
    }

    protected function tearDown(): void
    {
        @unlink($this->root . '/web/modules/roster/index.blade.php');
        @unlink($this->root . '/themes/aurora/modules/ping/index.blade.php');
        foreach ([
            '/web/modules/roster', '/web/modules', '/web',
            '/themes/aurora/modules/ping', '/themes/aurora/modules', '/themes/aurora', '/themes', '',
        ] as $d) {
            @rmdir($this->root . $d);
        }
        parent::tearDown();
    }

    private function controller(): object
    {
        // Trait đã khai $context/$module; ghi đè bằng property trùng tên là lỗi
        // composition, nên set qua constructor.
        return new class {
            use ViewMethods, ResponseMethods;

            public function __construct()
            {
                $this->context = 'web';
                $this->module = 'roster';
            }
        };
    }

    public function test_alias_map_dung_cap_module_type_cua_tung_render(): void
    {
        $c = $this->controller();

        $this->assertSame('web.modules.roster.index', $c->resolveViewPath('@MODULE:index'));
        $this->assertSame('web.pages.about', $c->resolveViewPath('@PAGE:about'));
        $this->assertSame('web.components.button', $c->resolveViewPath('@COMPONENT:button'));
        $this->assertSame('web.layouts.public', $c->resolveViewPath('@LAYOUT:public'));
        $this->assertSame('web.templates.default', $c->resolveViewPath('@TEMPLATE:default'));
    }

    public function test_raw_va_path_tran_deu_lay_base_cua_context(): void
    {
        $c = $this->controller();

        // Lỗi cũ: resolvePathByAlias trả 'modules.ping.index' TRẦN, không base.
        $this->assertSame('web.modules.ping.index', $c->resolveViewPath('@RAW:modules.ping.index'));
        $this->assertSame('web.modules.roster.index', $c->resolveViewPath('modules.roster.index'));
    }

    public function test_ten_tran_khong_dau_cham_la_view_thang_duoi_base(): void
    {
        $c = $this->controller();

        $this->assertSame('web.demo', $c->resolveViewPath('demo'));

        app(\Saola\Core\Engines\ViewContextManager::class)->setContextViews('web', 'test-web.demo');
        $this->assertSame('test-web.demo.demo', $c->resolveViewPath('demo'));
    }

    public function test_moi_context_co_base_rieng_khong_dam_vao_nhau(): void
    {
        $m = app(\Saola\Core\Engines\ViewContextManager::class);
        $m->registerContext('admin', ['base' => 'admin']);
        $m->setContextViews(['web' => 'test-web.demo', 'admin' => 'test-admin.dark']);

        $this->assertSame('test-web.demo.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
        $this->assertSame('test-admin.dark.modules.users.index', $m->resolvePath('admin', 'users', 'index', 'modules'));

        // Cặp đường rơi cũng phải tách theo context, nếu không client của admin
        // sẽ rơi về base của web.
        $this->assertSame('web', $m->exportContextState('web')['systemData']['__view_fallback_to__']);
        $this->assertSame('admin', $m->exportContextState('admin')['systemData']['__view_fallback_to__']);
        $this->assertSame('admin.modules.users.index', $m->fallbackViewName('test-admin.dark.modules.users.index'));
    }

    public function test_theme_bat_thi_path_di_theo_base_cua_theme(): void
    {
        app(ViewContextManager::class)->setContextViews('web', 'themes.aurora');
        $c = $this->controller();

        $this->assertSame('themes.aurora.modules.roster.index', $c->resolveViewPath('@MODULE:index'));
        $this->assertSame('themes.aurora.modules.ping.index', $c->resolveViewPath('@RAW:modules.ping.index'));
    }

    public function test_forceView_thang_ca_header_json(): void
    {
        // `forceView` được tài liệu hoá từ đầu nhưng không nhánh nào đọc — ép
        // trả view là bất khả thi cho tới bản vá này.
        $request = \Illuminate\Http\Request::create('/x', 'GET');
        $request->headers->set('X-Sao-Response', 'json');
        app()->instance('request', $request);

        $json = $this->controller()->response([], '@MODULE:index');
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $json);

        $view = $this->controller()->response([], '@MODULE:index', ['forceView' => true]);
        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $this->assertSame('web.modules.roster.index', $view->name());
    }

    public function test_forceView_ma_khong_co_view_thi_bao_loi_thay_vi_am_tham_tra_json(): void
    {
        $request = \Illuminate\Http\Request::create('/x', 'GET');
        app()->instance('request', $request);

        $this->expectException(\RuntimeException::class);

        // Không route name, không path ⇒ không resolve được view nào.
        $this->controller()->response([], null, ['forceView' => true]);
    }

    public function test_khoa_json_gui_client_bam_theo_view_theme_that_su_co(): void
    {
        $manager = app(ViewContextManager::class);
        $manager->setContextViews('web', 'themes.aurora');

        // Theme CÓ ping ⇒ khoá theme; theme KHÔNG có roster ⇒ khoá base, đúng
        // thứ registry JS đang mang.
        $this->assertSame(
            'themes.aurora.modules.ping.index',
            $manager->resolveClientViewKey($this->controller()->resolveViewPath('@RAW:modules.ping.index'))
        );
        $this->assertSame(
            'web.modules.roster.index',
            $manager->resolveClientViewKey($this->controller()->resolveViewPath('@MODULE:index'))
        );
    }
}
