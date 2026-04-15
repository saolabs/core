<?php

namespace Saola\Core\View\Compilers;

use Exception;

class BladeToSpaCompiler
{


    /**
     * Compile Blade template to SPA JavaScript
     */
    public function compile(string $bladeContent): string
    {
        // Use sequential regex processing for complex nested structures
        $content = $this->processSequentially($bladeContent);
        $content = $this->convertPhpToJs($content);
        $content = $this->finalCleanup($content);
        return $content;
    }

    /**
     * Process directives sequentially to handle nested structures
     */
        private function processSequentially(string $content): string
    {
        // Process recursively until no more changes
        $previousContent = '';
        $maxIterations = 20;
        $iteration = 0;

        while ($content !== $previousContent && $iteration < $maxIterations) {
            $previousContent = $content;
            $iteration++;

            // Process @foreach blocks first (most complex) - handle nested ones
            $content = $this->processForeachBlocksNested($content);

            // Process @if blocks
            $content = $this->processIfBlocks($content);

            // Process @for blocks
            $content = $this->processForBlocks($content);

            // Process @while blocks
            $content = $this->processWhileBlocks($content);

            // Process @switch blocks
            $content = $this->processSwitchBlocks($content);

            // Process remaining directives
            $content = $this->processRemainingDirectives($content);
            
            // Process @foreach again to handle any remaining ones
            $content = $this->processForeachBlocksNested($content);
            
            // Process @foreach one more time to handle deeply nested ones
            $content = $this->processForeachBlocksNested($content);
            
            // Process @foreach one final time to handle any remaining ones
            $content = $this->processForeachBlocksNested($content);
            
            // Process @foreach one more time to handle any remaining ones
            $content = $this->processForeachBlocksNested($content);
            
            // Process @foreach one more time to handle any remaining ones
            $content = $this->processForeachBlocksNested($content);
            
            // Process @foreach one more time to handle any remaining ones
            $content = $this->processForeachBlocksNested($content);
            
            // Process @foreach one more time to handle any remaining ones
            $content = $this->processForeachBlocksNested($content);
        }

        return $content;
    }

    /**
     * Process @foreach blocks with nested content (improved version)
     */
    private function processForeachBlocksNested(string $content): string
    {
        // Handle @foreach($array as $key => $value) pattern with nested content
        $content = preg_replace_callback('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s', function($matches) {
            $array = trim($matches[1]);
            $key = trim($matches[2]);
            $value = trim($matches[3]);
            $innerContent = trim($matches[4]);
            
            // Convert PHP array syntax to JavaScript object
            $array = $this->convertPhpArrayToJs($array);
            
            return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
        }, $content);
        
        // Handle @foreach($array as $value) pattern with nested content
        $content = preg_replace_callback('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s', function($matches) {
            $array = trim($matches[1]);
            $value = trim($matches[2]);
            $innerContent = trim($matches[3]);
            
            // Convert PHP array syntax to JavaScript object
            $array = $this->convertPhpArrayToJs($array);
            
            return "\${SPA.foreach({$array}, ({$value}) => `{$innerContent}`)}";
        }, $content);
        
        return $content;
    }

    /**
     * Process @foreach blocks with nested content (legacy method)
     */
    private function processForeachBlocks(string $content): string
    {
        return $this->processForeachBlocksNested($content);
    }

