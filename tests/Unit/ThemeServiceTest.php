<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Services\BundleManifest;
use Saola\Core\Services\ThemeService;
use Tests\TestCase;

class ThemeServiceTest extends TestCase
{
    private string $viewRoot;
    private BundleManifest $bundleManifest;

    protected function setUp(): void
    {
        parent::setUp();

        (new SaolaServiceProvider($this->app))->register();
        app(ViewContextManager::class)->registerContext('web', [
            'base' => 'web',
            'components' => 'web.components',
            'modules' => 'web.modules',
            'pages' => 'web.pages',
            'layouts' => 'web.layouts',
            'templates' => 'web.templates',
            'partials' => 'web.partials',
        ]);

        $this->viewRoot = sys_get_temp_dir() . '/sao-theme-' . bin2hex(random_bytes(4));
        mkdir($this->viewRoot . '/themes/storefront', 0777, true);
        mkdir($this->viewRoot . '/packages/storefront/dist', 0777, true);
        file_put_contents($this->viewRoot . '/packages/storefront/dist/theme.json', json_encode([
            'slug' => 'storefront',
            'contract' => 1,
            'idMode' => 'terse',
            'revision' => 'theme-rev-1',
            'context' => 'web',
        ], JSON_THROW_ON_ERROR));
        config()->set('sao.themes.path', $this->viewRoot . '/packages');
        $this->bundleManifest = new class extends BundleManifest {
            public array $info = ['contract' => 1, 'idMode' => 'terse'];

            public function buildInfo(): array
            {
                return $this->info;
            }
        };
        $this->app->instance(BundleManifest::class, $this->bundleManifest);
        View::getFinder()->addLocation($this->viewRoot);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink($this->viewRoot . '/packages/storefront/dist/theme.json');
        @rmdir($this->viewRoot . '/packages/storefront/dist');
        @rmdir($this->viewRoot . '/packages/storefront');
        @rmdir($this->viewRoot . '/packages');
        @rmdir($this->viewRoot . '/themes/storefront');
        @rmdir($this->viewRoot . '/themes');
        @rmdir($this->viewRoot);
        parent::tearDown();
    }

    private function theme(): ThemeService
    {
        return app(ThemeService::class);
    }

    private function modulePath(): string
    {
        return app(ViewContextManager::class)->resolvePath('web', 'roster', 'index', 'modules');
    }

    public function test_khong_co_theme_thi_giu_base_goc(): void
    {
        $this->assertFalse($this->theme()->apply('web'));
        $this->assertSame('web.modules.roster.index', $this->modulePath());
    }

    public function test_activate_roi_apply_thi_moi_thu_muc_di_theo_theme(): void
    {
        $this->theme()->activate('storefront', 'web');

        $this->assertSame([
            'slug' => 'storefront',
            'contract' => 1,
            'idMode' => 'terse',
            'revision' => 'theme-rev-1',
            'context' => 'web',
        ], Cache::get(ThemeService::CACHE_PREFIX . 'web'));
        $this->assertTrue($this->theme()->apply('web'));
        $this->assertSame('themes.storefront.modules.roster.index', $this->modulePath());
    }

    public function test_activate_theme_chua_compile_thi_bao_loi_ngay(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->theme()->activate('khong-ton-tai', 'web');
    }

