<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class ComponentDirectiveService
{
    public function registerDirectives(): void
    {
        Blade::directive('children', function ($expression) {
            return "<?php echo \$__ONE_CHILDREN_CONTENT__ ?? ''; ?>";
        });
    }
}
