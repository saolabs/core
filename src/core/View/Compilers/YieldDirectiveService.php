<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class YieldDirectiveService
{
    protected $commonService;

    public function __construct(CommonDirectiveService $commonService)
    {
        $this->commonService = $commonService;
    }
    public function registerDirectives(): void {
        // Directive @yieldAttr/@yieldattr - yield attributes với 2 tham số bắt buộc và 1 tùy chọn
        Blade::directive('yieldAttr', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });

        Blade::directive('yieldattr', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
 
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('onyield', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('onYield', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('YieldOn', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('yieldOn', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('yieldon', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('yieldListen', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('yieldlisten', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('yieldWatch', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
        // Directive @onyield - onYield attributes với 2 dạng: đơn giản và array
        Blade::directive('yieldwatch', function ($expression) {
            return $this->processOnYieldDirective($expression);
        });
    }
    /**
     * Process @onyield directive - tạo static attributes để theo dõi state changes
     * Khác với @subscribe: @onyield tạo static attributes, @subscribe tạo dynamic PHP code
     */
    public function processOnYieldDirective($expression)
    {
        return "<?php echo \$__helper->registerOnYield(\$__env, $expression); ?>";
    }
}
