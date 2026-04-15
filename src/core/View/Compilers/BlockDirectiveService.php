<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class BlockDirectiveService
{
    /**
     * Validate that @block directives have matching @endblock/@endBlock directives
     * 
     * @param string $bladeCode
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function validateBlockDirectives(string $bladeCode): bool
    {
        // Remove @verbatim blocks to avoid checking directives inside them
        // Handle nested verbatim blocks properly
        $verbatimStack = 0;
        $result = '';
        $lines = explode("\n", $bladeCode);
        $inVerbatim = false;
        
        foreach ($lines as $line) {
            // Check for @verbatim (case insensitive)
            if (preg_match('/@verbatim\b/i', $line)) {
                $verbatimStack++;
                $inVerbatim = true;
                continue; // Skip this line
            }
            
            // Check for @endverbatim (case insensitive)
            if (preg_match('/@endverbatim\b/i', $line)) {
                $verbatimStack--;
                if ($verbatimStack <= 0) {
                    $verbatimStack = 0;
                    $inVerbatim = false;
                }
                continue; // Skip this line
            }
            
            // Only process lines outside verbatim blocks
            if (!$inVerbatim) {
                $result .= $line . "\n";
            }
        }
        
        // Now validate blocks in the filtered code
        $lines = explode("\n", $result);
        $stack = [];
        
        foreach ($lines as $lineNum => $line) {
            $lineNum++; // 1-based line numbers
            
            // Check for @block
            if (preg_match('/@block\s*\(/i', $line)) {
                // Extract block name if possible
                if (preg_match('/@block\s*\(\s*[\'"]([^\'"]*)[\'"]/i', $line, $matches)) {
                    $blockName = $matches[1];
                } else {
                    $blockName = 'block_' . count($stack);
                }
                $stack[] = ['name' => $blockName, 'line' => $lineNum];
            }
            
            // Check for @endblock/@endBlock
            if (preg_match('/@endblock\b|@endBlock\b/i', $line)) {
                if (empty($stack)) {
                    throw new \InvalidArgumentException(
                        "Lỗi tại dòng {$lineNum}: Tìm thấy @endblock/@endBlock nhưng không có @block tương ứng. " .
                        "Đảm bảo mỗi @block có @endblock hoặc @endBlock tương ứng."
                    );
                }
                array_pop($stack);
            }
        }
        
        // Check if there are unclosed blocks
        if (!empty($stack)) {
            $unclosedBlocks = array_map(function($block) {
                return "'{$block['name']}' (dòng {$block['line']})";
            }, $stack);
            
            throw new \InvalidArgumentException(
                "Lỗi: Có " . count($stack) . " block chưa được đóng: " . implode(', ', $unclosedBlocks) . ". " .
                "Đảm bảo mỗi @block có @endblock hoặc @endBlock tương ứng."
            );
        }
        
        return true;
    }

    public function registerDirectives(): void
    {
        // Directive @block - open block
        Blade::directive('block', function ($expression) {
            return $this->openBlock($expression);
        });
        Blade::directive('Block', function ($expression) {
            return $this->openBlock($expression);
        });

        // Directive @endBlock - close block
        Blade::directive('endBlock', function ($expression) {
            return $this->closeBlock($expression);
        });
        Blade::directive('EndBlock', function ($expression) {
            return $this->closeBlock($expression);
        });
        Blade::directive('endblock', function ($expression) {
            return $this->closeBlock($expression);
        });

        Blade::directive('useBlock', function ($expression) {
            return $this->useBlock($expression);
        });
        Blade::directive('UseBlock', function ($expression) {
            return $this->useBlock($expression);
        });
        Blade::directive('useblock', function ($expression) {
            return $this->useBlock($expression);
        });

        Blade::directive('mount', function ($expression) {
            return $this->useBlock($expression);
        });
        Blade::directive('Mount', function ($expression) {
            return $this->useBlock($expression);
        });
        Blade::directive('mountBlock', function ($expression) {
            return $this->useBlock($expression);
        });
        Blade::directive('mountblock', function ($expression) {
            return $this->useBlock($expression);
        });

        // Directive @onBlock - onBlock attributes với prefix "block:"
        Blade::directive('onBlock', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });
        Blade::directive('onblock', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });
        Blade::directive('blockOn', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });
        Blade::directive('blockon', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });
        Blade::directive('blockListen', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });
        Blade::directive('blocklisten', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });
        Blade::directive('blockWatch', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });
        Blade::directive('blockwatch', function ($expression) {
            return $this->processOnBlockDirective($expression);
        });

        // Directive @onBlockChange - onBlockChange attributes với prefix "block:"
        Blade::directive('onBlockChange', function ($expression) {
            return $this->processOnBlockChangeDirective($expression);
        });
        Blade::directive('onblockchange', function ($expression) {
            return $this->processOnBlockChangeDirective($expression);
        });
        Blade::directive('blockChangeOn', function ($expression) {
            return $this->processOnBlockChangeDirective($expression);
        });
        Blade::directive('blockchangeon', function ($expression) {
            return $this->processOnBlockChangeDirective($expression);
        });

        // Directive @blockChange - blockChange attributes với prefix "block:"
        Blade::directive('blockChange', function ($expression) {
            return $this->processBlockChangeDirective($expression);
        });
        Blade::directive('blockchange', function ($expression) {
            return $this->processBlockChangeDirective($expression);
        });
    }

    public function openBlock($expression)
    {
        // Parse expression để lấy name và attributes
        // Format: 'blockName' hoặc 'blockName', ['attr1' => 'value1', 'attr2' => 'value2']
        
        // Remove outer parentheses nếu có
        $expression = trim($expression, '()');
        
        // Parse để tách name và attributes
        $parts = $this->parseBlockExpression($expression);
        $blockName = $parts['name'];
        $isVariable = $parts['isVariable'];
        $attributes = $parts['attributes'];
        
        // Tạo attributes string cho HTML comment
        $attributesStr = $this->formatAttributesForComment($attributes);
        
        // Generate code theo format yêu cầu với dynamic blockName
        if ($isVariable) {
            // Nếu là variable (bắt đầu với $), sử dụng dynamic blockName
            $code = "<?php \$__BlockID__ = \$__VIEW_ID__ . '-b-' . {$blockName}; \$__env->startSection('block-'.{$blockName}); echo \$__helper->startMarker('block', \$__BlockID__, ['name' => {$blockName}, 'viewId' => \$__BlockID__, 'attributes' => {$attributes}]); ?>";
        } else {
            // Nếu là string literal, sử dụng static blockName
            $code = "<?php \$__BlockID__ = \$__VIEW_ID__ . '-b-{$blockName}'; \$__env->startSection('block-{$blockName}'); echo \$__helper->startMarker('block', \$__BlockID__, ['name' => '{$blockName}', 'viewId' => \$__BlockID__, 'attributes' => {$attributes}]); ?>";
        }
        
        return $code;
    }

    public function closeBlock($expression)
    {
        // Validate that there's an open section before closing
        // Use try-catch to provide a clearer error message
        return "<?php echo \$__helper->endMarker('block', \$__BlockID__??\$__VIEW_ID__); 
            try {
                \$__env->stopSection();
            } catch (\InvalidArgumentException \$e) {
                throw new \InvalidArgumentException('Lỗi: Tìm thấy @endblock/@endBlock nhưng không có @block tương ứng. Đảm bảo mỗi @block có @endblock hoặc @endBlock tương ứng.', 0, \$e);
            }
        ?>";
    }

    public function useBlock($expression)
    {
        // Parse expression để lấy blockName và default value
        // Format: $blockName hoặc $blockName, $default
        $expression = trim($expression, '()');
        
        // Tách blockName và default value
        $parts = $this->parseUseBlockExpression($expression);
        $blockName = $parts['blockName'];
        $default = $parts['default'];
        $key = trim($blockName, '\'" ');
        $output = "<?php echo \$__helper->endMarker('block', \$__VIEW_ID__ . '-b-' . {$blockName}); ?>";
        if ($default !== null) {
            $output .= "<?php echo \$__env->yieldContent('block-' . {$blockName}, {$default}); ?>";
        } else {
            $output .= "<?php echo \$__env->yieldContent('block-' . {$blockName}); ?>";
        }
        $output .= "<?php \$__helper->endMarker('block', \$__VIEW_ID__ . '-b-' . {$blockName}); ?>";
        return $output;
    }

    private function parseUseBlockExpression($expression)
    {
        // Parse expression để tách blockName và default value
        // Format: $blockName hoặc $blockName, $default
        
        // Tìm dấu phẩy đầu tiên ngoài quotes
        $inQuote = false;
        $quoteChar = null;
        $commaPos = false;
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
            }
            
            if (!$inQuote && $char === ',') {
                $commaPos = $i;
                break;
            }
        }
        
        if ($commaPos === false) {
            // Chỉ có blockName, không có default
            $blockName = trim($expression);
            return ['blockName' => $blockName, 'default' => null];
        }
        
        // Có cả blockName và default
        $blockName = trim(substr($expression, 0, $commaPos));
        $default = trim(substr($expression, $commaPos + 1));
        
        return ['blockName' => $blockName, 'default' => $default];
    }

    private function parseBlockExpression($expression)
    {
        // Tách name và attributes array
        // Pattern: 'name' hoặc 'name', [...]
        
        // Tìm dấu phẩy đầu tiên ngoài quotes
        $inQuote = false;
        $quoteChar = null;
        $commaPos = false;
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            if (($char === '"' || $char === "'") && ($i === 0 || $expression[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
            }
            
            if (!$inQuote && $char === ',') {
                $commaPos = $i;
                break;
            }
        }
        
        if ($commaPos === false) {
            // Chỉ có name, không có attributes
            $name = trim($expression, '\'" ');
            $isVariable = str_starts_with($name, '$');
            return ['name' => $name, 'isVariable' => $isVariable, 'attributes' => []];
        }
        
        // Có cả name và attributes
        $namePart = substr($expression, 0, $commaPos);
        $attributesPart = substr($expression, $commaPos + 1);
        
        $name = trim($namePart, '\'" ');
        $isVariable = str_starts_with($name, '$');
        $attributes = $this->parseAttributesArray($attributesPart);
        
        return ['name' => $name, 'isVariable' => $isVariable, 'attributes' => $attributes];
    }
    
    private function parseAttributesArray($attributesStr)
    {
        // Parse ['attr1' => 'value1', 'attr2' => 'value2'] thành array
        $attributesStr = trim($attributesStr);
        
        // Remove brackets
        $attributesStr = trim($attributesStr, '[]');
        
        if (empty($attributesStr)) {
            return [];
        }
        
        // Parse key-value pairs
        $attributes = [];
        $current = '';
        $inQuote = false;
        $quoteChar = null;
        $key = '';
        $value = '';
        $expectingValue = false;
        
        for ($i = 0; $i < strlen($attributesStr); $i++) {
            $char = $attributesStr[$i];
            
            if (($char === '"' || $char === "'") && ($i === 0 || $attributesStr[$i-1] !== '\\')) {
                if (!$inQuote) {
                    $inQuote = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }
                $current .= $char;
            } elseif (!$inQuote && $char === '=' && $i + 1 < strlen($attributesStr) && $attributesStr[$i + 1] === '>') {
                // Tìm thấy dấu =>, đây là key
                $key = trim($current, '\'" ');
                $current = '';
                $expectingValue = true;
                $i++; // Skip dấu >
            } elseif (!$inQuote && $char === ',' && $expectingValue) {
                // Kết thúc value
                $value = trim($current, '\'" ');
                $attributes[$key] = $value;
                $key = '';
                $value = '';
                $current = '';
                $expectingValue = false;
            } else {
                $current .= $char;
            }
        }
        
        // Xử lý attribute cuối cùng
        if ($expectingValue && !empty($current)) {
            $value = trim($current, '\'" ');
            $attributes[$key] = $value;
        }
        
        return $attributes;
    }
    
    private function formatAttributesForComment($attributes)
    {
        if (empty($attributes)) {
            return '';
        }
        
        $attrStrings = [];
        foreach ($attributes as $key => $value) {
            $attrStrings[] = " {$key}=\"{$value}\"";
        }
        
        return implode('', $attrStrings);
    }

    /**
     * Process @onBlock directive - tạo HTML attributes để theo dõi block changes với prefix "block:"
     * Format: @onBlock($attr, $blockName, $default) hoặc @onBlock([array])
     */
    public function processOnBlockDirective($expression)
    {
        $expression = trim($expression, '()');
        
        // Kiểm tra nếu là array syntax
        if ($this->isArraySyntax($expression)) {
            // Xử lý array syntax với prefix "block:"
            return $this->processArraySyntax($expression);
        } else {
            // Xử lý simple syntax
            return $this->processSimpleSyntax($expression);
        }
    }

    /**
     * Xử lý array syntax: @onBlock(['attr' => 'yieldKey', ...])
     */
    private function processArraySyntax($expression)
    {
        // Sử dụng registerOnBlock thay vì registerOnYield
        return "<?php echo \$__helper->registerOnBlock(\$__env, {$expression}); ?>";
    }

    /**
     * Xử lý simple syntax: @onBlock($attr, $blockName, $default)
     */
    private function processSimpleSyntax($expression)
    {
        $parts = $this->parseOnBlockExpression($expression);
        $attr = $parts['attr'];
        $blockName = $parts['blockName'];
        $default = $parts['default'];
        
        // Tạo expression cho registerOnBlock (không cần prefix "block:" vì hàm sẽ tự thêm)
        $onBlockExpression = $attr;
        if ($blockName !== null) {
            $onBlockExpression .= ", {$blockName}";
        }
        if ($default !== null) {
            $onBlockExpression .= ", {$default}";
        }
        
        return "<?php echo \$__helper->registerOnBlock(\$__env, {$onBlockExpression}); ?>";
    }

    /**
     * Kiểm tra nếu expression là array syntax
     */
    private function isArraySyntax($expression)
    {
        $trimmed = trim($expression);
        return str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']');
    }


    /**
     * Process @onBlockChange directive - tạo static attributes để theo dõi block changes với prefix "block:"
     */
    public function processOnBlockChangeDirective($expression)
    {
        // Tương tự như @onBlock
        return $this->processOnBlockDirective($expression);
    }

    /**
     * Process @blockChange directive - tạo static attributes để theo dõi block changes với prefix "block:"
     */
    public function processBlockChangeDirective($expression)
    {
        // Tương tự như @onBlock
        return $this->processOnBlockDirective($expression);
    }

    /**
     * Parse expression cho @onBlock directive
     * Format: $attr, $blockName, $default (giống @onyield)
     */
    private function parseOnBlockExpression($expression)
    {
        // Parse expression theo format của @onyield: attr, yieldKey, default
        $params = $this->parseYieldAttrParams($expression);
        
        $attr = $params[0] ?? null;
        $blockName = $params[1] ?? null;
        $default = $params[2] ?? null;
        
        return ['attr' => $attr, 'blockName' => $blockName, 'default' => $default];
    }

    /**
     * Parse yield attribute parameters (copy từ CommonDirectiveService)
     */
    private function parseYieldAttrParams($expression)
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
}
