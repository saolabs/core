<?php

namespace Saola\Core\View\Compilers;

/**
 * Vars Directive Service
 * Chuyên xử lý logic phức tạp cho @vars directive
 */
class VarsDirectiveService
{
    protected $phpParser;
    
    public function __construct(SimplePhpStructureParserService $phpParser)
    {
        $this->phpParser = $phpParser;
    }
    
    /**
     * Process @vars directive - khai báo và kiểm tra biến với JSON output
     */
    public function processVarsDirective($expression)
    {
        // Parse expression để lấy danh sách các biến
        $vars = [];
        $jsonData = [];
        $parts = $this->parseVarsExpression($expression);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') !== false) {
                // Có giá trị mặc định
                $equalPos = $this->findEqualPosition($part);
                $var = trim(substr($part, 0, $equalPos));
                $default = trim(substr($part, $equalPos + 1));
                
                // Parse default value để xử lý complex structures
                $parsedDefault = $this->phpParser->parsePhpStructure($default);
                $defaultCode = $this->phpParser->generatePhpCode($parsedDefault);
                
                $vars[] = "if (!isset(" . $var . ") || empty(" . $var . ")) " . $var . " = $defaultCode;";
                
                // Thêm vào JSON data (loại bỏ dấu $)
                $varName = ltrim($var, '$');
                $jsonData[] = "'{$varName}' => " . $var;
            } else {
                // Không có giá trị mặc định, gán null
                $var = trim($part);
                $vars[] = "if (!isset(" . $var . ")) " . $var . " = null;";
                
                // Thêm vào JSON data (loại bỏ dấu $)
                $varName = ltrim($var, '$');
                $jsonData[] = "'{$varName}' => " . $var;
            }
        }
        
        $phpCode = "<?php " . implode(' ', $vars) . "?>";
        
        // Chỉ tạo script tag khi có biến được khai báo
        if (!empty($jsonData)) {
            $phpCode .= "<?php \$__helper->addViewData(\$__VIEW_PATH__, \$__VIEW_ID__, [" . implode(', ', $jsonData) . "]); ?>";
        }
        
        return $phpCode;
    }
    
    /**
     * Parse expression phức tạp có dấu ngoặc đơn
     */
    protected function parseVarsExpression($expression)
    {
        return $this->splitByComma($expression);
    }
    
    /**
     * Split expression by comma, respecting nested parentheses, square brackets and quotes
     * (Copied from EventDirectiveService for consistency)
     */
    protected function splitByComma($expression)
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
                // Check if this is an escaped quote
                if ($i > 0 && $expression[$i - 1] === '\\') {
                    // This is an escaped quote, keep it in the string
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
    
    /**
     * Tìm vị trí dấu = đầu tiên không nằm trong dấu ngoặc đơn
     */
    protected function findEqualPosition($part)
    {
        $parenCount = 0;
        $bracketCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($part); $i++) {
            $char = $part[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                // Check if this is an escaped quote
                if ($i > 0 && $part[$i - 1] === '\\') {
                    // This is an escaped quote, keep it in the string
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
                } elseif ($char === '=' && $parenCount === 0 && $bracketCount === 0) {
                    return $i;
                }
            }
        }
        
        return false;
    }
}
