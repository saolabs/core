<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class AttrDirectiveService
{
    public function registerDirectives(): void
    {
        Blade::directive('attr', function ($expression) {
            return $this->processAttrDirective($expression);
        });

        Blade::directive('Attr', function ($expression) {
            return $this->processAttrDirective($expression);
        });
    }

    /**
     * Process @attr directive
     * Builds a simple config map and emits a call to $__helper->addTagAttribute
     */
    public function processAttrDirective($expression)
    {
        $expr = is_null($expression) ? '' : trim($expression);
        if ($expr === '') {
            return '';
        }

        $attrs = [];
        $globalStates = [];
           $vars = []; // Initialize $vars before using it

        // process a value expression: returns ['states'=>[], 'render'=>string]
        $processValue = function (string $valExpr) use (&$globalStates) {
            $vars_local = [];
            $render = $this->transformExpressionToRender($valExpr, $vars_local);
            $states = array_values(array_unique($vars_local));
            $globalStates = array_unique(array_merge($globalStates, $states));
            return [
                'states' => $states,
                'render' => "() => " . $render,
            ];
        };

        // Array form: @attr(['id' => $id, 'name' => $name, ...])
        if (strlen($expr) > 0 && $expr[0] === '[') {
            // strip outer brackets
            $inner = trim(substr($expr, 1, -1));
            $pairs = $this->splitTopLevel($inner, ',');
            foreach ($pairs as $pair) {
                // split by top-level =>
                $kv = $this->splitTopLevel($pair, '=>');
                if (count($kv) < 2) {
                    continue;
                }
                $keyRaw = trim($kv[0]);
                $valRaw = trim(implode('=>', array_slice($kv, 1)));
                // remove quotes around key if present
                if ((strlen($keyRaw) >= 2) && (($keyRaw[0] === '"' && $keyRaw[-1] === '"') || ($keyRaw[0] === "'" && $keyRaw[-1] === "'"))) {
                    $key = substr($keyRaw, 1, -1);
                } else {
                    $key = $keyRaw;
                }
                $info = $processValue($valRaw);
                $attrs[$key] = $info;
            }
        } else {
            // Single form: @attr('name', $nameExpression)
            if (preg_match('/^(["\'])(.+?)\1\s*,\s*(.+)$/s', $expr, $m)) {
                $key = $m[2];
                $valExpr = trim($m[3]);
                $info = $processValue($valExpr);
                $attrs[$key] = $info;
            }
        }

        $config = [
            'states' => array_values(array_unique($globalStates)),
            'attrs' => $attrs,
        ];

        $configCode = var_export($config, true);

        return "<?php echo \$__helper->addTagAttribute(\$__VIEW_PATH__, \$__VIEW_ID__, {$configCode}, {$expression}); ?>";
    }

    protected function normalizeStateName(string $valExpr): string
    {
        $v = trim($valExpr);
        // remove leading $ and any dereference or function calls
        $v = ltrim($v, '$ ');
        // remove non-alnum/_ characters
        return preg_replace('/[^A-Za-z0-9_]/', '', $v);
    }

    /**
     * Transform a PHP expression into a JS-like render expression and collect variables.
     * Replaces PHP concat (.) with + and removes leading $ from variables.
     * Returns the transformed expression string and fills $outVars with variable names.
     */
    protected function transformExpressionToRender(string $expr, array &$outVars): string
    {
        $len = strlen($expr);
        $inSingle = false;
        $inDouble = false;
        $escape = false;
        $out = '';
        $outVars = [];

        for ($i = 0; $i < $len; $i++) {
            $ch = $expr[$i];

            if ($escape) {
                $out .= $ch;
                $escape = false;
                continue;
            }

            if ($ch === "\\") {
                $out .= $ch;
                $escape = true;
                continue;
            }

            if ($inSingle) {
                $out .= $ch;
                if ($ch === "'") {
                    $inSingle = false;
                }
                continue;
            }

            if ($inDouble) {
                $out .= $ch;
                if ($ch === '"') {
                    $inDouble = false;
                }
                continue;
            }

            if ($ch === "'") {
                $inSingle = true;
                $out .= $ch;
                continue;
            }
            if ($ch === '"') {
                $inDouble = true;
                $out .= $ch;
                continue;
            }

            // object operator conversion: '->' => '.'
            if ($ch === '-' && ($i + 1 < $len) && $expr[$i + 1] === '>') {
                $out .= '.';
                $i++; // skip '>'
                continue;
            }

            // variable
            if ($ch === '$') {
                $j = $i + 1;
                if ($j < $len && preg_match('/[a-zA-Z_]/', $expr[$j])) {
                    $start = $j;
                    $j++;
                    while ($j < $len && preg_match('/[a-zA-Z0-9_]/', $expr[$j])) {
                        $j++;
                    }
                    $var = substr($expr, $start, $j - $start);
                    $out .= $var;
                    $outVars[] = $var;
                    $i = $j - 1;
                    continue;
                }
            }

            // concat operator '.' -> ' + ' when not decimal point
            if ($ch === '.') {
                $prev = $i - 1 >= 0 ? $expr[$i - 1] : '';
                $next = $i + 1 < $len ? $expr[$i + 1] : '';
                $isNumberDot = preg_match('/[0-9]/', $prev) && preg_match('/[0-9]/', $next);
                if (!$isNumberDot) {
                    $out .= ' + ';
                    continue;
                }
            }

            $out .= $ch;
        }

        $outVars = array_values(array_unique($outVars));
        return $out;
    }

    /**
     * Split a string by a delimiter at top-level only (ignores delimiters inside (), [], {} and quotes).
     * Returns array of parts with surrounding whitespace preserved.
     */
    protected function splitTopLevel(string $s, string $delimiter): array
    {
        $parts = [];
        $len = strlen($s);
        $buf = '';
        $depthPar = 0;
        $depthBr = 0;
        $depthCur = 0;
        $inSingle = false;
        $inDouble = false;
        $i = 0;
        $dlen = strlen($delimiter);

        while ($i < $len) {
            $ch = $s[$i];

            if ($ch === "\\") {
                // include escape and next char
                if ($i + 1 < $len) {
                    $buf .= $ch . $s[$i + 1];
                    $i += 2;
                    continue;
                }
                $buf .= $ch;
                $i++;
                continue;
            }

            if ($inSingle) {
                $buf .= $ch;
                if ($ch === "'") {
                    $inSingle = false;
                }
                $i++;
                continue;
            }
            if ($inDouble) {
                $buf .= $ch;
                if ($ch === '"') {
                    $inDouble = false;
                }
                $i++;
                continue;
            }

            if ($ch === "'") { $inSingle = true; $buf .= $ch; $i++; continue; }
            if ($ch === '"') { $inDouble = true; $buf .= $ch; $i++; continue; }

            if ($ch === '(') { $depthPar++; $buf .= $ch; $i++; continue; }
            if ($ch === ')') { $depthPar = max(0, $depthPar - 1); $buf .= $ch; $i++; continue; }
            if ($ch === '[') { $depthBr++; $buf .= $ch; $i++; continue; }
            if ($ch === ']') { $depthBr = max(0, $depthBr - 1); $buf .= $ch; $i++; continue; }
            if ($ch === '{') { $depthCur++; $buf .= $ch; $i++; continue; }
            if ($ch === '}') { $depthCur = max(0, $depthCur - 1); $buf .= $ch; $i++; continue; }

            // if delimiter matches here and we're at top-level, split
            if ($depthPar === 0 && $depthBr === 0 && $depthCur === 0) {
                if (substr($s, $i, $dlen) === $delimiter) {
                    $parts[] = $buf;
                    $buf = '';
                    $i += $dlen;
                    continue;
                }
            }

            $buf .= $ch;
            $i++;
        }

        if ($buf !== '') {
            $parts[] = $buf;
        }

        return $parts;
    }
}
