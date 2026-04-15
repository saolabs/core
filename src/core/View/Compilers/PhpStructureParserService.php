<?php

namespace Saola\Core\View\Compilers;

/**
 * PHP Structure Parser Service
 * Chuyên xử lý parsing và nhận diện các cấu trúc PHP phức tạp
 */
class PhpStructureParserService
{
    /**
     * Parse PHP structure từ expression
     */
    public function parsePhpStructure($expression, $depth = 0)
    {
        // Ngăn chặn recursion quá sâu
        if ($depth > 10) {
            return ['type' => 'string', 'value' => $expression];
        }
        
        $expression = trim($expression);
        
        // 1. Array syntax: [key => value, ...]
        if (preg_match('/^\[.*\]$/', $expression)) {
            return $this->parseArrayStructure($expression, $depth);
        }
        
        // 2. Object syntax: {key: value, ...} hoặc (object)['key' => value]
        if (preg_match('/^\{.*\}$/', $expression) || preg_match('/^\(object\)\s*\[.*\]$/', $expression)) {
            return $this->parseObjectStructure($expression, $depth);
        }
        
        // 3. Function call: functionName(params)
        if (preg_match('/^(\w+)\s*\((.*)\)$/', $expression, $matches)) {
            return $this->parseFunctionCall($matches[1], $matches[2], $depth);
        }
        
        // 4. Method call: object->method(params) hoặc object::method(params)
        if (preg_match('/^(.+?)(->|::)(\w+)\s*\((.*)\)$/', $expression, $matches)) {
            return $this->parseMethodCall($matches[1], $matches[2], $matches[3], $matches[4], $depth);
        }
        
        // 5. Property access: object->property hoặc object::property
        if (preg_match('/^(.+?)(->|::)(\w+)$/', $expression, $matches)) {
            return $this->parsePropertyAccess($matches[1], $matches[2], $matches[3], $depth);
        }
        
        // 6. Array access: array[key] hoặc array['key']
        if (preg_match('/^(.+?)\[([^\]]+)\]$/', $expression, $matches)) {
            return $this->parseArrayAccess($matches[1], $matches[2], $depth);
        }
        
        // 7. Ternary operator: condition ? true : false
        if (strpos($expression, '?') !== false && strpos($expression, ':') !== false) {
            return $this->parseTernaryOperator($expression, $depth);
        }
        
        // 8. String concatenation: 'string' . variable . 'string'
        if (strpos($expression, '.') !== false) {
            return $this->parseStringConcatenation($expression, $depth);
        }
        
        // 9. Simple variable hoặc literal
        return $this->parseSimpleValue($expression);
    }
    
    /**
     * Parse array structure: [key => value, ...]
     */
    protected function parseArrayStructure($expression, $depth = 0)
    {
        $content = trim($expression, '[]');
        if (empty($content)) {
            return ['type' => 'array', 'value' => '[]'];
        }
        
        $items = $this->splitArrayItems($content);
        $parsedItems = [];
        
        foreach ($items as $item) {
            $parsedItems[] = $this->parseArrayItem($item, $depth);
        }
        
        return ['type' => 'array', 'value' => $parsedItems];
    }
    
    /**
     * Parse object structure: {key: value, ...} hoặc (object)['key' => value]
     */
    protected function parseObjectStructure($expression)
    {
        if (preg_match('/^\(object\)\s*\[(.*)\]$/', $expression, $matches)) {
            // (object)['key' => value] syntax
            $content = $matches[1];
            $items = $this->splitArrayItems($content);
            $parsedItems = [];
            
            foreach ($items as $item) {
                $parsedItems[] = $this->parseArrayItem($item);
            }
            
            return ['type' => 'object', 'value' => $parsedItems, 'syntax' => 'object_array'];
        } else {
            // {key: value, ...} syntax
            $content = trim($expression, '{}');
            $items = $this->splitObjectItems($content);
            $parsedItems = [];
            
            foreach ($items as $item) {
                $parsedItems[] = $this->parseObjectItem($item);
            }
            
            return ['type' => 'object', 'value' => $parsedItems, 'syntax' => 'object_curly'];
        }
    }
    
