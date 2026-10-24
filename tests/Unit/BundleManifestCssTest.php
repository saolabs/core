<?php

namespace Tests\Unit;

use Saola\Core\Services\BundleManifest;
use Tests\TestCase;

class BundleManifestCssTest extends TestCase
{
    public function test_admin_stylesheet_and_legacy_web_stylesheet_are_resolved_per_context(): void
    {
        config(['sao.assets.css.admin' => 'resources/saola/admin/assets/css/admin.css']);
        $manifest = $this->fixture();
        $manifest->entries['admin'] = [
            'resources/saola/admin/assets/css/admin.css' => ['file' => 'assets/admin-123.css'],
            'resources/css/app.css' => ['file' => 'assets/web-unwanted.css'],
        ];
        $manifest->entries['web'] = ['resources/css/app.css' => ['file' => 'assets/web-456.css']];
        $this->assertSame(asset('static/saola/admin/assets/admin-123.css'), $manifest->cssUrl('admin'));
        $this->assertSame(asset('static/saola/web/assets/web-456.css'), $manifest->cssUrl('web'));
        $manifest->dev = true;
        $this->assertSame('http://localhost:5173/resources/saola/admin/assets/css/admin.css', $manifest->cssUrl('admin'));
        $this->assertSame('http://localhost:5173/resources/css/app.css', $manifest->cssUrl('web'));
    }

    public function test_css_imported_by_context_bundle_works_without_a_standalone_css_entry(): void
    {
        config(['sao.assets.css.admin' => 'resources/saola/admin/assets/css/admin.css']);
        $manifest = $this->fixture();
        $manifest->entries['admin'] = ['resources/js/saola/app.admin.js' => ['file' => 'js/admin.js', 'css' => ['assets/admin-789.css']]];
        $this->assertSame(asset('static/saola/admin/assets/admin-789.css'), $manifest->cssUrl('admin'));
        $this->assertNull($manifest->cssUrl('web'));
    }

    private function fixture(): BundleManifest
    {
        return new class extends BundleManifest {
            public bool $dev = false;
            public array $entries = [];
            public function isDev(): bool { return $this->dev; }
            public function devOrigin(): ?string { return 'http://localhost:5173'; }
            public function manifest(string $context): array { return $this->entries[$context] ?? []; }
        };
    }
}
