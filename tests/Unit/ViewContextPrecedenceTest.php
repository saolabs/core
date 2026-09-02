<?php

namespace Tests\Unit;

use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Engines\ViewContextRegistry;
use Tests\TestCase;

/**
 * Thứ tự ưu tiên của view context, thấp đến cao:
 *
 *   1. core  — mặc định hệ thống, base = slug context
 *   2. app   — registerContext() ở provider của ứng dụng (cấp worker)
 *   3. theme — setContextViews() mỗi request (cao nhất)
 *
 * Hai tầng dưới cùng ghi vào registry singleton nên merge theo khoá — ai chạy
 * sau thắng. Tầng theme nằm ở lớp override riêng nên không phá hai tầng kia:
 * tắt theme là rơi lại đúng khai báo của app.
 */
class ViewContextPrecedenceTest extends TestCase
{
    private function manager(): ViewContextManager
    {
        $m = new ViewContextManager(new ViewContextRegistry());
        // Tầng 1 — y hệt ViewContextServiceProvider của core.
        $m->registerContext('web', ['base' => 'web']);

        return $m;
    }

    public function test_core_chi_khai_base_van_suy_du_bay_thu_muc(): void
    {
        $m = $this->manager();

        $this->assertSame('web.home', $m->resolvePath('web', '', 'home', 'base'));
        $this->assertSame('web.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
        $this->assertSame('web.pages.about', $m->resolvePath('web', '', 'about', 'pages'));
        $this->assertSame('web.components.button', $m->resolvePath('web', '', 'button', 'components'));
        $this->assertSame('web.layouts.public', $m->resolvePath('web', '', 'public', 'layouts'));
        $this->assertSame('web.templates.default', $m->resolvePath('web', '', 'default', 'templates'));
        $this->assertSame('web.partials.head', $m->resolvePath('web', '', 'head', 'partials'));
    }

    public function test_app_de_len_core(): void
    {
        $m = $this->manager();

        // Tầng 2 — app đổi base; sáu thư mục con phải đi theo.
        $m->registerContext('web', ['base' => 'storefront']);

        $this->assertSame('storefront.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
        $this->assertSame('storefront.layouts.public', $m->resolvePath('web', '', 'public', 'layouts'));
    }

    public function test_app_de_rieng_mot_thu_muc_phan_con_lai_bam_base(): void
    {
        $m = $this->manager();

        $m->registerContext('web', ['base' => 'web', 'components' => '_shared.components']);

        $this->assertSame('_shared.components.button', $m->resolvePath('web', '', 'button', 'components'));
        $this->assertSame('web.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
    }

    public function test_theme_de_len_ca_core_lan_app_va_tat_thi_tra_lai_app(): void
    {
        $m = $this->manager();
        $m->registerContext('web', ['base' => 'storefront']);          // app

        $m->setContextViews('web', 'themes.aurora');                    // theme
        $this->assertSame('themes.aurora.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));

        // Tắt theme ⇒ rơi lại khai báo của APP, không phải mặc định của core.
        $m->clearContextViews('web');
        $this->assertSame('storefront.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
    }

    public function test_duong_roi_cua_theme_tro_ve_base_cua_app(): void
    {
        $m = $this->manager();
        $m->registerContext('web', ['base' => 'storefront']);
        $m->setContextViews('web', 'themes.aurora');

        // Theme không đè view này ⇒ rơi về base của app, không phải 'web'.
        $this->assertSame('storefront.layouts.public', $m->fallbackViewName('themes.aurora.layouts.public'));
        $this->assertSame('storefront', $m->exportContextState('web')['systemData']['__view_fallback_to__']);
    }
}