    public function test_activate_tu_choi_theme_khong_tuong_thich_va_khong_doi_cache(): void
    {
        file_put_contents($this->viewRoot . '/packages/storefront/dist/theme.json', json_encode([
            'slug' => 'storefront',
            'contract' => 2,
            'idMode' => 'terse',
            'revision' => 'theme-rev-2',
            'context' => 'web',
        ], JSON_THROW_ON_ERROR));

        try {
            $this->theme()->activate('storefront', 'web');
            $this->fail('Theme khác contract phải bị từ chối.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('contract 2', $exception->getMessage());
        }

        $this->assertNull($this->theme()->active('web'));
    }

    public function test_check_compatible_tu_choi_metadata_bat_buoc_bi_thieu(): void
    {
        $this->assertFalse($this->theme()->checkCompatible('storefront', [])['ok']);
        $this->assertFalse($this->theme()->checkCompatible('storefront', [
            'contract' => '',
            'idMode' => 'terse',
        ])['ok']);

        file_put_contents($this->viewRoot . '/packages/storefront/dist/theme.json', json_encode([
            'slug' => 'storefront',
            'idMode' => 'terse',
            'revision' => 'theme-rev-1',
            'context' => 'web',
        ], JSON_THROW_ON_ERROR));
        $result = $this->theme()->checkCompatible('storefront', [
            'contract' => 1,
            'idMode' => 'terse',
            'context' => 'web',
        ]);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('thiếu contract', $result['reason']);
    }

    public function test_check_compatible_tu_choi_theme_thieu_revision(): void
    {
        file_put_contents($this->viewRoot . '/packages/storefront/dist/theme.json', json_encode([
            'slug' => 'storefront',
            'contract' => 1,
            'idMode' => 'terse',
        ], JSON_THROW_ON_ERROR));

        $result = $this->theme()->checkCompatible('storefront', [
            'contract' => 1,
            'idMode' => 'terse',
        ]);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('thiếu revision', $result['reason']);
    }

    public function test_activate_tu_choi_theme_cua_context_khac(): void
    {
        file_put_contents($this->viewRoot . '/packages/storefront/dist/theme.json', json_encode([
            'slug' => 'storefront',
            'contract' => 1,
            'idMode' => 'terse',
            'revision' => 'theme-rev-1',
            'context' => 'admin',
        ], JSON_THROW_ON_ERROR));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dành cho context [admin]');

        $this->theme()->activate('storefront', 'web');
    }

    public function test_resolver_chi_chay_mot_lan_roi_nam_trong_cache(): void
    {
        $calls = 0;
        $this->theme()->resolveUsing(function () use (&$calls) {
            $calls++;
            return 'storefront';
        }, 'web');

        $this->assertSame('storefront', $this->theme()->active('web'));
        $this->assertSame('storefront', $this->theme()->active('web'));
        $this->assertSame('storefront', $this->theme()->active('web'));

        $this->assertSame(1, $calls, 'mỗi request một truy vấn DB là thứ cache phải chặn');
    }

    public function test_khong_co_theme_cung_duoc_cache_de_khoi_hoi_db_moi_request(): void
    {
        $calls = 0;
        $this->theme()->resolveUsing(function () use (&$calls) {
            $calls++;
            return null;
        }, 'web');

        $this->assertNull($this->theme()->active('web'));
        $this->assertNull($this->theme()->active('web'));

        $this->assertSame(1, $calls);
    }

    public function test_service_khong_memo_slug_nen_worker_khac_thay_ngay(): void
    {
        $this->theme()->activate('storefront', 'web');
        $this->assertSame('storefront', $this->theme()->active('web'));

        // Worker khác tắt theme: chỉ có cache đổi, service này không được giữ
        // bản sao cũ trong thuộc tính.
        Cache::forever(ThemeService::CACHE_PREFIX . 'web', '');

        $this->assertNull($this->theme()->active('web'));
    }

    public function test_theme_bien_mat_sau_khi_active_thi_roi_ve_base_goc(): void
    {
        $this->theme()->activate('storefront', 'web');
        $this->theme()->apply('web');
        $this->assertSame('themes.storefront.modules.roster.index', $this->modulePath());

        rmdir($this->viewRoot . '/themes/storefront');

        $this->assertFalse($this->theme()->apply('web'));
        $this->assertSame('web.modules.roster.index', $this->modulePath());

        mkdir($this->viewRoot . '/themes/storefront', 0777, true);
    }

    public function test_theme_active_tro_nen_khong_tuong_thich_sau_deploy_thi_roi_ve_base(): void
    {
        $this->theme()->activate('storefront', 'web');
        $this->assertTrue($this->theme()->apply('web'));

        $this->bundleManifest->info = ['contract' => 2, 'idMode' => 'terse'];

        $this->assertFalse($this->theme()->apply('web'));
        $this->assertSame('web.modules.roster.index', $this->modulePath());
        $this->assertSame('storefront', $this->theme()->active('web'), 'giữ lựa chọn để admin có thể chẩn đoán và cài lại');
    }

    public function test_apply_nang_cap_cache_slug_cu_sang_selection_co_contract(): void
    {
        Cache::forever(ThemeService::CACHE_PREFIX . 'web', 'storefront');

        $this->assertTrue($this->theme()->apply('web'));
        $this->assertSame(1, Cache::get(ThemeService::CACHE_PREFIX . 'web')['contract']);
        $this->assertSame('theme-rev-1', Cache::get(ThemeService::CACHE_PREFIX . 'web')['revision']);
        $this->assertSame('web', Cache::get(ThemeService::CACHE_PREFIX . 'web')['context']);
    }

    public function test_phai_apply_lai_sau_moi_ranh_gioi_request_octane(): void
    {
        $this->theme()->activate('storefront', 'web');
        $this->theme()->apply('web');
        $this->assertSame('themes.storefront.modules.roster.index', $this->modulePath());

        // Octane giữa hai request: ViewContextManager là scoped nên override bay.
        $this->app->forgetScopedInstances();
        app(ViewContextManager::class)->registerContext('web', ['base' => 'web']);
        $this->assertSame('web.modules.roster.index', $this->modulePath());

        // Middleware chạy lại → theme trở lại. Đây là lý do apply() phải ở
        // middleware chứ không phải provider boot.
        $this->theme()->apply('web');
        $this->assertSame('themes.storefront.modules.roster.index', $this->modulePath());
    }

    public function test_deactivate_roi_apply_thi_tro_ve_base_goc(): void
    {
        $this->theme()->activate('storefront', 'web');
        $this->theme()->apply('web');
        $this->assertSame('themes.storefront.modules.roster.index', $this->modulePath());

        $this->theme()->deactivate('web');

        // Cùng một tiến trình, cùng một manager: apply() phải dọn override cũ,
        // không thì tinker/queue worker giữ theme vừa tắt.
        $this->assertFalse($this->theme()->apply('web'));
        $this->assertSame('web.modules.roster.index', $this->modulePath());
    }

    public function test_available_liet_ke_theme_da_compile(): void
    {
        $this->assertContains('storefront', $this->theme()->available());
    }
}
