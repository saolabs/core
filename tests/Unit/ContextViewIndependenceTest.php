<?php

namespace Tests\Unit;

use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Engines\ViewContextRegistry;
use Tests\TestCase;

/**
 * Mỗi context đặt base HOẶC bộ thư mục custom riêng, hoàn toàn độc lập.
 *
 * Độc lập ở đây là thật: mỗi context một ô riêng trong $contextViewOverrides,
 * nên dạng khai báo, base, và từng thư mục con đều không dính sang context khác.
 */
class ContextViewIndependenceTest extends TestCase
{
    private function manager(): ViewContextManager
    {
        $m = new ViewContextManager(new ViewContextRegistry());
        foreach (['web', 'admin', 'api'] as $c) {
            $m->registerContext($c, ['base' => $c]);
        }

        return $m;
    }

    public function test_moi_context_mot_base_rieng(): void
    {
        $m = $this->manager();
        $m->setContextViews([
            'web' => 'test-web.demo',
            'admin' => 'test-admin.dark',
            'api' => 'test-api.v2',
        ]);

        $this->assertSame('test-web.demo.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
        $this->assertSame('test-admin.dark.modules.abc.index', $m->resolvePath('admin', 'abc', 'index', 'modules'));
        $this->assertSame('test-api.v2.modules.abc.index', $m->resolvePath('api', 'abc', 'index', 'modules'));
    }

    public function test_custom_tung_thu_muc_khoa_thieu_thi_suy_tu_base(): void
    {
        $m = $this->manager();
        $m->setContextViews('web', [
            'base' => 'test-web.demo',
            'components' => 'shared.ui',       // dùng chung cho mọi bản giao diện
            'layouts' => 'web.layouts',        // giữ vỏ cũ
        ]);

        $this->assertSame('shared.ui.button', $m->resolvePath('web', '', 'button', 'components'));
        $this->assertSame('web.layouts.public', $m->resolvePath('web', '', 'public', 'layouts'));
        // Khoá không khai → suy từ base.
        $this->assertSame('test-web.demo.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
        $this->assertSame('test-web.demo.pages.about', $m->resolvePath('web', '', 'about', 'pages'));
        $this->assertSame('test-web.demo.home', $m->resolvePath('web', '', 'home', 'base'));
    }

    public function test_hai_context_dung_hai_DANG_khai_bao_khac_nhau(): void
    {
        $m = $this->manager();
        $m->setContextViews([
            'web' => ['base' => 'test-web.demo', 'components' => 'shared.ui'],
            'admin' => 'test-admin.dark',
        ]);

        $this->assertSame('shared.ui.button', $m->resolvePath('web', '', 'button', 'components'));
        $this->assertSame('test-admin.dark.components.button', $m->resolvePath('admin', '', 'button', 'components'));
    }

    public function test_bien_dai_dien_va_duong_roi_cung_tach_theo_context(): void
    {
        $m = $this->manager();
        $m->setContextViews(['web' => 'test-web.demo', 'admin' => 'test-admin.dark']);

        $this->assertSame('shared.ui.', $m->setContextViews('web', ['base' => 'test-web.demo', 'components' => 'shared.ui'])
            ->getContextVariable('web', '__component__'));
        $this->assertSame('test-admin.dark.components.', $m->getContextVariable('admin', '__component__'));

        // Client của admin phải rơi về base của admin, không phải của web.
        $this->assertSame('admin', $m->exportContextState('admin')['systemData']['__view_fallback_to__']);
        $this->assertSame('web', $m->exportContextState('web')['systemData']['__view_fallback_to__']);
        $this->assertSame('admin.modules.x.y', $m->fallbackViewName('test-admin.dark.modules.x.y'));
        $this->assertSame('web.modules.x.y', $m->fallbackViewName('test-web.demo.modules.x.y'));
    }

    public function test_clear_mot_context_khong_dung_toi_context_khac(): void
    {
        $m = $this->manager();
        $m->setContextViews(['web' => 'test-web.demo', 'admin' => 'test-admin.dark']);

        $m->clearContextViews('web');

        $this->assertSame('web.modules.abc.index', $m->resolvePath('web', 'abc', 'index', 'modules'));
        $this->assertSame('test-admin.dark.modules.abc.index', $m->resolvePath('admin', 'abc', 'index', 'modules'));
    }

    public function test_thu_muc_custom_rong_hoac_sai_kieu_thi_bao_loi_ngay(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->manager()->setContextViews('web', ['base' => 'test-web.demo', 'components' => '']);
    }
}