    /**
     * Parse function call: functionName(params)
     */
    protected function parseFunctionCall($functionName, $paramsString)
    {
        $params = $this->parseFunctionParameters($paramsString);
        
        return [
            'type' => 'function_call',
            'function' => $functionName,
            'params' => $params
        ];
    }
    
    /**
     * Parse method call: object->method(params) hoặc object::method(params)
     */
    protected function parseMethodCall($object, $operator, $method, $paramsString)
    {
        $params = $this->parseFunctionParameters($paramsString);
        
        return [
            'type' => 'method_call',
            'object' => $this->parsePhpStructure($object),
            'operator' => $operator,
            'method' => $method,
            'params' => $params
        ];
    }
    
    /**
     * Parse property access: object->property hoặc object::property
     */
    protected function parsePropertyAccess($object, $operator, $property)
    {
        return [
            'type' => 'property_access',
            'object' => $this->parsePhpStructure($object),
            'operator' => $operator,
            'property' => $property
        ];
    }
    
    /**
     * Parse array access: array[key] hoặc array['key']
     */
    protected function parseArrayAccess($array, $key)
    {
        return [
            'type' => 'array_access',
            'array' => $this->parsePhpStructure($array),
            'key' => $this->parsePhpStructure($key)
        ];
    }
    
    /**
     * Parse ternary operator: condition ? true : false
     */
    protected function parseTernaryOperator($expression)
    {
        $parts = $this->splitTernaryOperator($expression);
        
        return [
            'type' => 'ternary',
            'condition' => $this->parsePhpStructure($parts[0]),
            'true_value' => $this->parsePhpStructure($parts[1]),
            'false_value' => $this->parsePhpStructure($parts[2])
        ];
    }
    
    /**
     * Parse string concatenation: 'string' . variable . 'string'
     */
    protected function parseStringConcatenation($expression)
    {
        $parts = $this->splitStringConcatenation($expression);
        $parsedParts = [];
        
        foreach ($parts as $part) {
            $parsedParts[] = $this->parsePhpStructure($part);
        }
        
        return [
            'type' => 'string_concatenation',
            'parts' => $parsedParts
        ];
    }
    
    /**
     * Parse simple value: variable, string, number, boolean, null
     */
    protected function parseSimpleValue($expression)
    {
        $expression = trim($expression);
        
        if (empty($expression)) {
            return ['type' => 'null', 'value' => 'null'];
        }
        
        // String literals
        if (strlen($expression) >= 2 && 
            (($expression[0] === '"' && $expression[-1] === '"') || 
             ($expression[0] === "'" && $expression[-1] === "'"))) {
            return ['type' => 'string', 'value' => $expression];
        }
        
        // Numeric literals
        if (is_numeric($expression)) {
            return ['type' => 'number', 'value' => $expression];
        }
        
        // Boolean literals
        if (in_array(strtolower($expression), ['true', 'false'])) {
            return ['type' => 'boolean', 'value' => strtolower($expression) === 'true' ? 'true' : 'false'];
        }
        
        // Null literal
        if (strtolower($expression) === 'null') {
            return ['type' => 'null', 'value' => 'null'];
        }
        
        // Variable
        if (strpos($expression, '$') === 0) {
            return ['type' => 'variable', 'value' => $expression];
        }
        
        // Constant
        return ['type' => 'constant', 'value' => $expression];
    }
    
