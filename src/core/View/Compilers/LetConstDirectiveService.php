<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class LetConstDirectiveService
{
    public function registerDirectives()
    {
        Blade::directive('let', function ($expression) {
            return $this->processLetConstDirective($expression, 'let');
        });

        Blade::directive('const', function ($expression) {
            return $this->processLetConstDirective($expression, 'const');
        });

        Blade::directive('useState', function ($expression) {
            return $this->processUseStateDirective($expression);
        });

        Blade::directive('exec', function ($expression) {
            return $this->processExecDirective($expression);
        });

        Blade::directive('states', function ($expression) {
            return $this->processStatesDirective($expression);
        });
    }

    public function processLetConstDirective($expression, $type)
    {
        $segments = $this->splitExpression($expression);
        $phpCode = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (empty($segment)) {
                continue;
            }

            // Handle destructuring: `[$var1, $var2] = ...` or `{$var1, $var2} = ...`
            if (preg_match('/^(\[[^\]]+\]|\{[^\}]+\})\s*=\s*(.+)$/s', $segment, $matches)) {
                $leftSide = trim($matches[1]);
                $rightSide = trim($matches[2]);

                // Fix variable names in array destructuring - ensure all variables have $ prefix
                if (Str::startsWith($leftSide, '[') && Str::endsWith($leftSide, ']')) {
                    // Extract variables from array destructuring like [$count, setCount]
                    $leftSide = $this->fixArrayDestructuringVariables($leftSide);
                }

                // Handle object destructuring with (array) cast for PHP
                if (Str::startsWith($leftSide, '{') && Str::endsWith($leftSide, '}')) {
                    $keys = [];
                    preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $leftSide, $keyMatches);
                    foreach ($keyMatches[1] as $key) {
                        $keys[] = "'{$key}' => \${$key}";
                    }
                    $leftSide = '[' . implode(', ', $keys) . ']';
                    $rightSide = '(array) ' . $rightSide; // Add (array) cast for PHP
                }

                $phpCode[] = "{$leftSide} = {$rightSide};";
                // If right side is a useState(...) call, register the initial state value for UI
                if (preg_match('/useState\s*\(/', $rightSide)) {
                    // extract variable names from left side (e.g. [$state, $setState])
                    preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $leftSide, $mvars);
                    if (!empty($mvars[1])) {
                        $stateKey = $mvars[1][0];
                        $stateVar = '$' . $stateKey;
                        $phpCode[] = "\$__helper->setState(\$__VIEW_PATH__, \$__VIEW_ID__, '{$stateKey}', {$stateVar});";
                    }
                }
            } else {
                // Standard assignment: `$var = value`
                $phpCode[] = "{$segment};";
            }
        }

        return "<?php " . implode(' ', $phpCode) . " ?>";
    }

    public function processUseStateDirective($expression)
    {
        $expression = trim($expression);

        // Remove outer parentheses if present: (expression) -> expression
        if (preg_match('/^\((.*)\)$/s', $expression, $matches)) {
            $expression = trim($matches[1]);
        }

        // Check if it's array format: ['key1' => value1, 'key2' => value2]
        // Must start with [ and contain => with balanced brackets
        if (preg_match('/^\[.*\]$/s', $expression) && $this->containsArrowOperator($expression)) {
            return $this->processUseStateArrayFormat($expression);
        }

        // Parse parameters
        $params = $this->parseParams($expression);

        // New 2-parameter format: @useState($varName, value)
        if (count($params) === 2) {
            $varName = trim($params[0]);
            $value = trim($params[1]);

            // Check if first param is a variable (starts with $)
            if (strpos($varName, '$') === 0) {
                $stateVar = $varName;
                $stateKey = preg_replace('/^\$/', '', $stateVar);

                // Auto-generate setter name: $user -> $setUser
                $setterName = 'set' . ucfirst($stateKey);
                $setStateVar = '$' . $setterName;

                // Add helper call to register initial state value for UI
                return "<?php [{$stateVar}, {$setStateVar}] = useState({$value}); \$__helper->setState(\$__VIEW_PATH__, \$__VIEW_ID__, '{$stateKey}', {$stateVar}); ?>";
            }
        }

        // Original 3-parameter format: $value, $stateName, $setStateName
        if (count($params) === 3) {
            list($value, $stateName, $setStateName) = $params;

            // Check if stateName and setStateName are variables (start with $)
            if (strpos($stateName, '$') === 0) {
                // Already a variable, use as is
                $stateVar = $stateName;
            } else {
                // Remove quotes and add $ prefix
                $stateVar = '$' . trim($stateName, "'\"");
            }

            if (strpos($setStateName, '$') === 0) {
                // Already a variable, use as is
                $setStateVar = $setStateName;
            } else {
                // Remove quotes and add $ prefix
                $setStateVar = '$' . trim($setStateName, "'\"");
            }

            // Add helper call to register initial state value for UI
            $stateKey = preg_replace('/^\$/', '', $stateVar);
            return "<?php [{$stateVar}, {$setStateVar}] = useState({$value}); \$__helper->setState(\$__VIEW_PATH__, \$__VIEW_ID__, '{$stateKey}', {$stateVar}); ?>";
        }

        // If parsing fails or parameters are incorrect, return the original directive to prevent hard errors
        return "<?php // Invalid @useState directive: @useState({$expression}) ?>";
    }

    /**
     * Process @states directive
     *
     * Supports two formats:
     * 1. Array format: @states(['key1' => value1, 'key2' => value2])
     * 2. Variable assignment format: @states($key1 = value1, $key2 = value2)
     *
     * Both generate:
     *   [$key, $setKey] = useState(value);
     *   $__helper->setState($__VIEW_PATH__, $__VIEW_ID__, 'key', $key);
     */
    public function processStatesDirective($expression)
    {
        $expression = trim($expression);

        // Remove outer parentheses if present
        if (preg_match('/^\((.*)\)$/s', $expression, $matches)) {
            $expression = trim($matches[1]);
        }

        // Check if it's array format: ['key1' => value1, 'key2' => value2]
        if (preg_match('/^\[.*\]$/s', $expression) && $this->containsArrowOperator($expression)) {
            return $this->processUseStateArrayFormat($expression);
        }

        // Variable assignment format: $key1 = value1, $key2 = value2
        return $this->processStatesAssignmentFormat($expression);
    }

    /**
     * Process @states variable assignment format
     * @states($count = 0, $name = 'default', $items = [])
     *
     * Generates for each pair:
     *   [$count, $setCount] = useState(0);
     *   $__helper->setState($__VIEW_PATH__, $__VIEW_ID__, 'count', $count);
     */
    protected function processStatesAssignmentFormat($expression)
    {
        $pairs = $this->parseStatesAssignments($expression);
        $phpCode = [];

        foreach ($pairs as $pair) {
            if (!isset($pair['key']) || !isset($pair['value'])) {
                continue;
            }

            $key = $pair['key'];
            $value = $pair['value'];

            $stateVar = '$' . $key;
            $setStateVar = '$set' . ucfirst($key);

            $phpCode[] = "[{$stateVar}, {$setStateVar}] = useState({$value});";
            $phpCode[] = "\$__helper->setState(\$__VIEW_PATH__, \$__VIEW_ID__, '{$key}', {$stateVar});";
        }

        if (empty($phpCode)) {
            return "<?php // Invalid @states directive ?>";
        }

        return "<?php " . implode(' ', $phpCode) . " ?>";
    }

    /**
     * Parse variable assignment pairs from expression like: $key1 = value1, $key2 = value2
     */
    protected function parseStatesAssignments($expression)
    {
        $pairs = [];
        $balance = 0;
        $currentPair = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            // Handle escaping within strings
            if ($inString && $char === '\\') {
                $currentPair .= $char;
                if ($i + 1 < strlen($expression)) {
                    $currentPair .= $expression[++$i];
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                if ($inString && $char === $stringChar) {
                    $inString = false;
                } elseif (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                }
            }

            if (!$inString) {
                if ($char === '(' || $char === '[' || $char === '{') {
                    $balance++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $balance--;
                } elseif ($char === ',' && $balance === 0) {
                    $pairs[] = $this->parseStatesAssignment($currentPair);
                    $currentPair = '';
                    continue;
                }
            }
            $currentPair .= $char;
        }

        if (trim($currentPair) !== '') {
            $pairs[] = $this->parseStatesAssignment($currentPair);
        }

        return array_filter($pairs);
    }

    /**
     * Parse a single assignment: $key = value
     */
    protected function parseStatesAssignment($assignmentString)
    {
        $assignmentString = trim($assignmentString);

        // Match $varName = value (using first top-level = only)
        if (!preg_match('/^\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/s', $assignmentString, $matches)) {
            return null;
        }

        return [
            'key' => trim($matches[1]),
            'value' => trim($matches[2]),
        ];
    }

    public function processExecDirective($expression)
    {
        $expression = trim($expression);

        // Remove outer parentheses if present
        if (Str::startsWith($expression, '(') && Str::endsWith($expression, ')')) {
            $expression = substr($expression, 1, -1);
        }

        $expression = trim($expression);

        if ($expression === '') {
            return "<?php ?>";
        }

        $parsed = '';
        $balance = 0;
        $inString = false;
        $stringChar = '';
        $len = strlen($expression);

        for ($i = 0; $i < $len; $i++) {
            $char = $expression[$i];

            // Handle escaping within strings
            if ($inString && $char === '\\') {
                $parsed .= $char;
                if ($i + 1 < $len) {
                    $parsed .= $expression[++$i];
                }
                continue;
            }

            // Handle quotes
            if (($char === '"' || $char === "'")) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }

            // Handle structural nesting when not in string
            if (!$inString) {
                if ($char === '(' || $char === '[' || $char === '{') {
                    $balance++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $balance--;
                } elseif ($char === ',' && $balance === 0) {
                    // Replace top-level comma with semicolon
                    $parsed .= ';';
                    continue;
                }
            }

            $parsed .= $char;
        }

        $parsed = trim($parsed);

        // Ensure expression ends with semicolon if it doesn't already
        if (!Str::endsWith($parsed, ';')) {
            $parsed .= ';';
        }

        return "<?php {$parsed} ?>";
    }

    /**
     * Check if expression contains => operator (not in strings)
     */
    protected function containsArrowOperator($expression)
    {
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($expression) - 1; $i++) {
            $char = $expression[$i];

            if ($char === "'" || $char === '"') {
                if ($inString && $char === $stringChar) {
                    $inString = false;
                } elseif (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                }
            }

            if (!$inString && $char === '=' && $expression[$i + 1] === '>') {
                return true;
            }
        }

        return false;
    }

    /**
     * Process array format: @useState(['key1' => value1, 'key2' => value2])
     * Each key creates: $key variable and $setKey setter
     */
    protected function processUseStateArrayFormat($arrayExpression)
    {
        $phpCode = [];

        // Remove [ and ] brackets
        $content = trim($arrayExpression, '[]');

        // Parse key => value pairs
        $pairs = $this->parseArrayPairs($content);

        foreach ($pairs as $pair) {
            if (!isset($pair['key']) || !isset($pair['value'])) {
                continue;
            }

            $key = $pair['key'];
            $value = $pair['value'];

            // Create variable names
            $stateVar = '$' . $key;
            $setterName = 'set' . ucfirst($key);
            $setStateVar = '$' . $setterName;

            // Create PHP code for state initialization
            $phpCode[] = "[{$stateVar}, {$setStateVar}] = useState({$value});";
            $phpCode[] = "\$__helper->setState(\$__VIEW_PATH__, \$__VIEW_ID__, '{$key}', {$stateVar});";
        }

        if (empty($phpCode)) {
            return "<?php // Invalid @useState array format ?>";
        }

        return "<?php " . implode(' ', $phpCode) . " ?>";
    }

    /**
     * Parse array pairs from string like: 'key1' => value1, 'key2' => value2
     */
    protected function parseArrayPairs($content)
    {
        $pairs = [];
        $balance = 0;
        $currentPair = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];

            if ($char === "'" || $char === '"') {
                if ($inString && $char === $stringChar) {
                    $inString = false;
                } elseif (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                }
            }

            if (!$inString) {
                if ($char === '(' || $char === '[' || $char === '{') {
                    $balance++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $balance--;
                } elseif ($char === ',' && $balance === 0) {
                    $pairs[] = $this->parseArrayPair($currentPair);
                    $currentPair = '';
                    continue;
                }
            }
            $currentPair .= $char;
        }

        if (trim($currentPair) !== '') {
            $pairs[] = $this->parseArrayPair($currentPair);
        }

        return array_filter($pairs);
    }

    /**
     * Parse single key => value pair
     */
    protected function parseArrayPair($pairString)
    {
        $pairString = trim($pairString);

        if (strpos($pairString, '=>') === false) {
            return null;
        }

        list($key, $value) = explode('=>', $pairString, 2);

        $key = trim($key);
        $value = trim($value);

        // Remove quotes from key
        $key = trim($key, "'\"");

        // Remove $ prefix if present
        $key = ltrim($key, '$');

        return [
            'key' => $key,
            'value' => $value
        ];
    }

    protected function splitExpression($expression)
    {
        $segments = [];
        $balance = 0;
        $currentSegment = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            if ($char === "'" || $char === '"') {
                if ($inString && $char === $stringChar) {
                    $inString = false;
                } elseif (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                }
            }

            if (!$inString) {
                if ($char === '(' || $char === '[' || $char === '{') {
                    $balance++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $balance--;
                } elseif ($char === ',' && $balance === 0) {
                    $segments[] = $currentSegment;
                    $currentSegment = '';
                    continue;
                }
            }
            $currentSegment .= $char;
        }
        $segments[] = $currentSegment; // Add the last segment
        return array_filter($segments, 'trim');
    }

    protected function parseParams($expression)
    {
        $params = [];
        $balance = 0;
        $currentParam = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            if ($char === "'" || $char === '"') {
                if ($inString && $char === $stringChar) {
                    $inString = false;
                } elseif (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                }
            }

            if (!$inString) {
                if ($char === '(' || $char === '[' || $char === '{') {
                    $balance++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $balance--;
                } elseif ($char === ',' && $balance === 0) {
                    $params[] = trim($currentParam);
                    $currentParam = '';
                    continue;
                }
            }
            $currentParam .= $char;
        }
        $params[] = trim($currentParam); // Add the last parameter

        // Filter out empty params but keep numeric zeros and reindex
        $filtered = [];
        foreach ($params as $param) {
            $param = trim($param);
            if ($param !== '') {
                $filtered[] = $param;
            }
        }
        return $filtered;
    }

    /**
     * Fix variable names in array destructuring to ensure all have $ prefix
     * Converts [$count, setCount] to [$count, $setCount]
     */
    protected function fixArrayDestructuringVariables($arrayString)
    {
        // Remove [ and ] brackets
        $content = trim($arrayString, '[]');

        // Split by comma, preserving spaces and structure
        $variables = [];
        $parts = explode(',', $content);

        foreach ($parts as $part) {
            $part = trim($part);

            // If it's a variable that doesn't start with $, add it
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $part)) {
                $variables[] = '$' . $part;
            } else {
                // Already has $ or is more complex expression, keep as is
                $variables[] = $part;
            }
        }

        return '[' . implode(', ', $variables) . ']';
    }
}
