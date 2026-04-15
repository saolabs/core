<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class WrapperDirectiveService
{
    public function registerDirectives(): void {
        // Directive @wrap - supports all three cases
        Blade::directive('wrap', function ($expression) {
            return $this->startWrapDirective($expression);
        });
        Blade::directive('Wrap', function ($expression) {
            return $this->startWrapDirective($expression);
        });
        Blade::directive('endwrap', function ($expression) {
            return $this->endWrapDirective($expression);
        });
        Blade::directive('EndWrap', function ($expression) {
            return $this->endWrapDirective($expression);
        });
        Blade::directive('endWrap', function ($expression) {
            return $this->endWrapDirective($expression);
        });

        // Directive @wrapper - supports all three cases
        Blade::directive('wrapper', function ($expression) {
            return $this->startWrapDirective($expression);
        });
        Blade::directive('Wrapper', function ($expression) {
            return $this->startWrapDirective($expression);
        });
        Blade::directive('endwrapper', function ($expression) {
            return $this->endWrapDirective($expression);
        });
        Blade::directive('EndWrapper', function ($expression) {
            return $this->endWrapDirective($expression);
        });
        Blade::directive('endwrapper', function ($expression) {
            return $this->endWrapDirective($expression);
        });
    }

    /**
     * Process @wrap directive - supports three syntaxes:
     * 1. @wrap -> HTML comment
     * 2. @wrap($tag, $attributes) -> HTML tag with custom tag
     * 3. @wrap($attributes = []) -> HTML div with attributes only
     */
    public function startWrapDirective($expression)
    {
        // Remove outer parentheses if present
        $expression = trim($expression, '()');
        
        if (empty($expression)) {
            // Case 1: @wrap -> HTML comment
            return '<?php echo $__helper->startMarker("view", $__VIEW_ID__); ?>';
        }
        
        // Check if expression starts with array syntax (case 3)
        if (preg_match('/^\s*\[.*\]\s*$/', $expression)) {
            // Case 3: @wrap($attributes = []) -> HTML div with attributes only 
            return '<?php echo $__helper->startMarker("view", $__VIEW_ID__, '.$expression.'); ?>';
        }
        
        // Case 2: @wrap($tag, $attributes) -> HTML tag with custom tag
        $attributes = $this->parseWrapAttrExpression($expression);
        return '<?php echo $__helper->startMarker("view", $__VIEW_ID__, '.$attributes.'); ?>';
    }

    /**
     * Process @endWrap directive - always no parameters
     * Uses $__wrapper_tag__ variable to determine closing tag
     */
    public function endWrapDirective($expression)
    {
        // @endWrap should always be without parameters
        // Check if $__wrapper_tag__ is set to determine closing method
        return '<?php echo $__helper->endMarker("view", $__VIEW_ID__); ?>';
    }

    /**
     * Parse @wrap expression to extract tag and attributes
     */
    private function parseWrapAttrExpression($expression)
    {
        // Find first comma outside quotes
        $inQuote = false;
        $quoteChar = null;
        $commaPos = false;
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
            }
            
            if (!$inQuote && $char === ',') {
                $commaPos = $i;
                break;
            }
        }
        
        if ($commaPos === false) {
            // Only tag, no attributes
            $tag = trim($expression, '\'" ');
            return '[]';
        }
        
        // Both tag and attributes
        $tagPart = substr($expression, 0, $commaPos);
        $attributesPart = substr($expression, $commaPos + 1);
        return $attributesPart??'[]';
    }
    
}
