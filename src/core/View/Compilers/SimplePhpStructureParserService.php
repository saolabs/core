<?php

namespace Saola\Core\View\Compilers;

/**
 * Simple PHP Structure Parser Service
 * Version đơn giản để tránh vòng lặp vô hạn
 */
class SimplePhpStructureParserService
{
    /**
     * Parse PHP structure từ expression - version đơn giản
     */
    public function parsePhpStructure($expression)
    {
        $expression = trim($expression);
        
        // Nếu là array syntax: [key => value, ...]
        if (preg_match('/^\[.*\]$/', $expression)) {
            return $this->parseSimpleArray($expression);
        }
        
        // Nếu là string literal
        if (preg_match('/^[\'"].*[\'"]$/', $expression)) {
            return ['type' => 'string', 'value' => $expression];
        }
        
        // Nếu là number
        if (is_numeric($expression)) {
            return ['type' => 'number', 'value' => $expression];
        }
        
        // Nếu là boolean
        if (in_array(strtolower($expression), ['true', 'false'])) {
            return ['type' => 'boolean', 'value' => $expression];
        }
        
        // Nếu là null
        if (strtolower($expression) === 'null') {
            return ['type' => 'null', 'value' => 'null'];
        }
        
        // Mặc định là variable
        return ['type' => 'variable', 'value' => $expression];
    }
    
    /**
     * Parse array đơn giản
     */
    protected function parseSimpleArray($expression)
    {
        $content = trim($expression, '[]');
        if (empty($content)) {
            return ['type' => 'array', 'value' => '[]'];
        }
        
        // Tách các item bằng dấu phẩy, xử lý nested brackets
        $items = $this->splitArrayItems($content);
        $parsedItems = [];
        
        foreach ($items as $item) {
            $item = trim($item);
            if (strpos($item, '=>') !== false) {
                $parts = explode('=>', $item, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $parsedItems[] = [
                    'key' => $this->parsePhpStructure($key),
                    'value' => $this->parsePhpStructure($value)
                ];
            } else {
                $parsedItems[] = $this->parsePhpStructure($item);
            }
        }
        
        return ['type' => 'array', 'value' => $parsedItems];
    }
    
    /**
     * Split array items, xử lý nested brackets
     */
    protected function splitArrayItems($content)
    {
        $items = [];
        $current = '';
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
                if ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;
                } elseif ($char === ',' && $bracketCount === 0) {
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
                
            case 'string':
            case 'number':
            case 'boolean':
            case 'null':
            case 'variable':
            default:
                return $structure['value'];
        }
    }
}
