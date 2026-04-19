<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class HydrationDirectiveService
{
    protected $keys = [];

    /**
     * Register the hydration directives
     * @hydrate and @hydration are aliases of each other
     */
    public function registerDirectives(): void
    {
        $config = config('saola.hydration.keys', config('sao.hydration.keys'));
        $this->keys = is_array($config) ? $config : [];

        // Register @hydrate directive (primary)
        Blade::directive('hydrate', function ($expression) {
            return $this->compileHydrationDirective($expression);
        });

        // Register @hydration directive as alias of @hydrate
        Blade::directive('hydration', function ($expression) {
            return $this->compileHydrationDirective($expression);
        });
    }

    /**
     * Compile hydration directive expression
     *
     * Only accepts a single expression (the hydrate ID).
     * Attribute key is always $this->keys['hydrate'].
     *
     * @hydrate($value)              -> data-hydrate="<?php echo $__VIEW_ID__; ?>-<?php echo $value ;?>"
     * @hydrate('test')              -> data-hydrate="<?php echo $__VIEW_ID__; ?>-<?php echo 'test' ;?>"
     * @hydrate($abc . 'def')        -> data-hydrate="<?php echo $__VIEW_ID__; ?>-<?php echo $abc . 'def' ;?>"
     * @hydrate($value, $abc. 'def') -> treats entire expression as Saola (multi-param misuse)
     */
    public function compileHydrationDirective($expression)
    {
        // Always use the full expression as-is (joins back if user mistakenly adds commas)
        $content = trim($expression);
        $attrkey = $this->keys['hydrate'] ?? 'data-hydrate';

        return $attrkey . '="<?php echo $__VIEW_ID__; ?>-<?php echo ' . $content . '; ?>"';
    }

    /**
     * Parse directive arguments, respecting quotes and nested parentheses
     * Splits on the first top-level comma only
     */
    protected function parseArguments($expression)
    {
        $expression = trim($expression);
        $length = strlen($expression);
        $depth = 0;
        $inSingleQuote = false;
        $inDoubleQuote = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            // Handle escape sequences
            if ($char === '\\' && $i + 1 < $length) {
                $i++;
                continue;
            }

            if ($char === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
            } elseif (!$inSingleQuote && !$inDoubleQuote) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    return [
                        substr($expression, 0, $i),
                        substr($expression, $i + 1),
                    ];
                }
            }
        }

        return [$expression];
    }

    /**
     * Process hydration directives (@hydrate and @hydration are aliases)
     * @hydrate($userState->name) -> data-binding="userState.name"
     * @hydration($username) -> data-binding="username"
     * Both directives produce the same output
     * Supports nested parentheses
     */
    public function processHydrationDirective($key, $content)
    {
        $attrkey = array_key_exists($key, $this->keys) ? $this->keys[$key] : $key;
        return $attrkey . '="<?php echo $__VIEW_ID__; ?>.-' . $content . ' ?>"';
    }
}