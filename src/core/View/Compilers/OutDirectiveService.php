<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class OutDirectiveService
{
    public function registerDirectives(): void
    {
        Blade::directive('out', function ($expression) {
            return $this->processOutDirective($expression);
        });
        Blade::directive('Out', function ($expression) {
            return $this->processOutDirective($expression);
        });
    }

    /**
     * Process @out directive
     * Wraps the expression with a one:output comment and generates subscribe list
     */
    public function processOutDirective($expression)
    {
        $content = is_null($expression) ? '' : trim($expression);
        if ($content === '') {
            return '';
        }

        // Extract variable base names like $abc, $arr, $obj while
        // - ignoring $ inside single-quoted strings
        // - honoring escaped backslashes (\$)
        $vars = $this->parseVariablesFromExpression($content);

        $subscribe = '';
        if (!empty($vars)) {
            $subscribe = implode(',', $vars);
            // $keys = implode(',', array_map(fn($var) => "'$var'", $vars));
        }
        

        // Build output wrapper. Escape $__VIEW_PATH__ and $__VIEW_ID__ dollar signs.
        $wrapper  = '<?php $__OC_TASK_ID__ = uniqid(); $__CURRENT_OC_INDEX__ = $__helper->addOutputComponent($__VIEW_PATH__, $__VIEW_ID__, $__OC_TASK_ID__, "'.$subscribe.'"); ?>';
        $wrapper .= '<!-- [one:output id="<?php echo $__OC_TASK_ID__; ?>"] -->';
        $wrapper .= '<?php echo ' . $content . '; ?>';
        $wrapper .= '<!-- [/one:output] -->';

        return $wrapper;
    }

    /**
     * Parse variables from an arbitrary PHP expression string.
     * - ignores dollar signs inside single-quoted strings
     * - supports variables inside double-quoted template strings
     */
    protected function parseVariablesFromExpression(string $expr): array
    {
        $len = strlen($expr);
        $inSingle = false;
        $inDouble = false;
        $escape = false;
        $vars = [];

        for ($i = 0; $i < $len; $i++) {
            $ch = $expr[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($ch === "\\") {
                $escape = true;
                continue;
            }

            if ($inSingle) {
                if ($ch === "'") {
                    $inSingle = false;
                }
                continue;
            }

            if ($inDouble) {
                if ($ch === '"') {
                    $inDouble = false;
                    continue;
                }
                if ($ch === '$') {
                    $j = $i + 1;
                    if ($j < $len && preg_match('/[a-zA-Z_]/', $expr[$j])) {
                        $start = $j;
                        $j++;
                        while ($j < $len && preg_match('/[a-zA-Z0-9_]/', $expr[$j])) {
                            $j++;
                        }
                        $vars[] = substr($expr, $start, $j - $start);
                        $i = $j - 1;
                        continue;
                    }
                }
                continue;
            }

            // Not in any quote
            if ($ch === "'") {
                $inSingle = true;
                continue;
            }
            if ($ch === '"') {
                $inDouble = true;
                continue;
            }
            if ($ch === '$') {
                $j = $i + 1;
                if ($j < $len && preg_match('/[a-zA-Z_]/', $expr[$j])) {
                    $start = $j;
                    $j++;
                    while ($j < $len && preg_match('/[a-zA-Z0-9_]/', $expr[$j])) {
                        $j++;
                    }
                    $vars[] = substr($expr, $start, $j - $start);
                    $i = $j - 1;
                }
            }
        }

        return array_values(array_unique($vars));
    }
}