    /**
     * Process @if blocks with nested content
     */
    private function processIfBlocks(string $content): string
    {
        $content = preg_replace_callback('/@if\s*\(\s*([^)]+)\s*\)(.*?)@endif/s', function($matches) {
            $condition = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            // Handle @elseif and @else within the content
            $innerContent = preg_replace('/@elseif\s*\(\s*([^)]+)\s*\)/', '`; } else if($1){ return `', $innerContent);
            $innerContent = preg_replace('/@else/', '`; } else { return `', $innerContent);
            
            return "\${SPA.execute(() => { 
            if({$condition}){ return `{$innerContent}`; } 
            return '';
        })}";
        }, $content);
        
        return $content;
    }

    /**
     * Process @for blocks with nested content
     */
    private function processForBlocks(string $content): string
    {
        $content = preg_replace_callback('/@for\s*\(\s*([^)]+)\s*\)(.*?)@endfor/s', function($matches) {
            $condition = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            return "\${SPA.execute(() => { let __outputString = ``; for({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
        }, $content);
        
        return $content;
    }

    /**
     * Process @while blocks with nested content
     */
    private function processWhileBlocks(string $content): string
    {
        $content = preg_replace_callback('/@while\s*\(\s*([^)]+)\s*\)(.*?)@endwhile/s', function($matches) {
            $condition = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            return "\${SPA.execute(() => { let __outputString = ``; while({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
        }, $content);
        
        return $content;
    }

    /**
     * Process @switch blocks with nested content
     */
    private function processSwitchBlocks(string $content): string
    {
        $content = preg_replace_callback('/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s', function($matches) {
            $value = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            // Handle @case and @break within the content
            $innerContent = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $innerContent);
            $innerContent = preg_replace('/@break/', '`; break', $innerContent);
            
            return "\${SPA.execute(() => { switch({$value}){ {$innerContent} } return ''; })}";
        }, $content);
        
        return $content;
    }

    /**
     * Process remaining directives
     */
    private function processRemainingDirectives(string $content): string
    {
        // Process @case directives
        $content = preg_replace('/@case\s*\(\s*([^)]+)\s*\)/', 'case $1: return `', $content);
        
        // Process @break directives
        $content = preg_replace('/@break/', '`; break', $content);
        
        // Process @default directives
        $content = preg_replace('/@default/', 'default: return `', $content);
        
        // Process @elseif directives
        $content = preg_replace('/@elseif\s*\(\s*([^)]+)\s*\)/', '`; } else if($1){ return `', $content);
        
        // Process @else directives
        $content = preg_replace('/@else/', '`; } else { return `', $content);
        
        // Process @php ... @endphp blocks
        $content = preg_replace_callback('/@php\s*(.*?)@endphp/s', function($matches) {
            $phpCode = trim($matches[1]);
            return "\${SPA.execute(() => { {$phpCode}; return ''; })}";
        }, $content);
        
        // Process @php directives with parameters (fallback)
        $content = preg_replace('/@php\s*\(([^)]*)\)/', '$1', $content);
        
        // Process @php without parameters (fallback)
        $content = preg_replace('/@php/', '', $content);
        
        // Remove Blade comments {{-- ... --}}
        $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content);
        
        // Convert remaining {{}} patterns to JavaScript variables
        $content = preg_replace('/\{\{([^}]+)\}\}/', '${$1}', $content);
        
        // Process remaining @foreach directives that might be missed
        $content = preg_replace('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)/', '${SPA.foreach($1, ($2) => `', $content);
        $content = preg_replace('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)/', '${SPA.foreach($1, ($3, $2) => `', $content);
        $content = str_replace('@endforeach', '`)}', $content);
        
        // Convert remaining PHP array syntax to JavaScript object
        $content = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\]/', '{$1: $2}', $content);
        
        // Process @vars directive (should be handled in main command, but just in case)
        $content = preg_replace('/@vars\s*\(([^)]+)\)/', '', $content);
        
        // Process remaining @if directives
        $content = preg_replace('/@if\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { let __outputString = ``; if($1){ __outputString += `', $content);
        $content = str_replace('@endif', '`; } return __outputString; })}', $content);
        
        // Process remaining @for directives
        $content = preg_replace('/@for\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { let __outputString = ``; for($1){ __outputString += `', $content);
        $content = str_replace('@endfor', '`; } return __outputString; })}', $content);
        
        // Process remaining @while directives
        $content = preg_replace('/@while\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { let __outputString = ``; while($1){ __outputString += `', $content);
        $content = str_replace('@endwhile', '`; } return __outputString; })}', $content);
        
        // Process remaining @switch directives
        $content = preg_replace('/@switch\s*\(\s*([^)]+)\s*\)/', '${SPA.execute(() => { let __outputString = ``; switch($1){', $content);
        $content = str_replace('@endswitch', '} return __outputString; })}', $content);
        
        return $content;
    }

    /**
     * Parse with stack to handle nested directives correctly
     */
    private function parseWithStack(string $content): string
    {
        $result = '';
        $stack = [];
        $i = 0;
        $length = strlen($content);
        
        while ($i < $length) {
            if ($content[$i] == '@' && !$this->isInString($content, $i)) {
                $directiveInfo = $this->parseDirectiveAtPosition($content, $i);
                $directive = $directiveInfo['directive'];
                $params = $directiveInfo['params'];
                $output = $directiveInfo['output'];
                
                // Handle opening directives
                if (in_array($directive, ['foreach', 'if', 'for', 'while', 'switch'])) {
                    $stack[] = [
                        'type' => $directive,
                        'params' => $params,
                        'startPos' => strlen($result)
                    ];
                    $result .= $output;
                }
                // Handle closing directives
                elseif (in_array($directive, ['endforeach', 'endif', 'endfor', 'endwhile', 'endswitch'])) {
                    if (!empty($stack)) {
                        $lastOpen = array_pop($stack);
                        $result .= $this->getClosingOutput($lastOpen['type']);
                    }
                }
                // Handle other directives
                else {
                    $result .= $output;
                }
                
                $i = $directiveInfo['nextPosition'];
            } else {
                $result .= $content[$i];
                $i++;
            }
        }
        
        return $result;
    }

    /**
     * Get closing output for directive type
     */
    private function getClosingOutput(string $type): string
    {
        switch ($type) {
            case 'foreach':
                return '`)}';
            case 'if':
            case 'for':
            case 'while':
                return '`; } return __outputString; })}';
            case 'switch':
                return '} return __outputString; })}';
            default:
                return '';
        }
    }



    /**
     * Smart directive parser - tìm vị trí @directive và phân tích từng ký tự
     * để xử lý cú pháp lồng nhau chính xác
     */
    private function parseDirectivesSmart(string $content): string
    {
        $result = '';
        $i = 0;
        $length = strlen($content);
        
        while ($i < $length) {
            // Tìm vị trí xuất hiện của @directive
            if ($content[$i] == '@' && !$this->isInString($content, $i)) {
                $directiveInfo = $this->parseDirectiveAtPosition($content, $i);
                $result .= $directiveInfo['output'];
                $i = $directiveInfo['nextPosition'];
            } else {
                $result .= $content[$i];
                $i++;
            }
        }
        
        return $result;
    }

    /**
     * Kiểm tra xem có đang trong string không
     */
    private function isInString(string $content, int $position): bool
    {
        $inString = false;
        $strChar = '';
        
        for ($i = 0; $i < $position; $i++) {
            $c = $content[$i];
            if (($c == '"' || $c == "'") && ($i == 0 || $content[$i-1] != '\\')) {
                if ($inString && $c == $strChar) {
                    $inString = false;
                    $strChar = '';
                } elseif (!$inString) {
                    $inString = true;
                    $strChar = $c;
                }
            }
        }
        
        return $inString;
    }

    /**
     * Parse directive tại vị trí cụ thể
     */
    private function parseDirectiveAtPosition(string $content, int $startPos): array
    {
        $i = $startPos + 1; // Bỏ qua @
        $directiveName = '';
        $params = '';
        $nestingLevel = 0;
        $inString = false;
        $strChar = '';
        
        // Đọc tên directive
        while ($i < strlen($content) && preg_match('/[a-zA-Z0-9_]/', $content[$i])) {
            $directiveName .= $content[$i];
            $i++;
        }
        
        // Bỏ qua khoảng trắng
        while ($i < strlen($content) && ctype_space($content[$i])) {
            $i++;
        }
        
        // Kiểm tra có tham số không
        if ($i < strlen($content) && $content[$i] == '(') {
            $i++; // Bỏ qua (
            $nestingLevel = 1;
            
            // Phân tích từng ký tự cho đến khi đóng ) ở cùng level
            while ($i < strlen($content) && $nestingLevel > 0) {
                $c = $content[$i];
                
                // Xử lý string literals
                if (($c == '"' || $c == "'") && ($i == 0 || $content[$i-1] != '\\')) {
                    if ($inString && $c == $strChar) {
                        $inString = false;
                        $strChar = '';
                    } elseif (!$inString) {
                        $inString = true;
                        $strChar = $c;
                    }
                    $params .= $c;
                }
                // Xử lý dấu ngoặc
                elseif (!$inString) {
                    if ($c == '(') {
                        $nestingLevel++;
                    } elseif ($c == ')') {
                        $nestingLevel--;
                        if ($nestingLevel > 0) {
                            $params .= $c;
                        }
                    } else {
                        $params .= $c;
                    }
                } else {
                    $params .= $c;
                }
                
                $i++;
            }
        }
        
        // Xử lý directive
        $output = $this->processDirective($directiveName, trim($params));
        
        return [
            'directive' => $directiveName,
            'params' => trim($params),
            'output' => $output,
            'nextPosition' => $i
        ];
    }



    /**
     * Process individual directive with smart parameter parsing
     */
    private function processDirective(string $directive, string $params): string
    {
        $directive = trim($directive);
        $params = trim($params);
        
        // Parse parameters using Template.php-like mechanism
        $parsedParams = $this->parseParamsSmart($params);
        
        switch ($directive) {
            case 'foreach':
                return $this->processForeach($parsedParams);
            case 'if':
                return $this->processIf($parsedParams);
            case 'for':
                return $this->processFor($parsedParams);
            case 'while':
                return $this->processWhile($parsedParams);
            case 'switch':
                return $this->processSwitch($parsedParams);
            case 'case':
                return $this->processCase($parsedParams);
            case 'break':
                return $this->processBreak();
            case 'default':
                return $this->processDefault();
            case 'elseif':
                return $this->processElseif($parsedParams);
            case 'else':
                return $this->processElse();
            case 'endif':
                return $this->processEndif();
            case 'endfor':
                return $this->processEndfor();
            case 'endwhile':
                return $this->processEndwhile();
            case 'endswitch':
                return $this->processEndswitch();
            case 'endforeach':
                return $this->processEndforeach();
            case 'vars':
                return $this->processVars($parsedParams);
            case 'php':
                return $this->processPhp($params);
            case 'endphp':
                return $this->processEndphp();
            default:
                return "@{$directive}";
        }
    }

    /**
     * Smart parameter parser inspired by Template.php parseStrFnParams
     */
    private function parseParamsSmart(string $str): array
    {
        $containers = [
            [
                "type" => "string",
                "str" => "",
                "params" => ""
            ]
        ];
        $inFunction = 0;
        $inString = false;
        $strChar = '';
        $currentIndex = 0;
        $strArr = str_split($str);
        
        foreach ($strArr as $i => $c) {
            if ($c == '"' || $c == "'") {
                if ($c == $strChar) {
                    $inString = false;
                    $strChar = '';
                    if ($inFunction) {
                        $containers[$currentIndex]["params"] .= $c;
                    }
                } elseif (!$inString) {
                    $inString = true;
                    $strChar = $c;
                    if ($inFunction) {
                        $containers[$currentIndex]["params"] .= $c;
                    }
                } else {
                    if ($inFunction) {
                        $containers[$currentIndex]["params"] .= $c;
                    }
                }
            }
            elseif ($c == '(') {
                if (!$inFunction) {
                    $inFunction++;
                    $containers[$currentIndex]["type"] = 'function';
                } else {
                    if (!$inString) $inFunction++;
                    $containers[$currentIndex]["params"] .= $c;
                }
            }
            elseif ($c == ')') {
                if ($inFunction) {
                    if (!$inString) {
                        $inFunction--;
                        if ($inFunction == 0) {
                            // Function completed
                            $containers[$currentIndex]["args"] = $this->parseParamsSmart($containers[$currentIndex]["params"]);
                        } else {
                            $containers[$currentIndex]["params"] .= $c;
                        }
                    } else {
                        $containers[$currentIndex]["params"] .= $c;
                    }
                }
            }
            elseif ($inFunction) {
                $containers[$currentIndex]["params"] .= $c;
            }
            else {
                $containers[$currentIndex]["str"] .= $c;
            }
        }
        
        return $containers;
    }

    /**
     * Process @foreach directive with smart parsing
     */
    private function processForeach(array $params): string
    {
        if (count($params) >= 2) {
            $array = $params[0]['str'] ?? '';
            $item = $params[1]['str'] ?? '';
            
            // Handle @foreach($input as $key => $value) pattern
            if (strpos($item, '=>') !== false) {
                $parts = explode('=>', $item);
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                return "\${SPA.foreach({$array}, ({$value}, {$key}) => `";
            }
            
            // Convert PHP array syntax to JavaScript object
            $array = $this->convertPhpArrayToJs($array);
            
            return "\${SPA.foreach({$array}, ({$item}) => `";
        }
        return '@foreach';
    }

    /**
     * Process @if directive with smart parsing
     */
    private function processIf(array $params): string
    {
        if (count($params) >= 1) {
            $condition = $params[0]['str'] ?? '';
            return "\${SPA.execute(() => {
        let __outputString = ``;
        if({$condition}){
            __outputString += `";
        }
        return '@if';
    }

    /**
     * Process @for directive with smart parsing
     */
    private function processFor(array $params): string
    {
        if (count($params) >= 1) {
            $condition = $params[0]['str'] ?? '';
            return "\${SPA.execute(() => {
        let __outputString = ``;
        for({$condition}){
            __outputString += `";
        }
        return '@for';
    }

    /**
     * Process @while directive with smart parsing
     */
    private function processWhile(array $params): string
    {
        if (count($params) >= 1) {
            $condition = $params[0]['str'] ?? '';
            return "\${SPA.execute(() => {
        let __outputString = ``;
        while({$condition}){
            __outputString += `";
        }
        return '@while';
    }

    /**
     * Process @switch directive with smart parsing
     */
    private function processSwitch(array $params): string
    {
        if (count($params) >= 1) {
            $value = $params[0]['str'] ?? '';
            return "\${SPA.execute(() => {
        let __outputString = ``;
        switch({$value}){";
        }
        return '@switch';
    }

    /**
     * Process @case directive
     */
    private function processCase(array $params): string
    {
        if (count($params) >= 1) {
            $value = $params[0]['str'] ?? '';
            return "case {$value}: __outputString += `";
        }
        return '@case';
    }

    /**
     * Process @break directive
     */
    private function processBreak(): string
    {
        return '`; break;';
    }

    /**
     * Process @default directive
     */
    private function processDefault(): string
    {
        return 'default: __outputString += `';
    }

    /**
     * Process @elseif directive
     */
    private function processElseif(array $params): string
    {
        if (count($params) >= 1) {
            $condition = $params[0]['str'] ?? '';
            return '`;
        }
        else if(' . $condition . '){
            __outputString += `';
        }
        return '@elseif';
    }

    /**
     * Process @else directive
     */
    private function processElse(): string
    {
        return '`;
        }
        else{
            __outputString += `';
    }

    /**
     * Process @endif directive
     */
    private function processEndif(): string
    {
        return '`;
        }
        return __outputString;
    })}';
    }

    /**
     * Process @endfor directive
     */
    private function processEndfor(): string
    {
        return '`;
        }
        return __outputString;
    })}';
    }

    /**
     * Process @endwhile directive
     */
    private function processEndwhile(): string
    {
        return '`;
        }
        return __outputString;
    })}';
    }

    /**
     * Process @endswitch directive
     */
    private function processEndswitch(): string
    {
        return '
        }
        return __outputString;
    })}';
    }

    /**
     * Process @endforeach directive
     */
    private function processEndforeach(): string
    {
        return '`)}';
    }

    /**
     * Process @vars directive
     */
    private function processVars(array $params): string
    {
        // @vars directive will be handled separately in the main compilation
        return '';
    }

    /**
     * Process @php directive
     */
    private function processPhp(string $params): string
    {
        // @php directive - convert PHP code to JavaScript
        $phpCode = trim($params);
        if (empty($phpCode)) {
            return '';
        }
        
        // Convert PHP variable assignments to JavaScript
        $phpCode = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([^;]+);/', 'let $1 = $2;', $phpCode);
        
        // Convert PHP increment/decrement
        $phpCode = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)\+\+;/', '$1++;', $phpCode);
        $phpCode = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)--;/', '$1--;', $phpCode);
        
        return $phpCode;
    }

    /**
     * Process @endphp directive
     */
    private function processEndphp(): string
    {
        // @endphp directive - no special processing needed
        return '';
    }

    /**
     * Convert PHP array syntax to JavaScript object
     */
    private function convertPhpArrayToJs(string $array): string
    {
        // Convert simple variables like $users -> users
        $array = preg_replace('/^\$([a-zA-Z_][a-zA-Z0-9_]*)$/', '$1', $array);
        
        // Convert ['key' => value] to {key: value}
        $array = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*\]/', '{$1: $2}', $array);
        $array = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*,\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*([^,\]]+)\s*\]/', '{$1: $2, $3: $4}', $array);
        
        // Convert ['key' => $variable] to {key: variable}
        $array = preg_replace('/\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\]/', '{$1: $2}', $array);
        
        // Convert specific patterns like ['id' => $id]
        $array = str_replace("['id' => \$id]", "{id: id}", $array);
        $array = str_replace("['id' => id]", "{id: id}", $array);
        
        return $array;
    }

    /**
     * Convert PHP syntax to JavaScript
     */
    private function convertPhpToJs(string $content): string
    {
        // Convert PHP object operator (->) to JavaScript dot notation (.) - GENERAL PATTERN
        // Handle $var->attr -> var.attr
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1.$2', $content);
        
        // Handle $var->attr['key'] -> var.attr['key']
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]/', '$1.$2[$3]', $content);
        
        // Handle $var->attr['key']->prop -> var.attr['key'].prop
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1.$2[$3].$4', $content);
        
        // Handle $var->attr['key']->prop['key2'] -> var.attr['key'].prop['key2']
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]/', '$1.$2[$3].$4[$5]', $content);
        
        // Handle function calls with complex parameters like $helper->getPosts(['id' => $id])
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*\[\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=>\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\]\s*\)/', '$1.$2({$3: $4})', $content);
        
        // Handle $var->attr['key']->prop['key2']->prop2 -> var.attr['key'].prop['key2'].prop2
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1.$2[$3].$4[$5].$6', $content);
        
        // Handle function calls on objects: $var->method() -> var.method()
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', '$1.$2(', $content);
        
        // Handle function calls on array properties: $var->attr['key']->method() -> var.attr['key'].method()
        $content = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', '$1.$2[$3].$4(', $content);
        
        // Convert PHP variables {{$var}} to JavaScript ${var}
        $content = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', '${$1}', $content);
        $content = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', '${$1.$2}', $content);
        $content = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]\s*\}\}/', '${$1[$2]}', $content);
        $content = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\?\?\s*([^}]+)\s*\}\}/', '${$1 || $2}', $content);
        $content = preg_replace('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\?\s*([^}]+)\s*:\s*([^}]+)\s*\}\}/', '${$1 ? $2 : $3}', $content);
        $content = preg_replace('/\{\{\s*([^}]+)\s*\}\}/', '${$1}', $content);
        
        // Convert unescaped output {!! !!}
        $content = preg_replace('/\{!!\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*!!\}/', '${$1}', $content);
        $content = preg_replace('/\{!!\s*([^}]+)\s*!!\}/', '${$1}', $content);
        
        // Convert PHP function calls to SPA functions
        $content = preg_replace('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\)\s*\}\}/', '${SPA.$1($2)}', $content);
        $content = preg_replace('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*,\s*([^}]+)\s*\)\s*\}\}/', '${SPA.$1($2, $3)}', $content);
        $content = preg_replace('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^}]+)\s*\)\s*\}\}/', '${SPA.$1($2)}', $content);
        
        // Clean up extra spaces in template literals
        $content = preg_replace('/\$\{([^}]+)\s+\}/', '${$1}', $content);
        
        return $content;
    }

    /**
     * Final cleanup and fixes
     */
    private function finalCleanup(string $content): string
    {
        // Fix remaining PHP variables that might be missed
        $content = preg_replace('/(?<!\{)(?<!\$)\$([a-zA-Z_][a-zA-Z0-9_]*)(?!\})/', '{{$1}}', $content);
        
        // Fix for loop syntax - convert {{i}} to i in various contexts
        $content = preg_replace('/for\(\{\{([^}]+)\}\}/', 'for($1', $content);
        $content = preg_replace('/for\(([^;]+);\s*\{\{([^}]+)\}\}/', 'for($1; $2', $content);
        $content = preg_replace('/for\(([^;]+);\s*([^;]+);\s*\{\{([^}]+)\}\}/', 'for($1; $2; $3', $content);
        
        // Fix comparison operators with {{}} variables
        $content = preg_replace('/\{\{([^}]+)\}\}\s*</', '$1 < ', $content);
        $content = preg_replace('/\{\{([^}]+)\}\}\s*<=/', '$1 <= ', $content);
        $content = preg_replace('/\{\{([^}]+)\}\}\s*>/', '$1 > ', $content);
        $content = preg_replace('/\{\{([^}]+)\}\}\s*>=/', '$1 >= ', $content);
        $content = preg_replace('/\{\{([^}]+)\}\}\s*==/', '$1 == ', $content);
        $content = preg_replace('/\{\{([^}]+)\}\}\s*!=/', '$1 != ', $content);
        
        // Fix increment/decrement operators
        $content = preg_replace('/\{\{\s*([^}]+)\s*\}\}\s*\+=/', '$1 += ', $content);
        $content = preg_replace('/\{\{\s*([^}]+)\s*\}\}\s*\+\+/', '$1++', $content);
        $content = preg_replace('/\{\{\s*([^}]+)\s*\}\}\s*--/', '$1--', $content);
        
        // Fix function calls with {{}} parameters
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\{\{([^}]+)\}\}\)/', '$1($2)', $content);
        
        // Fix missing closing parentheses in function calls
        $content = preg_replace('/if\s*\(\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\)\s*\{/', 'if($1($2)){', $content);
        
        // Fix missing closing parentheses in for loops
        $content = preg_replace('/for\s*\(\s*([^;]+);\s*([^;]+);\s*([^)]*)\s*\{/', 'for($1; $2; $3){', $content);
        
        // Fix missing closing parentheses in function calls within conditions (more specific)
        $content = preg_replace('/if\s*\(\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\{/', 'if($1($2)){', $content);
        $content = preg_replace('/for\s*\(\s*([^;]+);\s*([^;]+);\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\{/', 'for($1; $2; $3($4)){', $content);
        
        // Fix missing closing parentheses in for loop conditions
        $content = preg_replace('/for\s*\(\s*([^;]+);\s*([^;]+);\s*([^)]*)\s*\{/', 'for($1; $2; $3){', $content);
        
        // Fix missing closing parentheses in while loop conditions
        $content = preg_replace('/while\s*\(\s*([^)]*)\s*\{/', 'while($1){', $content);
        
        // Fix missing closing parentheses in function calls (more specific)
        $content = preg_replace('/if\s*\(\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\{/', 'if($1($2)){', $content);
        $content = preg_replace('/for\s*\(\s*([^;]+);\s*([^;]+);\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\{/', 'for($1; $2; $3($4)){', $content);
        
        // Fix missing closing parentheses in for loop conditions
        $content = preg_replace('/for\s*\(\s*([^;]+);\s*([^;]+);\s*([^)]*)\s*\{/', 'for($1; $2; $3){', $content);
        
        // Fix missing closing parentheses in while loop conditions
        $content = preg_replace('/while\s*\(\s*([^)]*)\s*\{/', 'while($1){', $content);
        
        // Fix missing closing parentheses in function calls
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*([^)]*)\s*\{/', '$1($2){', $content);
        
        // Fix remaining {{}} patterns that might be missed
        $content = preg_replace('/\{\{([^}]+)\}\}/', '$1', $content);
        
        // Fix remaining -> operators that might be missed (without $ prefix)
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1.$2', $content);
        
        // Fix remaining -> operators in array contexts
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1[$2].$3', $content);
        
        // Fix remaining -> operators in nested array contexts
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]\.([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1[$2].$3[$4].$5', $content);
        
        // Fix remaining -> operators in deeply nested contexts
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]\.([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]\.([a-zA-Z_][a-zA-Z0-9_]*)\[([^\]]+)\]->([a-zA-Z_][a-zA-Z0-9_]*)/', '$1[$2].$3[$4].$5[$6].$7', $content);
        
        // Fix for loop variable declarations
        $content = preg_replace('/for\(([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*([^;]+);\s*([^;]+);\s*([^)]+)\)/', 'for(let $1 = $2; $3; $4)', $content);
        
        // Fix missing closing parentheses in for loop conditions
        $content = preg_replace('/for\(([^;]+);\s*([^;]+);\s*([^)]+)\{/', 'for($1; $2; $3){', $content);
        
        // Fix specific pattern: for(let i = 0; i < 10; i++{ -> for(let i = 0; i < 10; i++){
        $content = str_replace('for(let i = 0; i < 10; i++{', 'for(let i = 0; i < 10; i++){', $content);
        
        // Fix general pattern: for(...; ...; ...++{ -> for(...; ...; ...++){
        $content = preg_replace('/for\(([^;]+);\s*([^;]+);\s*([^)]+)\+\+\{/', 'for($1; $2; $3++){', $content);
        
        // Fix missing closing parentheses in for loop increment
        $content = preg_replace('/for\(([^;]+);\s*([^;]+);\s*([^)]+)\+\+\{/', 'for($1; $2; $3++){', $content);
        
        // Fix any remaining variable++{ -> variable++){
        $content = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)\+\+\{/', '$1++){', $content);
        
        // Fix any remaining i++{ -> i++){
        $content = str_replace('i++{', 'i++){', $content);
        
        // Fix extra ){ patterns that were incorrectly added (more specific)
        // Only remove ){ that are not part of valid JavaScript syntax
        $content = preg_replace('/([^a-zA-Z0-9_\]"\'])\s*\)\s*\{/', '$1{', $content);
        
        // Final fix for for loop syntax
        $content = str_replace('for(let i = 0; i < 10; i++{', 'for(let i = 0; i < 10; i++){', $content);
        $content = str_replace('i++{', 'i++){', $content);
        
        return $content;
    }

    /**
     * Handle remaining directives that might be missed by the smart parser
     */
    private function handleRemainingDirectives(string $content): string
    {
        // Handle @foreach blocks with content
        $content = preg_replace_callback('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s', function($matches) {
            $array = trim($matches[1]);
            $item = trim($matches[2]);
            $innerContent = trim($matches[3]);
            
            // Convert PHP array syntax to JavaScript object
            $array = $this->convertPhpArrayToJs($array);
            
            return "\${SPA.foreach({$array}, ({$item}) => `{$innerContent}`)}";
        }, $content);
        
        // Handle @foreach blocks with key => value
        $content = preg_replace_callback('/@foreach\s*\(\s*([^)]+)\s+as\s+\$([a-zA-Z0-9_]+)\s*=>\s*\$([a-zA-Z0-9_]+)\s*\)(.*?)@endforeach/s', function($matches) {
            $array = trim($matches[1]);
            $key = trim($matches[2]);
            $value = trim($matches[3]);
            $innerContent = trim($matches[4]);
            
            // Convert PHP array syntax to JavaScript object
            $array = $this->convertPhpArrayToJs($array);
            
            return "\${SPA.foreach({$array}, ({$value}, {$key}) => `{$innerContent}`)}";
        }, $content);
        
        // Handle @if blocks
        $content = preg_replace_callback('/@if\s*\(\s*([^)]+)\s*\)(.*?)@endif/s', function($matches) {
            $condition = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            return "\${SPA.execute(() => { let __outputString = ``; if({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
        }, $content);
        
        // Handle @for blocks
        $content = preg_replace_callback('/@for\s*\(\s*([^)]+)\s*\)(.*?)@endfor/s', function($matches) {
            $condition = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            return "\${SPA.execute(() => { let __outputString = ``; for({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
        }, $content);
        
        // Handle @while blocks
        $content = preg_replace_callback('/@while\s*\(\s*([^)]+)\s*\)(.*?)@endwhile/s', function($matches) {
            $condition = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            return "\${SPA.execute(() => { let __outputString = ``; while({$condition}){ __outputString += `{$innerContent}`; } return __outputString; })}";
        }, $content);
        
        // Handle @switch blocks
        $content = preg_replace_callback('/@switch\s*\(\s*([^)]+)\s*\)(.*?)@endswitch/s', function($matches) {
            $value = trim($matches[1]);
            $innerContent = trim($matches[2]);
            
            return "\${SPA.execute(() => { let __outputString = ``; switch({$value}){ {$innerContent} } return __outputString; })}";
        }, $content);
        
        // Handle @php directive
        $content = preg_replace('/@php\s*\(([^)]*)\)/', '$1', $content);
        $content = str_replace('@endphp', '', $content);
        
        return $content;
    }
}
