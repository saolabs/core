<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Saola\Core\View\Compilers\SimplePhpStructureParserService;
use Saola\Core\View\Compilers\VarsDirectiveService;

/**
 * `@vars(x = mặc_định)` chỉ được lấy mặc định khi biến CHƯA ĐƯỢC TRUYỀN.
 *
 * Phía JS, compiler sinh `let {x = mặc_định} = __data__`, mà destructuring chỉ
 * áp dụng mặc định khi giá trị là `undefined`. Blade phải khớp ngữ nghĩa đó.
 *
 * Bản cũ sinh `if (!isset($x) || empty($x))`, nên MỌI giá trị falsy từ
 * controller — `false`, `0`, `''`, `[]` — bị thay bằng mặc định của template,
 * và CHỈ ở phía SSR. Đo được trên /demo/market ngày 05/09/2026: controller gửi
 * `rising = false`, SSR tô xanh "tăng" trong khi con số hiển thị là -1.96%,
 * còn CSR tô đỏ đúng. Đúng lớp lệch SSR/CSR mà cả dự án đang chống.
 *
 * Cổng parity SSR↔CSR bắt được, nhưng chỉ khi trang dính lỗi nằm trong danh
 * sách route được gác — nên bất biến này phải có test riêng.
 */
class VarsDefaultSemanticsTest extends TestCase
{
    private function compile(string $expression): string
    {
        return (new VarsDirectiveService(new SimplePhpStructureParserService()))
            ->processVarsDirective($expression);
    }

    /**
     * Chỉ lấy khối `<?php … ?>` ĐẦU TIÊN — đó là câu lệnh gán mặc định. Khối sau
     * gọi `$__helper->addViewData(...)`, cần cả runtime view nên không eval được.
     */
    private function guard(string $expression): string
    {
        preg_match('/<\\?php(.*?)\\?>/s', $this->compile($expression), $m);

        return trim($m[1] ?? '');
    }

    /** Chạy câu lệnh gán mặc định với một tập biến có sẵn, trả về giá trị sau cùng. */
    private function evaluate(string $expression, array $provided): mixed
    {
        $code = $this->guard($expression);
        extract($provided);
        eval($code);

        return $x ?? null;
    }

    public function test_gia_tri_falsy_tu_controller_khong_bi_mac_dinh_nuot(): void
    {
        self::assertFalse($this->evaluate('$x = true', ['x' => false]), 'false bị thay bằng true');
        self::assertSame(0, $this->evaluate('$x = 10', ['x' => 0]), '0 bị thay bằng 10');
        self::assertSame('', $this->evaluate("\$x = 'Khách'", ['x' => '']), 'chuỗi rỗng bị thay');
        self::assertSame([], $this->evaluate('$x = [1, 2]', ['x' => []]), 'mảng rỗng bị thay');
    }

    public function test_van_lay_mac_dinh_khi_chua_truyen(): void
    {
        self::assertTrue($this->evaluate('$x = true', []));
        self::assertSame(10, $this->evaluate('$x = 10', []));
        self::assertSame('Khách', $this->evaluate("\$x = 'Khách'", []));
    }

    public function test_null_van_tinh_la_chua_truyen(): void
    {
        // `isset` coi null là chưa có. Giữ nguyên quy ước PHP: controller trả null
        // cho một biến tuỳ chọn thì template vẫn có mặc định để hiển thị.
        self::assertSame(10, $this->evaluate('$x = 10', ['x' => null]));
    }

    public function test_gia_tri_that_luon_thang_mac_dinh(): void
    {
        self::assertSame(42, $this->evaluate('$x = 10', ['x' => 42]));
        self::assertSame('Sao', $this->evaluate("\$x = 'Khách'", ['x' => 'Sao']));
    }

    public function test_khong_con_empty_trong_code_sinh_ra(): void
    {
        // Chốt trực tiếp vào code sinh ra: nếu ai đó thêm `empty()` lại thì đỏ
        // ngay, kể cả khi các test hành vi ở trên vô tình vẫn xanh.
        self::assertSame('if (!isset($x)) $x = true;', $this->guard('$x = true'));
        self::assertStringNotContainsString('empty(', $this->compile('$x = true'));
    }
}
