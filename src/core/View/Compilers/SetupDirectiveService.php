<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class SetupDirectiveService
{
    public function registerDirectives(): void
    {
        // Directive @setup - alias của @register
        Blade::directive('setup', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        });

        Blade::directive('Setup', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        });

        // Directive @endsetup - alias của @endregister  
        Blade::directive('endsetup', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });

        Blade::directive('endSetup', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });

        Blade::directive('EndSetup', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });

        // Directive @script - với các variant
        Blade::directive('script', function ($expression) {
            return $this->processScriptDirective($expression);
        });

        Blade::directive('Script', function ($expression) {
            return $this->processScriptDirective($expression);
        });

        // Directive @endscript
        Blade::directive('endscript', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });

        Blade::directive('endScript', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });

        Blade::directive('EndScript', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });

    }

    /**
     * Process @script directive with different variants
     */
    public function processScriptDirective($expression)
    {
        $content = is_null($expression) ? '' : trim($expression);
        
        // Default behavior: act like @register
        if ($content === '') {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        }

        // Parse expression to determine script type
        $content = trim($content, '()');
        
        // @script(setup) or @script('setup')
        if ($content === 'setup' || $content === "'setup'" || $content === '"setup"') {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        }

        // @script(view) or @script('view')
        if ($content === 'view' || $content === "'view'" || $content === '"view"') {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        }

        // @script(component) or @script('component')
        if ($content === 'component' || $content === "'component'" || $content === '"component"') {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        }

        // Default case - act like @register
        return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
    }
}