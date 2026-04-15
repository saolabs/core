<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class TemplateDirectiveService
{
    private $wrapperService;

    public function __construct()
    {
        $this->wrapperService = new WrapperDirectiveService();
    }

    public function registerDirectives(): void
    {
        // Directive @template - alias of @wrap with enhanced parameter syntax
        Blade::directive('template', function ($expression) {
            return $this->startTemplateDirective($expression);
        });
        Blade::directive('Template', function ($expression) {
            return $this->startTemplateDirective($expression);
        });

        // Directive @endtemplate - closes template
        Blade::directive('endtemplate', function ($expression) {
            return $this->endTemplateDirective($expression);
        });
        Blade::directive('Endtemplate', function ($expression) {
            return $this->endTemplateDirective($expression);
        });
        Blade::directive('EndTemplate', function ($expression) {
            return $this->endTemplateDirective($expression);
        });
        Blade::directive('endTemplate', function ($expression) {
            return $this->endTemplateDirective($expression);
        });
    }

    /**
     * Process @template directive - supports multiple parameter formats:
     * 1. @template -> same as @wrap
     * 2. @template($tag = '...', $subscribe = [...], ..., $attributes = [...])
     * 3. @template(tag: '...', subscribe: [...], keyN: ..., attributes: [...])
     * 4. @template(['tag' => '...', 'subscribe' => [...], ...])
     */
    public function startTemplateDirective($expression)
    {
        // Remove outer parentheses if present
        $expression = trim($expression, '()');
        
        if (empty($expression)) {
            // Case 1: @template -> same as @wrap (HTML comment)
            return $this->wrapperService->startWrapDirective('');
        }
        
        // Parse parameters and convert to array format for WrapperDirectiveService
        $attributes = $this->parseTemplateParameters($expression);
        
        // Convert to array syntax that WrapperDirectiveService expects
        $arrayExpression = $this->convertToArrayExpression($attributes);
        
        // Delegate to WrapperDirectiveService
        return $this->wrapperService->startWrapDirective($arrayExpression);
    }

    /**
     * Process @endtemplate directive - delegates to WrapperDirectiveService
     */
    public function endTemplateDirective($expression)
    {
        return $this->wrapperService->endWrapDirective($expression);
    }

    /**
     * Parse template parameters from various formats:
     * - Positional: $tag = '...', $subscribe = [...], $attr1 = '...', ...
     * - Named: tag: '...', subscribe: [...], attr1: '...', ...
     * - Array: ['tag' => '...', 'subscribe' => [...], ...]
     */
    private function parseTemplateParameters($expression)
    {
        $expression = trim($expression);
        
        // Check if it's already array syntax
        if (preg_match('/^\s*\[/', $expression)) {
            // Already in array format, return as is (will be handled by WrapperDirectiveService)
            return $this->parseArraySyntax($expression);
        }
        
        // Check if it's named parameter syntax (contains colons)
        if ($this->isNamedParameterSyntax($expression)) {
            return $this->parseNamedParameters($expression);
        }
        
        // Parse as positional parameters with defaults
        return $this->parsePositionalParameters($expression);
    }

    /**
     * Check if expression uses named parameter syntax (key: value)
     */
    private function isNamedParameterSyntax($expression)
    {
        // Look for pattern: word: (outside of quotes and arrays)
        $inQuote = false;
        $quoteChar = null;
        $bracketDepth = 0;
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            // Handle quotes
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
            }
            
            // Handle brackets
            if (!$inQuote) {
                if ($char === '[') $bracketDepth++;
                elseif ($char === ']') $bracketDepth--;
            }
            
            // Check for colon (not inside quotes or brackets, not part of ::)
            if (!$inQuote && $bracketDepth === 0 && $char === ':' && 
                ($i + 1 >= strlen($expression) || $expression[$i + 1] !== ':') &&
                ($i === 0 || $expression[$i - 1] !== ':')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Parse array syntax: ['key' => 'value', ...]
     */
    private function parseArraySyntax($expression)
    {
        // Remove outer brackets
        $expression = trim($expression, '[]');
        
        if (empty($expression)) {
            return [];
        }
        
        return $this->parseKeyValuePairs($expression, '=>');
    }

    /**
     * Parse named parameters: key: value, key2: value2, ...
     */
    private function parseNamedParameters($expression)
    {
        return $this->parseKeyValuePairs($expression, ':');
    }

    /**
     * Parse positional parameters: $tag = '...', $subscribe = [...], ...
     */
    private function parsePositionalParameters($expression)
    {
        $attributes = [];
        $parts = $this->splitByComma($expression);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Check if it's an assignment: $varName = value
            if (preg_match('/^\s*\$(\w+)\s*=\s*(.+)$/s', $part, $matches)) {
                $key = $matches[1];
                $value = trim($matches[2]);
                $attributes[$key] = $value;
            } else {
                // If no assignment, treat as 'tag' parameter
                if (!isset($attributes['tag'])) {
                    $attributes['tag'] = trim($part, '\'" ');
                }
            }
        }
        
        return $attributes;
    }

    /**
     * Parse key-value pairs with given separator (=> or :)
     */
    private function parseKeyValuePairs($expression, $separator)
    {
        $attributes = [];
        $parts = $this->splitByComma($expression);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Find separator position (not inside quotes or brackets)
            $separatorPos = $this->findSeparatorPosition($part, $separator);
            
            if ($separatorPos !== false) {
                $key = trim(substr($part, 0, $separatorPos), '\'" ');
                $value = trim(substr($part, $separatorPos + strlen($separator)));
                
                // Clean up key (remove $ if present)
                $key = ltrim($key, '$');
                
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }

    /**
     * Find separator position outside quotes and brackets
     */
    private function findSeparatorPosition($expression, $separator)
    {
        $inQuote = false;
        $quoteChar = null;
        $bracketDepth = 0;
        $separatorLen = strlen($separator);
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            // Handle quotes
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
            }
            
            // Handle brackets
            if (!$inQuote) {
                if ($char === '[') $bracketDepth++;
                elseif ($char === ']') $bracketDepth--;
            }
            
            // Check for separator
            if (!$inQuote && $bracketDepth === 0) {
                if (substr($expression, $i, $separatorLen) === $separator) {
                    // For ':', make sure it's not '::'
                    if ($separator === ':') {
                        $notDoubleColon = ($i + 1 >= strlen($expression) || $expression[$i + 1] !== ':') &&
                                         ($i === 0 || $expression[$i - 1] !== ':');
                        if ($notDoubleColon) {
                            return $i;
                        }
                    } else {
                        return $i;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Split expression by comma (respecting quotes, brackets, and parentheses)
     */
    private function splitByComma($expression)
    {
        $parts = [];
        $current = '';
        $inQuote = false;
        $quoteChar = null;
        $bracketDepth = 0;
        $parenDepth = 0;
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            // Handle quotes
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
            }
            
            // Handle brackets and parentheses
            if (!$inQuote) {
                if ($char === '[') $bracketDepth++;
                elseif ($char === ']') $bracketDepth--;
                elseif ($char === '(') $parenDepth++;
                elseif ($char === ')') $parenDepth--;
            }
            
            // Split on comma only if not inside quotes, brackets, or parentheses
            if (!$inQuote && $bracketDepth === 0 && $parenDepth === 0 && $char === ',') {
                if (!empty(trim($current))) {
                    $parts[] = $current;
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        // Add the last part
        if (!empty(trim($current))) {
            $parts[] = $current;
        }
        
        return $parts;
    }

    /**
     * Convert parsed attributes to array expression string for WrapperDirectiveService
     */
    private function convertToArrayExpression($attributes)
    {
        if (empty($attributes)) {
            return '';
        }
        
        $pairs = [];
        foreach ($attributes as $key => $value) {
            // Don't add quotes around value if it's already quoted or is an array
            $value = trim($value);
            $pairs[] = "'{$key}' => {$value}";
        }
        
        return '[' . implode(', ', $pairs) . ']';
    }
}
