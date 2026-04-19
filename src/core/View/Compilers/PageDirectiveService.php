<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class PageDirectiveService
{
    public function registerDirectives(): void
    {
        
        // Directive @pageStart
        Blade::directive('pageStart', function ($expression) {
            return $this->srartPageDirective($expression);
        });
        
        Blade::directive('PageStart', function ($expression) {
            return $this->srartPageDirective($expression);
        });
        
        Blade::directive('pageopen', function ($expression) {
            return $this->srartPageDirective($expression);
        });
        
        Blade::directive('pageOpen', function ($expression) {
            return $this->srartPageDirective($expression);
        });
        
        Blade::directive('PageOpen', function ($expression) {
            return $this->srartPageDirective($expression);
        });
        
        // Directive @pageEnd
        Blade::directive('pageEnd', function ($expression) {
            return $this->endPageDirective($expression);
        });
        
        Blade::directive('PageEnd', function ($expression) {
            return $this->endPageDirective($expression);
        });
        
        Blade::directive('pageclose', function ($expression) {
            return $this->endPageDirective($expression);
        });
        
        Blade::directive('pageClose', function ($expression) {
            return $this->endPageDirective($expression);
        });
        
        Blade::directive('PageClose', function ($expression) {
            return $this->endPageDirective($expression);
        });
        
        // Directive @docStart
        Blade::directive('docStart', function ($expression) {
            return $this->srartPageDirective($expression);
        });
        
        Blade::directive('DocStart', function ($expression) {
            return $this->srartPageDirective($expression);
        });
        
        // Directive @docEnd
        Blade::directive('docEnd', function ($expression) {
            return $this->endPageDirective($expression);
        });
        
        Blade::directive('DocEnd', function ($expression) {
            return $this->endPageDirective($expression);
        });
    }

    public function srartPageDirective($expression) {
        return "<?php echo \$__env->make(\$__system__.'page.begin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><!-- [sao:page ...] -->";
    }

    public function endPageDirective($expression) {
        return "<!-- [/sao:page] --><?php echo \$__env->make(\$__system__.'page.end', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>";
    }
}

