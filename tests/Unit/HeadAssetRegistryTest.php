<?php

namespace Tests\Unit;

use Saola\Core\View\Services\ViewHelperService;
use Saola\Core\View\Services\ViewStorageManager;
use Tests\TestCase;

/**
 * Store của `@addCssLink` / `@addScriptSrc`.
 *
 * Hai tính chất phải giữ:
 *   - trùng (id, hoặc url nếu không có id) chỉ ra MỘT thẻ — layout, page và
 *     component dùng chung một file css là chuyện thường;
 *   - `renderHeadAssets` chỉ trả thứ CHƯA in, vì nó được gọi hai lần: ở <head>
 *     (_system.page.begin) và ở cuối <body> (_system.partials.scripts) cho phần
 *     đăng ký muộn hơn. Không có mốc đó thì mỗi asset ra hai thẻ.
 */
class HeadAssetRegistryTest extends TestCase
{
    private function helper(): ViewHelperService
    {
        return new ViewHelperService(new ViewStorageManager());
    }

    public function test_css_va_script_ra_dung_the_va_dung_bucket(): void
    {
        $helper = $this->helper();
        $helper->addCssLink('/static/app.css');
        $helper->addScriptSrc('https://cdn/prism.js', ['data-manual' => true]);

        $css = $helper->renderHeadAssets('css');
        $this->assertStringContainsString('<link rel="stylesheet" href="/static/app.css">', $css);
        $this->assertStringNotContainsString('<script', $css);

        $rest = $helper->renderHeadAssets();
        $this->assertStringContainsString('<script src="https://cdn/prism.js" data-manual></script>', $rest);
    }

    public function test_trung_url_chi_ra_mot_the(): void
    {
        $helper = $this->helper();
        $helper->addCssLink('/a.css');
        $helper->addCssLink('/a.css');

        $this->assertSame(1, substr_count($helper->renderHeadAssets(), '<link'));
    }

    public function test_trung_id_chi_ra_mot_the_du_url_khac(): void
    {
        // Cùng `id` = cùng một asset ở hai đường dẫn (đổi CDN, đổi phiên bản).
        $helper = $this->helper();
        $helper->addCssLink('/v1/theme.css', ['id' => 'theme']);
        $helper->addCssLink('/v2/theme.css', ['id' => 'theme']);

        $html = $helper->renderHeadAssets();
        $this->assertSame(1, substr_count($html, '<link'));
        $this->assertStringContainsString('/v1/theme.css', $html);
    }

    public function test_lan_in_thu_hai_khong_lap_lai_thu_da_in(): void
    {
        $helper = $this->helper();
        $helper->addCssLink('/a.css');
        $this->assertStringContainsString('<link', $helper->renderHeadAssets('css'));

        // Cuối <body>: chỉ phần đăng ký SAU khi <head> đã render.
        $this->assertSame('', $helper->renderHeadAssets());

        $helper->addCssLink('/late.css');
        $late = $helper->renderHeadAssets();
        $this->assertStringContainsString('/late.css', $late);
        $this->assertStringNotContainsString('/a.css', $late);
    }

    public function test_attribute_duoc_escape_va_ten_la_bi_bo(): void
    {
        $helper = $this->helper();
        $helper->addCssLink('/a.css?x=1&y=2', [
            'media' => 'print"><script>alert(1)</script>',
            'onload' => 'ok',
            '<bad>' => 'x',
            'href' => '/khong-duoc-ghi-de.css',
        ]);

        $html = $helper->renderHeadAssets();
        $this->assertStringContainsString('href="/a.css?x=1&amp;y=2"', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringNotContainsString('<bad>', $html);
        $this->assertStringNotContainsString('khong-duoc-ghi-de', $html);
        // Attribute hợp lệ vẫn phải đi qua.
        $this->assertStringContainsString('onload="ok"', $html);
    }
}
