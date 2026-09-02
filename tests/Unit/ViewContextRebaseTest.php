<?php

namespace Tests\Unit;

use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Engines\ViewContextRegistry;
use Tests\TestCase;

/**
 * Đổi base của một context phải kéo theo mọi thư mục con.
 *
 * Bẫy cũ: ViewContextServiceProvider khai đủ 7 key với tiền tố 'web.', mà
 * registerContext() chỉ array_merge — nên đăng ký lại với base 'themes.x' chỉ
 * đổi được 'base', còn modules/pages/layouts vẫn ghim ở 'web.*'. Trong khi biến
 * đại diện (__module__…) lại suy từ base MỚI, nên hai nửa trỏ hai nơi.
 */
class ViewContextRebaseTest extends TestCase
{
    /** Y như core/src/core/Providers/ViewContextServiceProvider::registerContexts(). */
    private function manager(): ViewContextManager
    {
        $manager = new ViewContextManager(new ViewContextRegistry());
        $manager->registerContext('web', [
            'base' => 'web',
            'components' => 'web.components',
            'templates' => 'web.templates',
            'partials' => 'web.partials',
            'modules' => 'web.modules',
            'layouts' => 'web.layouts',
            'pages' => 'web.pages',
        ]);

        return $manager;
    }

    public function test_doi_base_keo_theo_thu_muc_con(): void
    {
        $manager = $this->manager();

        $manager->registerContext('web', ['base' => 'themes.storefront']);

        $this->assertSame('themes.storefront.home', $manager->resolvePath('web', '', 'home', 'base'));
        $this->assertSame('themes.storefront.modules.roster.index', $manager->resolvePath('web', 'roster', 'index', 'modules'));
        $this->assertSame('themes.storefront.pages.about', $manager->resolvePath('web', '', 'about', 'pages'));
        $this->assertSame('themes.storefront.layouts.main', $manager->resolvePath('web', '', 'main', 'layouts'));
        $this->assertSame('themes.storefront.templates.default', $manager->resolvePath('web', '', 'default', 'templates'));
    }

    public function test_bien_dai_dien_va_thu_muc_khong_con_lech_nhau(): void
    {
        $manager = $this->manager();

        $manager->registerContext('web', ['base' => 'themes.storefront']);

        $this->assertSame('themes.storefront.modules.', $manager->getContextVariable('web', '__module__'));
        $this->assertSame('themes.storefront.modules', $manager->getBaseDirectory('web', 'modules'));
    }

    public function test_thu_muc_dat_tay_ngoai_base_thi_giu_nguyen(): void
    {
        $manager = $this->manager();
        // components dùng chung cho mọi theme — lựa chọn có chủ đích.
        $manager->registerContext('web', ['base' => 'web', 'components' => 'shared.components']);

        $manager->registerContext('web', ['base' => 'themes.storefront']);

        $this->assertSame('shared.components.button', $manager->resolvePath('web', '', 'button', 'components'));
        $this->assertSame('themes.storefront.modules.roster.index', $manager->resolvePath('web', 'roster', 'index', 'modules'));
    }

    public function test_context_chi_khai_base_van_suy_du_qua_fallback(): void
    {
        $manager = new ViewContextManager(new ViewContextRegistry());
        $manager->registerContext('web', ['base' => 'themes.storefront']);

        $this->assertSame('themes.storefront.modules.roster.index', $manager->resolvePath('web', 'roster', 'index', 'modules'));
        $this->assertSame('themes.storefront.pages.about', $manager->resolvePath('web', '', 'about', 'pages'));
    }

    public function test_setContextViews_van_la_duong_theo_request_va_khong_dung_registry(): void
    {
        $manager = $this->manager();

        $manager->setContextViews('web', 'themes.storefront');
        $this->assertSame('themes.storefront.modules.roster.index', $manager->resolvePath('web', 'roster', 'index', 'modules'));

        $manager->clearContextViews('web');
        $this->assertSame('web.modules.roster.index', $manager->resolvePath('web', 'roster', 'index', 'modules'));
    }
}
