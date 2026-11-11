<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Saola\Core\Console\Commands\InstallThemeCommand;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Services\BundleManifest;
use Saola\Core\Services\ThemeService;
use Tests\TestCase;

/** `saola:theme:install` đặt Blade và asset theo `{context}.{slug}`. */
class InstallThemeCommandTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        (new SaolaServiceProvider($this->app))->register();
        app(ViewContextManager::class)->registerContext('web', ['base' => 'web']);

        $this->root = sys_get_temp_dir() . '/sao-install-' . bin2hex(random_bytes(4));
        $this->package(['namespace' => 'web.aurora']);

        config()->set('sao.themes.path', $this->root . '/packages');
        config()->set('sao.themes.public_path', $this->root . '/public/static/saola/themes');

        $views = $this->root . '/views';
        $this->app->instance(ThemeService::class, new class($views) extends ThemeService {
            public function __construct(private string $views) {}

            public function installedViewsPath(string $slug, string $context = 'web'): string
            {
                return $this->views . '/' . $context . '/' . $slug;
            }
        });
        $this->app->instance(BundleManifest::class, new class extends BundleManifest {
            public function buildInfo(): array
            {
                return ['contract' => 1, 'idMode' => 'terse'];
            }
        });
        Cache::flush();
        Artisan::registerCommand($this->app->make(InstallThemeCommand::class));
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
        parent::tearDown();
    }

    private function package(array $extra): void
    {
        $dist = $this->root . '/packages/aurora/dist';
        @mkdir($dist . '/views/layouts', 0777, true);
        @mkdir($dist . '/public', 0777, true);
        file_put_contents($dist . '/views/layouts/app.blade.php', 'x');
        file_put_contents($dist . '/public/main.js', '//');
        file_put_contents($dist . '/theme.json', json_encode($extra + [
            'slug' => 'aurora', 'context' => 'web', 'contract' => 1,
            'idMode' => 'terse', 'revision' => 'r1',
        ]));
    }

    public function test_cai_blade_vao_context_slug_va_link_asset_ngoai_outdir_cua_vite(): void
    {
        $this->assertSame(0, Artisan::call('saola:theme:install', ['slug' => 'aurora']));

        $this->assertFileExists($this->root . '/views/web/aurora/layouts/app.blade.php');
        $this->assertFileExists($this->root . '/views/web/aurora/' . InstallThemeCommand::MARKER);
        $link = $this->root . '/public/static/saola/themes/web/aurora';
        $this->assertTrue(is_link($link));
        $this->assertFileExists($link . '/main.js');
    }

    public function test_goi_build_theo_namespace_cu_bi_tu_choi_va_khong_dong_vao_file(): void
    {
        $this->package(['namespace' => 'themes.aurora']);

        $this->assertSame(1, Artisan::call('saola:theme:install', ['slug' => 'aurora']));
        $this->assertStringContainsString('build lại', Artisan::output());
        $this->assertDirectoryDoesNotExist($this->root . '/views/web/aurora');
    }

    public function test_thu_muc_trung_slug_nhung_khong_phai_theme_khong_bi_de_ke_ca_force(): void
    {
        mkdir($this->root . '/views/web/aurora', 0777, true);
        file_put_contents($this->root . '/views/web/aurora/keep.blade.php', 'keep');

        $this->assertSame(1, Artisan::call('saola:theme:install', ['slug' => 'aurora', '--force' => true]));
        $this->assertFileExists($this->root . '/views/web/aurora/keep.blade.php');
    }

    public function test_cai_lai_voi_force_ghi_de_theme_da_cai(): void
    {
        Artisan::call('saola:theme:install', ['slug' => 'aurora']);
        $this->assertSame(1, Artisan::call('saola:theme:install', ['slug' => 'aurora']));
        $this->assertSame(0, Artisan::call('saola:theme:install', ['slug' => 'aurora', '--force' => true]));
    }

    public function test_public_base_theo_context(): void
    {
        $themes = app(ThemeService::class);

        $this->assertSame('static/saola/themes/web/aurora', $themes->publicBase('aurora', 'web'));
        $this->assertSame('static/saola/themes/admin/aurora', $themes->publicBase('aurora', 'admin'));
    }
}
