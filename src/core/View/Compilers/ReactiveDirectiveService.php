<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

/**
 * ReactiveDirectiveService
 * 
 * Đăng ký các Blade directives @startReactive và @endReactive
 * để đánh dấu các vùng reactive trong template
 */
class ReactiveDirectiveService
{
    // LƯU Ý: marker prefix + shortcut map đã được tập trung tại
    // ViewStorageManager (nguồn sự thật duy nhất, khớp client MarkerRegistry).
    // Trước đây class này có bản sao riêng (prefix 'o', map lệch) nhưng KHÔNG
    // được dùng để emit — đã gỡ để tránh nhầm cấu trúc marker.

    /**
     * Đăng ký các reactive directives
     */
    public function registerDirectives(): void
    {
        // Directive @startReactive - mở vùng reactive
        Blade::directive('startReactive', function (string $expression) {
            return "<?php echo \$__helper->startReactive({$expression}); ?>";
        });

        // Directive @endReactive - đóng vùng reactive
        Blade::directive('endReactive', function (string $expression) {
            return "<?php echo \$__helper->endReactive({$expression}); ?>";
        });
    }
}
