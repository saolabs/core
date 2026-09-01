<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Saola\Core\View\Compilers\StyleDirectiveService;

/**
 * @style của Saola phải nhận CẢ HAI hợp đồng.
 *
 * Không có service này thì @style rơi về bản dựng sẵn của Laravel, mà hợp đồng
 * của Laravel NGƯỢC LẠI cú pháp Saola:
 *
 *   Laravel: @style(['color: red' => $cond])   khoá = chuỗi CSS, giá trị = điều kiện
 *   Saola:   @style({ color: 'red' })          khoá = thuộc tính, giá trị = giá trị
 *
 * Compiler dịch cú pháp Saola thành ['color' => 'red'], Laravel đọc thành "áp
 * chuỗi CSS `color` nếu 'red' truthy" → render `style="color;"`, mất sạch giá
 * trị. Đã xảy ra thật trên thanh progress của trang demo.
 */
class StyleDirectiveServiceTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_render(array|string $input, string $expected): void
    {
        $this->assertSame($expected, StyleDirectiveService::render($input));
    }

    public static function cases(): array
    {
        return [
            // ── dạng Saola: khoá là thuộc tính ──
            'một thuộc tính' => [['width' => '50%'], ' style="width: 50%;"'],
            'nhiều thuộc tính' => [['color' => 'blue', 'margin' => '0'], ' style="color: blue; margin: 0;"'],
            'giá trị số 0 VẪN áp' => [['margin' => 0], ' style="margin: 0;"'],
            'null bị bỏ' => [['color' => null, 'width' => '1px'], ' style="width: 1px;"'],
            'false bị bỏ' => [['color' => false, 'width' => '1px'], ' style="width: 1px;"'],
            'chuỗi rỗng bị bỏ' => [['color' => '', 'width' => '1px'], ' style="width: 1px;"'],

            // ── dạng Laravel: khoá là chuỗi CSS, giá trị là điều kiện ──
            'điều kiện đúng' => [['display: none' => true], ' style="display: none;"'],
            'điều kiện sai' => [['display: none' => false], ''],
            'trộn hai dạng' => [
                ['width' => '10px', 'display: none' => true],
                ' style="width: 10px; display: none;"',
            ],

            // ── khoá số: phần tử chính là chuỗi CSS ──
            'khoá số' => [['width: 50%'], ' style="width: 50%;"'],
            'khoá số có dấu ;' => [['width: 50%;'], ' style="width: 50%;"'],

            // ── ca biên ──
            'mảng rỗng không phát gì' => [[], ''],
            'chuỗi trần' => ['color: red', ' style="color: red;"'],
        ];
    }

    public function test_escape_gia_tri(): void
    {
        // Giá trị đi thẳng vào thuộc tính HTML nên phải escape
        $out = StyleDirectiveService::render(['content' => '"><script>x</script>']);

        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    public function test_input_khong_phai_mang_thi_khong_no(): void
    {
        $this->assertSame('', StyleDirectiveService::render(null));
        $this->assertSame('', StyleDirectiveService::render(123));
    }
}
