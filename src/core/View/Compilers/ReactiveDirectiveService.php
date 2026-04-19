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
    protected $markerPrefix = 'o';
    protected $markerTagShortcut = [
        "view" => 'v',                           // View Marker
        "component" => 'c',                      // Component Marker
        "block" => 'b',                          // Block Marker
        "reactive" => 'r',                       // Reactive Marker
        "section" => 's',                        // Section Marker
        "layout" => 'l',                         // Layout Marker
        "for" => 'fo',                           // For loop Marker
        "forin" => 'fi',                         // For-in loop Marker
        "foreach" => 'fe',                       // For-each loop Marker
        "forelse" => 'fls',                      // Forelse loop Marker
        "each" => 'ea',                          // Each loop Marker
        "if" => 'if',                            // If condition Marker
        "switch" => 'sw',                        // Switch condition Marker
        "while" => 'wh',                         // While loop Marker
        "include" => 'inc',                      // Include Marker
        "echo" => 'e',                           // Echo Marker
        "echoescaped" => 'ee',                   // Echo escaped Marker
        "yield" => 'y',                          // Yield Marker
        "slot" => 'st',                          // Slot Marker
        "template" => 't',                       // Template Marker
        "style" => 'sty',                        // Style Marker
        "script" => 'sc',                        // Script Marker
        "useblock" => 'ub',                      // Use block Marker
        "extend" => 'ex',                        // Extend Marker
    ];

    public function getMarkerTagShortcut($name){
        return $this->markerTagShortcut[$name] ?? $name;
    }
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
