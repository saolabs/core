<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Saola;
use Saola\Core\Services\BundleManifest;
use Saola\Core\Services\ClientShare;
use Saola\Core\Services\PackageAssets;
use Tests\TestCase;

/**
 * Cầu nối package PHP → client: bundle JS (`extra.saola.client` + `saola:assets`
 * → `APP_CONFIGS.bundles`) và dữ liệu theo request (`Saola::share` →
 * `APP_CONFIGS.shared`).
 */
class PackageClientBridgeTest extends TestCase
{
    private string $root;

    protected function getPackageProviders($app): array
    {
        return [SaolaServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/saola-bridge-' . bin2hex(random_bytes(4));
        foreach (['vendor/composer', 'vendor/acme/perm/dist', 'vendor/acme/admin-only/dist', 'vendor/acme/plain', 'public'] as $dir) {
            mkdir($this->root . '/' . $dir, 0777, true);
        }
        file_put_contents($this->root . '/vendor/acme/perm/dist/client.js', 'export default {}');
        file_put_contents($this->root . '/vendor/acme/admin-only/dist/admin.js', 'export default {}');
        file_put_contents($this->root . '/vendor/composer/installed.json', json_encode(['packages' => [
            ['name' => 'acme/perm', 'install-path' => '../acme/perm', 'extra' => ['saola' => ['client' => 'dist/client.js']]],
            ['name' => 'acme/admin-only', 'install-path' => '../acme/admin-only', 'extra' => ['saola' => ['client' => ['admin' => 'dist/admin.js']]]],
            ['name' => 'acme/plain', 'install-path' => '../acme/plain'],
        ]]));
        $this->app->setBasePath($this->root);
        $this->app->usePublicPath($this->root . '/public');
        $this->app->forgetInstance(PackageAssets::class);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
        parent::tearDown();
    }

    public function test_chua_publish_thi_bao_loi_va_khong_phat_url_hong(): void
    {
        $reported = [];
        $this->app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, new class($reported) extends \Illuminate\Foundation\Exceptions\Handler {
            public function __construct(private array &$sink) {}
            public function report(\Throwable $e): void { $this->sink[] = $e->getMessage(); }
        });

        $this->assertSame([], app(PackageAssets::class)->urls('web'));
        $this->assertStringContainsString('saola:assets', $reported[0] ?? '');
    }

    public function test_publish_roi_phat_url_theo_context_truoc_theme(): void
    {
        $this->artisan('saola:assets')->assertSuccessful();

        $this->assertFileExists($this->root . '/public/static/saola/packages/acme/perm/client.js');
        $web = app(BundleManifest::class)->bundleUrls('web');
        $admin = app(BundleManifest::class)->bundleUrls('admin');

        $this->assertCount(1, $web);
        $this->assertMatchesRegularExpression('#/static/saola/packages/acme/perm/client\.js\?v=\d+$#', $web[0]);
        // admin: package khai theo context; thứ tự theo tên package.
        $this->assertCount(2, $admin);
        $this->assertStringContainsString('acme/admin-only/admin.js', $admin[0]);
        $this->assertStringContainsString('acme/perm/client.js', $admin[1]);
    }

    public function test_share_tinh_lai_moi_request_bo_null_va_nuot_loi(): void
    {
        $this->app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, new class extends \Illuminate\Foundation\Exceptions\Handler {
            public function __construct() {}
            public function report(\Throwable $e): void {}
        });
        Saola::share('who', fn (Request $r) => ['path' => $r->path()]);
        Saola::share('gone', fn () => null);
        Saola::share('broken', fn () => throw new \RuntimeException('x'));

        $share = app(ClientShare::class);
        $this->assertSame(['who' => ['path' => 'a']], $share->export(Request::create('/a')));
        $this->assertSame(['who' => ['path' => 'b']], $share->export(Request::create('/b')));

        $this->expectException(\InvalidArgumentException::class);
        Saola::share('bad key', fn () => 1);
    }
}