    /**
     * Generate PHP code từ parsed structure
     */
    public function generatePhpCode($structure)
    {
        switch ($structure['type']) {
            case 'array':
                if (is_array($structure['value'])) {
                    $items = [];
                    foreach ($structure['value'] as $item) {
                        if (isset($item['key'])) {
                            $key = $this->generatePhpCode($item['key']);
                            $value = $this->generatePhpCode($item['value']);
                            $items[] = "$key => $value";
                        } else {
                            $items[] = $this->generatePhpCode($item);
                        }
                    }
                    return '[' . implode(', ', $items) . ']';
                } else {
                    return $structure['value'];
                }
                
            case 'object':
                if ($structure['syntax'] === 'object_array') {
                    $items = [];
                    foreach ($structure['value'] as $item) {
                        if (isset($item['key'])) {
                            $key = $this->generatePhpCode($item['key']);
                            $value = $this->generatePhpCode($item['value']);
                            $items[] = "$key => $value";
                        } else {
                            $items[] = $this->generatePhpCode($item);
                        }
                    }
                    return '(object)[' . implode(', ', $items) . ']';
                } else {
                    $items = [];
                    foreach ($structure['value'] as $item) {
                        if (isset($item['key'])) {
                            $key = $this->generatePhpCode($item['key']);
                            $value = $this->generatePhpCode($item['value']);
                            $items[] = "$key: $value";
                        } else {
                            $items[] = $this->generatePhpCode($item);
                        }
                    }
                    return '{' . implode(', ', $items) . '}';
                }
                
            case 'function_call':
                $params = [];
                foreach ($structure['params'] as $param) {
                    $params[] = $this->generatePhpCode($param);
                }
                return $structure['function'] . '(' . implode(', ', $params) . ')';
                
            case 'method_call':
                $object = $this->generatePhpCode($structure['object']);
                $params = [];
                foreach ($structure['params'] as $param) {
                    $params[] = $this->generatePhpCode($param);
                }
                return $object . $structure['operator'] . $structure['method'] . '(' . implode(', ', $params) . ')';
                
            case 'property_access':
                $object = $this->generatePhpCode($structure['object']);
                return $object . $structure['operator'] . $structure['property'];
                
            case 'array_access':
                $array = $this->generatePhpCode($structure['array']);
                $key = $this->generatePhpCode($structure['key']);
                return $array . '[' . $key . ']';
                
            case 'ternary':
                $condition = $this->generatePhpCode($structure['condition']);
                $trueValue = $this->generatePhpCode($structure['true_value']);
                $falseValue = $this->generatePhpCode($structure['false_value']);
                return "($condition ? $trueValue : $falseValue)";
                
            case 'string_concatenation':
                $parts = [];
                foreach ($structure['parts'] as $part) {
                    $parts[] = $this->generatePhpCode($part);
                }
                return implode(' . ', $parts);
                
            case 'string':
            case 'number':
            case 'boolean':
            case 'null':
            case 'variable':
            case 'constant':
            default:
                return $structure['value'];
        }
    }
    
    // Helper methods
    protected function splitArrayItems($content)
    {
        $items = [];
        $current = '';
        $parenCount = 0;
        $bracketCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                if ($i > 0 && $content[$i - 1] === '\\') {
                    $current .= $char;
                    continue;
                }
                $inQuotes = false;
                $quoteChar = '';
            } elseif (!$inQuotes) {
                if ($char === '(') {
                    $parenCount++;
                } elseif ($char === ')') {
                    $parenCount--;
                } elseif ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;
                } elseif ($char === ',' && $parenCount === 0 && $bracketCount === 0) {
                    $items[] = trim($current);
                    $current = '';
                    continue;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $items[] = trim($current);
        }
        
