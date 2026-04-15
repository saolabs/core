<?php

namespace Saola\Core\View\Compilers;

class CommonDirectiveService
{
    /**
     * Extract state key from variable (remove $ prefix)
     */
    public function extractStateKey($stateVar)
    {
        $stateVar = trim($stateVar);
        if (strpos($stateVar, '$') === 0) {
            return substr($stateVar, 1);
        }
        return $stateVar;
    }

    /**
     * Parse yield attribute parameters
     */
    public function parseYieldAttrParams($expression)
    {
        if (empty($expression)) {
            return [];
        }

        $params = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        $parenCount = 0;

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                $inQuotes = false;
                $quoteChar = '';
                $current .= $char;
            } elseif ($char === '[' && !$inQuotes) {
                $parenCount++;
                $current .= $char;
            } elseif ($char === ']' && !$inQuotes) {
                $parenCount--;
                $current .= $char;
            } elseif ($char === ',' && !$inQuotes && $parenCount === 0) {
                if (trim($current)) {
                    $params[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current)) {
            $params[] = trim($current);
        }

        return $params;
    }

    /**
     * Parse array content and extract items
     */
    public function parseArrayContent($arrayContent)
    {
        $items = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';
        $parenCount = 0;

        for ($i = 0; $i < strlen($arrayContent); $i++) {
            $char = $arrayContent[$i];

            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                $inQuotes = false;
                $quoteChar = '';
                $current .= $char;
            } elseif ($char === '[' && !$inQuotes) {
                $parenCount++;
                $current .= $char;
            } elseif ($char === ']' && !$inQuotes) {
                $parenCount--;
                $current .= $char;
            } elseif ($char === ',' && !$inQuotes && $parenCount === 0) {
                if (trim($current)) {
                    $items[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current)) {
            $items[] = trim($current);
        }

        return $items;
    }

    /**
     * Check if string is array syntax
     */
    public function isArraySyntax($content)
    {
        return strpos($content, '[') === 0 && strpos($content, ']') === strlen($content) - 1;
    }

    /**
     * Check if array content contains key-value pairs
     */
    public function hasKeyValuePairs($arrayContent)
    {
        return strpos($arrayContent, '=>') !== false;
    }

    /**
     * Generate PHP echo statement for helper method
     */
    public function generateHelperEcho($method, $params)
    {
        $paramsJson = json_encode($params);
        return "<?php echo \$__helper->{$method}(\$__VIEW_PATH__, \$__VIEW_ID__, {$paramsJson});?>";
    }
}
