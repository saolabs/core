<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\View;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Engines\ViewContextRegistry;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\View\Finders\ThemeAwareViewFinder;
use Tests\TestCase;

/**
 * Theme chỉ cần mang những view nó muốn đổi.
 *
 * Không có đường rơi này thì theme phải là bản sao ĐẦY ĐỦ: `__layout__`,
 * `__partial__`… là tiền tố của cả context nên mọi `@extends`/`@include` đều
 * trỏ vào `themes.{slug}.*`, thiếu một file là cả trang 500.
 *
 * Hai phía phải rơi ĐỘC LẬP và ra cùng kết quả: SSR qua finder, client qua cặp
 * `__view_fallback_from__/to__` trong systemData.
 */
class ThemeFallbackTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        (new SaolaServiceProvider($this->app))->register();

        $this->root = sys_get_temp_dir() . '/sao-fb-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/web/layouts', 0777, true);
        mkdir($this->root . '/themes/aurora/modules/ping', 0777, true);
        file_put_contents($this->root . '/web/layouts/workspace.blade.php', 'base layout');
        file_put_contents($this->root . '/themes/aurora/modules/ping/index.blade.php', 'theme page');
        View::getFinder()->addLocation($this->root);
    }

    protected function tearDown(): void
    {
        foreach ([
            '/web/layouts/workspace.blade.php', '/themes/aurora/modules/ping/index.blade.php',
        ] as $f) {
            @unlink($this->root . $f);
        }
        foreach ([
            '/web/layouts', '/web', '/themes/aurora/modules/ping', '/themes/aurora/modules',
            '/themes/aurora', '/themes', '',
        ] as $d) {
            @rmdir($this->root . $d);
        }
        parent::tearDown();
    }

    private function manager(): ViewContextManager
    {
        $manager = app(ViewContextManager::class);
        $manager->registerContext('web', ['base' => 'web']);
        $manager->setContextViews('web', 'themes.aurora');

        return $manager;
    }

    public function test_finder_roi_ve_base_khi_theme_khong_de_view(): void
    {
        $this->manager();

        // Theme không có layout này; SSR vẫn phải render được.
        $this->assertTrue(view()->exists('themes.aurora.layouts.workspace'));
        $this->assertStringEndsWith(
            'web/layouts/workspace.blade.php',
            View::getFinder()->find('themes.aurora.layouts.workspace')
        );
    }

    public function test_kiem_nghiem_van_phan_biet_duoc_file_that(): void
    {
        $this->manager();
        $finder = View::getFinder();
        $this->assertInstanceOf(ThemeAwareViewFinder::class, $finder);

        $this->assertTrue($finder->existsWithoutFallback('themes.aurora.modules.ping.index'));
        $this->assertFalse($finder->existsWithoutFallback('themes.aurora.layouts.workspace'));
    }

    public function test_khoa_gui_cho_client_bam_theo_view_theme_that_su_co(): void
    {
        $manager = $this->manager();

        // Theme đè trang này ⇒ dùng khoá theme, registry JS có nó.
        $this->assertSame(
            'themes.aurora.modules.ping.index',
            $manager->resolveClientViewKey('themes.aurora.modules.ping.index')
        );
        // Theme không đè ⇒ khoá base, đúng thứ registry JS đang có.
        $this->assertSame(
            'web.layouts.workspace',
            $manager->resolveClientViewKey('themes.aurora.layouts.workspace')
        );
    }

    public function test_fallbackViewName_chi_doi_tien_to_cua_theme_dang_bat(): void
    {
        $manager = $this->manager();

        $this->assertSame('web.layouts.workspace', $manager->fallbackViewName('themes.aurora.layouts.workspace'));
        $this->assertNull($manager->fallbackViewName('web.layouts.workspace'));
        $this->assertNull($manager->fallbackViewName('themes.khac.layouts.workspace'));
    }

    public function test_systemData_mang_cap_duong_roi_cho_client(): void
    {
        $manager = $this->manager();
        $systemData = $manager->exportContextState('web')['systemData'];

        $this->assertSame('themes.aurora', $systemData['__view_fallback_from__']);
        $this->assertSame('web', $systemData['__view_fallback_to__']);
    }

    public function test_khong_co_theme_thi_khong_gui_cap_nao_ca(): void
    {
        $manager = app(ViewContextManager::class);
        $manager->registerContext('web', ['base' => 'web']);

        $systemData = $manager->exportContextState('web')['systemData'];

        $this->assertArrayNotHasKey('__view_fallback_from__', $systemData);
        $this->assertNull($manager->fallbackViewName('web.layouts.workspace'));
    }
}
