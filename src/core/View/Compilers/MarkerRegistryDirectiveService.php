<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class MarkerRegistryDirectiveService
{

    /**
     * Register the marker registry directives
     * @marker and @markerRegistry are aliases of each other
     */
    public function registerDirectives(): void {
        Blade::directive('openMarker', function ($expression) {
            return $this->compileOpenMarkerDirective($expression);
        });
        Blade::directive('openmarker', function ($expression) {
            return $this->compileOpenMarkerDirective($expression);
        });
        Blade::directive('startMarker', function ($expression) {
            return $this->compileOpenMarkerDirective($expression);
        });
        Blade::directive('startmarker', function ($expression) {
            return $this->compileOpenMarkerDirective($expression);
        });
        Blade::directive('closeMarker', function ($expression) {
            return $this->compileCloseMarkerDirective($expression);
        });
        Blade::directive('closemarker', function ($expression) {
            return $this->compileCloseMarkerDirective($expression);
        });
        Blade::directive('endMarker', function ($expression) {
            return $this->compileCloseMarkerDirective($expression);
        });
        Blade::directive('endmarker', function ($expression) {
            return $this->compileCloseMarkerDirective($expression);
        });
    }

    /**
     * Compile open/start marker directive
     *
     * @startMarker('view')                         -> startMarker('view', $__VIEW_ID__)
     * @startMarker('view', 'custom-id')            -> startMarker('view', 'custom-id')
     * @startMarker('block', 'sidebar')             -> startMarker('block', $__VIEW_ID__.'-'.'sidebar')
     * @startMarker('block')                        -> startMarker('block', '')
     * @startMarker('block', 'sidebar', ['a'=>1])   -> startMarker('block', $__VIEW_ID__.'-'.'sidebar', ['a'=>1])
     */
    public function compileOpenMarkerDirective($expression)
    {
        $parts = $this->parseArguments($expression);
        $name = trim($parts[0] ?? "''");
        $registryId = isset($parts[1]) ? trim($parts[1]) : null;
        $attrs = isset($parts[2]) ? trim($parts[2]) : '[]';

        $php = '<?php ';
        $php .= '$__m_name = ' . $name . '; ';

        if ($registryId !== null) {
            $php .= '$__m_rid = ' . $registryId . '; ';
            $php .= 'if ($__m_name === \'view\') { ';
            $php .= '$__m_id = $__m_rid; ';
            $php .= '} else { ';
            $php .= '$__m_id = $__VIEW_ID__ . \'-\' . $__m_rid; ';
            $php .= '} ';
        } else {
            $php .= 'if ($__m_name === \'view\') { ';
            $php .= '$__m_id = $__VIEW_ID__; ';
            $php .= '} else { ';
            $php .= '$__m_id = \'\'; ';
            $php .= '} ';
        }

        $php .= 'echo $__helper->startMarker($__m_name, $__m_id, ' . $attrs . '); ?>';

        return $php;
    }

    /**
     * Compile close/end marker directive
     *
     * @endMarker('view')              -> endMarker('view', $__VIEW_ID__)
     * @endMarker('view', 'custom-id') -> endMarker('view', 'custom-id')
     * @endMarker('block', 'sidebar')  -> endMarker('block', $__VIEW_ID__.'-'.'sidebar')
     * @endMarker('block')             -> endMarker('block', '')
     */
    public function compileCloseMarkerDirective($expression)
    {
        $parts = $this->parseArguments($expression);
        $name = trim($parts[0] ?? "''");
        $registryId = isset($parts[1]) ? trim($parts[1]) : null;

        $php = '<?php ';
        $php .= '$__m_name = ' . $name . '; ';

        if ($registryId !== null) {
            $php .= '$__m_rid = ' . $registryId . '; ';
            $php .= 'if ($__m_name === \'view\') { ';
            $php .= '$__m_id = $__m_rid; ';
            $php .= '} else { ';
            $php .= '$__m_id = $__VIEW_ID__ . \'-\' . $__m_rid; ';
            $php .= '} ';
        } else {
            $php .= 'if ($__m_name === \'view\') { ';
            $php .= '$__m_id = $__VIEW_ID__; ';
            $php .= '} else { ';
            $php .= '$__m_id = \'\'; ';
            $php .= '} ';
        }

        $php .= 'echo $__helper->endMarker($__m_name, $__m_id); ?>';

        return $php;
    }

    /**
     * Parse directive arguments, respecting quotes and nested parentheses/brackets
     * Splits on top-level commas
     */
    protected function parseArguments($expression)
    {
        $expression = trim($expression);
        $length = strlen($expression);
        $depth = 0;
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $parts = [];
        $start = 0;

        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $i++;
                continue;
            }

            if ($char === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
            } elseif (!$inSingleQuote && !$inDoubleQuote) {
                if (in_array($char, ['(', '[', '{'])) {
                    $depth++;
                } elseif (in_array($char, [')', ']', '}'])) {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    $parts[] = substr($expression, $start, $i - $start);
                    $start = $i + 1;
                }
            }
        }

        $parts[] = substr($expression, $start);

        return $parts;
    }
}
