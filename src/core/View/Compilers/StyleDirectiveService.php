<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

/**
 * Directive @style của Saola.
 *
 * Không có service này thì @style rơi về bản DỰNG SẴN của Laravel, mà hợp đồng
 * của Laravel là NGƯỢC LẠI với cú pháp Saola:
 *
 *   Laravel:  @style(['color: red' => $cond])     khoá = chuỗi CSS, giá trị = điều kiện
 *   Saola:    @style({ color: 'red' })            khoá = thuộc tính, giá trị = giá trị
 *
 * Compiler dịch cú pháp Saola thành `['color' => 'red']`, và Laravel đọc đó là
 * "áp chuỗi CSS `color` nếu 'red' truthy" — render ra `style="color;"`, mất
 * sạch giá trị. Lỗi này có thật trên thanh progress của trang demo:
 * `@style({width: `${count * 10}%`})` ra `style="width;"` thay vì
 * `style="width: 50%;"`.
 *
 * Service này nhận CẢ HAI dạng nên không phá code cũ:
 *   - khoá là số            → phần tử là chuỗi CSS nguyên vẹn
 *   - khoá CÓ chứa ':'      → dạng Laravel, giá trị là điều kiện
 *   - khoá KHÔNG chứa ':'   → dạng Saola, giá trị là giá trị CSS
 */
class StyleDirectiveService
{
    public function registerDirectives(): void
    {
        Blade::directive('style', function ($expression) {
            return "<?php echo \\Saola\\Core\\View\\Compilers\\StyleDirectiveService::render({$expression}); ?>";
        });
    }

    /**
     * Dựng thuộc tính style="..." từ mảng.
     *
     * @param  mixed $styles
     */
    public static function render($styles): string
    {
        if (is_string($styles)) {
            $styles = [$styles];
        }

        if (!is_array($styles)) {
            return '';
        }

        $parts = [];

        foreach ($styles as $key => $value) {
            // Khoá số: phần tử chính là chuỗi CSS
            if (is_int($key)) {
                $css = trim((string) $value);
                if ($css !== '') {
                    $parts[] = rtrim($css, '; ');
                }
                continue;
            }

            $key = trim((string) $key);

            // Dạng Laravel: khoá đã là chuỗi CSS đầy đủ, giá trị là điều kiện
            if (str_contains($key, ':')) {
                if ($value) {
                    $parts[] = rtrim($key, '; ');
                }
                continue;
            }

            // Dạng Saola: khoá là thuộc tính, giá trị là giá trị CSS.
            // false/null/'' nghĩa là không áp; 0 thì CÓ áp (margin: 0 hợp lệ).
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            $parts[] = $key . ': ' . trim((string) $value);
        }

        return $parts === [] ? '' : ' style="' . e(implode('; ', $parts) . ';') . '"';
    }
}