        return $items;
    }
    
    protected function parseArrayItem($item)
    {
        if (strpos($item, '=>') !== false) {
            $parts = explode('=>', $item, 2);
            return [
                'key' => $this->parsePhpStructure(trim($parts[0])),
                'value' => $this->parsePhpStructure(trim($parts[1]))
            ];
        } else {
            return $this->parsePhpStructure($item);
        }
    }
    
    protected function splitObjectItems($content)
    {
        $items = [];
        $current = '';
        $parenCount = 0;
        $bracketCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                if ($i > 0 && $content[$i - 1] === '\\') {
                    $current .= $char;
                    continue;
                }
                $inQuotes = false;
                $quoteChar = '';
            } elseif (!$inQuotes) {
                if ($char === '(') {
                    $parenCount++;
                } elseif ($char === ')') {
                    $parenCount--;
                } elseif ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;
                } elseif ($char === ',' && $parenCount === 0 && $bracketCount === 0) {
                    $items[] = trim($current);
                    $current = '';
                    continue;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $items[] = trim($current);
        }
        
        return $items;
    }
    
    protected function parseObjectItem($item)
    {
        if (strpos($item, ':') !== false) {
            $parts = explode(':', $item, 2);
            return [
                'key' => $this->parsePhpStructure(trim($parts[0])),
                'value' => $this->parsePhpStructure(trim($parts[1]))
            ];
        } else {
            return $this->parsePhpStructure($item);
        }
    }
    
    protected function parseFunctionParameters($paramsString)
    {
        if (empty(trim($paramsString))) {
            return [];
        }
        
        $params = [];
        $current = '';
        $parenCount = 0;
        $bracketCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($paramsString); $i++) {
            $char = $paramsString[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                if ($i > 0 && $paramsString[$i - 1] === '\\') {
                    $current .= $char;
                    continue;
                }
                $inQuotes = false;
                $quoteChar = '';
            } elseif (!$inQuotes) {
                if ($char === '(') {
                    $parenCount++;
                } elseif ($char === ')') {
                    $parenCount--;
                } elseif ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;
                } elseif ($char === ',' && $parenCount === 0 && $bracketCount === 0) {
                    $params[] = $this->parsePhpStructure(trim($current));
                    $current = '';
                    continue;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $params[] = $this->parsePhpStructure(trim($current));
        }
        
        return $params;
    }
    
    protected function splitTernaryOperator($expression)
    {
        $parts = [];
        $current = '';
        $parenCount = 0;
        $bracketCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        $foundQuestion = false;
        $foundColon = false;
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                if ($i > 0 && $expression[$i - 1] === '\\') {
                    $current .= $char;
                    continue;
                }
                $inQuotes = false;
                $quoteChar = '';
            } elseif (!$inQuotes) {
                if ($char === '(') {
                    $parenCount++;
                } elseif ($char === ')') {
                    $parenCount--;
                } elseif ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;
                } elseif ($char === '?' && $parenCount === 0 && $bracketCount === 0 && !$foundQuestion) {
                    $parts[] = trim($current);
                    $current = '';
                    $foundQuestion = true;
                    continue;
                } elseif ($char === ':' && $parenCount === 0 && $bracketCount === 0 && $foundQuestion && !$foundColon) {
                    $parts[] = trim($current);
                    $current = '';
                    $foundColon = true;
                    continue;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }
        
        // Ensure we have exactly 3 parts
        if (count($parts) !== 3) {
            // Fallback: try to find the first ? and first : after that
            $questionPos = strpos($expression, '?');
            if ($questionPos !== false) {
                $colonPos = strpos($expression, ':', $questionPos);
                if ($colonPos !== false) {
                    $parts = [
                        trim(substr($expression, 0, $questionPos)),
                        trim(substr($expression, $questionPos + 1, $colonPos - $questionPos - 1)),
                        trim(substr($expression, $colonPos + 1))
                    ];
                }
            }
        }
        
        return $parts;
    }
    
    protected function splitStringConcatenation($expression)
    {
        $parts = [];
        $current = '';
        $parenCount = 0;
        $bracketCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                if ($i > 0 && $expression[$i - 1] === '\\') {
                    $current .= $char;
                    continue;
                }
                $inQuotes = false;
                $quoteChar = '';
            } elseif (!$inQuotes) {
                if ($char === '(') {
                    $parenCount++;
                } elseif ($char === ')') {
                    $parenCount--;
                } elseif ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;
                } elseif ($char === '.' && $parenCount === 0 && $bracketCount === 0) {
                    $parts[] = trim($current);
                    $current = '';
                    continue;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }
        
        return $parts;
    }
}
