<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class EventDirectiveService
{
    protected $phpParser;

    public function __construct(PhpStructureParserService $phpParser)
    {
        $this->phpParser = $phpParser;
    }
    public function registerDirectives(): void {
        $events = [
            'click' => ['click', 'Click', 'onClick', 'OnClick', 'onclick'],
            'change' => ['change', 'Change', 'onChange', 'OnChange', 'onchange'],
            'submit' => ['submit', 'Submit', 'onSubmit', 'OnSubmit', 'onsubmit'],
            'focus' => ['focus', 'Focus', 'onFocus', 'OnFocus', 'onfocus'],
            'blur' => ['blur', 'Blur', 'onBlur', 'OnBlur', 'onblur'],
            'input' => ['input', 'onInput', 'OnInput', 'oninput'],
            'keydown' => ['keydown', 'Keydown', 'onKeydown', 'OnKeydown', 'onkeydown'],
            'keyup' => ['keyup', 'Keyup', 'onKeyup', 'OnKeyup', 'onkeyup'],
            'keypress' => ['keypress', 'Keypress', 'onKeypress', 'OnKeypress', 'onkeypress'],
            'mousedown' => ['mousedown', 'Mousedown', 'onMousedown', 'OnMousedown', 'onmousedown'],
            'mouseup' => ['mouseup', 'Mouseup', 'onMouseup', 'OnMouseup', 'onmouseup'],
            'mouseover' => ['mouseover', 'Mouseover', 'onMouseover', 'OnMouseover', 'onmouseover'],
            'mouseout' => ['mouseout', 'Mouseout', 'onMouseout', 'OnMouseout', 'onmouseout'],
            'mousemove' => ['mousemove', 'Mousemove', 'onMousemove', 'OnMousemove', 'onmousemove'],
            'mouseenter' => ['mouseenter', 'Mouseenter', 'onMouseenter', 'OnMouseenter', 'onmouseenter'],
            'mouseleave' => ['mouseleave', 'Mouseleave', 'onMouseleave', 'OnMouseleave', 'onmouseleave'],
            'dblclick' => ['dblclick', 'Dblclick', 'onDblclick', 'OnDblclick', 'ondblclick'],
            'contextmenu' => ['contextmenu', 'Contextmenu', 'onContextmenu', 'OnContextmenu', 'oncontextmenu'],
            'wheel' => ['wheel', 'Wheel', 'onWheel', 'OnWheel', 'onwheel'],
            'scroll' => ['scroll', 'Scroll', 'onScroll', 'OnScroll', 'onscroll'],
            'resize' => ['resize', 'Resize', 'onResize', 'OnResize', 'onresize'],
            'load' => ['load', 'Load', 'onLoad', 'OnLoad', 'onload'],
            'unload' => ['unload', 'Unload', 'onUnload', 'OnUnload', 'onunload'],
            'beforeunload' => ['beforeunload', 'Beforeunload', 'onBeforeunload', 'OnBeforeunload', 'onbeforeunload'],
            'error' => ['error', 'Error', 'onError', 'OnError', 'onerror'],
            'abort' => ['abort', 'Abort', 'onAbort', 'OnAbort', 'onabort'],
            'select' => ['select', 'Select', 'onSelect', 'OnSelect', 'onselect'],
            'selectstart' => ['selectstart', 'selectStart', 'SelectStart', 'onSelectStart', 'OnSelectStart', 'onselectstart'],
            'selectionchange' => ['selectionchange', 'SelectionChange', 'onSelectionChange', 'OnSelectionChange', 'onselectionchange'],
        ];

        foreach ($events as $eventType => $directives) {
            foreach ($directives as $directive) {
                Blade::directive($directive, function ($expression) use ($eventType) {
                    return $this->processEventDirective($eventType, $expression);
                });
            }
        }
    }
    /**
     * Process Event directive với multiple handlers
     * Tất cả đều dùng addEventListener với config array format
     * Config array có thể chứa: arrow functions và handler objects
     */
    public function processEventDirective($eventType, $expression)
    {
        try {
            $raw = trim($expression);
            if (empty($raw)) {
                return '';
            }
            
            // Parse expression để extract handlers và expressions
            $configArray = $this->parseEventConfig($raw);
            
            if (empty($configArray)) {
                return '';
            }
            
            // Build config array JSON string
            $configJson = $this->buildConfigArrayJson($configArray);
            
            // Return helper call để add event listener
            return "<?php echo \$__helper->addEventListener(\$__VIEW_PATH__, \$__VIEW_ID__,'{$eventType}', {$configJson}); ?>";
                   
        } catch (\Exception $e) {
            // Log error và return empty string để không break template
            Log::error("Event directive error: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Parse event expression thành config array
     * Config array chứa: arrow functions và handler objects
     * Giữ nguyên thứ tự như trong input
     */
    protected function parseEventConfig($expression)
    {
        $configArray = [];
        
        // Remove outer parentheses if exists
        $expression = trim($expression);
        if (strlen($expression) >= 2 && $expression[0] === '(' && $expression[-1] === ')') {
            $expression = substr($expression, 1, -1);
        }
        
        if (empty($expression)) {
            return $configArray;
        }
        
        // Split by comma, but respect nested parentheses and quotes
        $parts = $this->splitByComma($expression);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            // Kiểm tra xem có phải là function call không có $ prefix không
            if ($this->isFunctionCallWithoutDollar($part)) {
                // Kiểm tra xem function có chứa $event trong params không
                if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/s', $part, $matches)) {
                    $paramsString = $matches[2];
                    if ($this->functionContainsEventParam($paramsString)) {
                        // Có $event trong params → wrap toàn bộ trong arrow function
                        $funcName = $matches[1];
                        $jsParams = preg_replace('/\$(?:event|Event|EVENT)(?![a-zA-Z])/', 'event', $paramsString);
                        $jsParams = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $jsParams);
                        $jsParams = $this->convertPhpArrayToJsInString($jsParams);
                        
                        $arrowFunction = '(event) => ' . $funcName . '(' . $jsParams . ')';
                        $configArray[] = [
                            'type' => 'arrow',
                            'data' => $arrowFunction
                        ];
                        continue;
                    }
                }
                
                // Không có $event → dùng object config
                $handler = $this->parseHandler($part);
                if ($handler) {
                    $configArray[] = [
                        'type' => 'handler',
                        'data' => $handler
                    ];
                }
            } else {
                // Biểu thức hoặc hàm có $ prefix
                // Kiểm tra xem có nested function calls phức tạp không
                // Nếu có → dùng object handler để chắc chắn hơn
                if ($this->hasNestedFunctionCalls($part)) {
                    // Có nested calls → parse như handler function
                    // Xử lý cả trường hợp có $ prefix (state setter)
                    $handler = $this->parseHandlerWithDollar($part);
                    if ($handler) {
                        // Parse và process parameters (có thể chứa function calls)
                        $paramsString = implode(', ', $handler['params']);
                        $processedParams = $this->parseHandlerParameters($paramsString);
                        
                        $configArray[] = [
                            'type' => 'handler',
                            'data' => [
                                'handler' => $handler['handler'],
                                'params' => $processedParams
                            ]
                        ];
                    } else {
                        // Nếu không parse được → dùng arrow function
                        $expressions = $this->splitBySemicolon($part);
                        foreach ($expressions as $expr) {
                            $expr = trim($expr);
                            if (empty($expr)) continue;
                            
                            $configArray[] = [
                                'type' => 'arrow',
                                'data' => $this->processExpressionToArrow($expr)
                            ];
                        }
                    }
                } else {
                    // Không có nested calls → dùng arrow function format
                    // Tách biểu thức có `;` thành nhiều arrow functions
                    $expressions = $this->splitBySemicolon($part);
                    foreach ($expressions as $expr) {
                        $expr = trim($expr);
                        if (empty($expr)) continue;
                        
                        $configArray[] = [
                            'type' => 'arrow',
                            'data' => $this->processExpressionToArrow($expr)
                        ];
                    }
                }
            }
        }
        
        return $configArray;
    }
    
    /**
     * Parse multiple event handlers từ expression (cải tiến từ file cũ)
     * @deprecated - Sử dụng parseEventConfig thay thế
     */
    protected function parseEventHandlers($expression)
    {
        $handlers = [];
        
        // Remove outer parentheses if exists (only if the entire expression is wrapped in parentheses)
        $expression = trim($expression);
        if (strlen($expression) >= 2 && $expression[0] === '(' && $expression[-1] === ')') {
            $expression = substr($expression, 1, -1);
        }
        
        if (empty($expression)) {
            return $handlers;
        }
        
        // Split by comma, but respect nested parentheses and quotes
        $handlerStrings = $this->splitByComma($expression);
        
        foreach ($handlerStrings as $handlerString) {
            $handlerString = trim($handlerString);
            
            if (empty($handlerString)) continue;
            
            // Parse individual handler
            $handler = $this->parseHandler($handlerString);
            if ($handler) {
                $handlers[] = $handler;
            }
        }
        
        return $handlers;
    }
    
    /**
     * Parse individual handler: deleteUser(Event, $user->id) - cải tiến để xử lý nested arrays
     */
    protected function parseHandler($handlerString)
    {
        $handlerString = trim($handlerString);
        
        // Check if has parameters: functionName(params)
        if (strpos($handlerString, '(') !== false) {
            // Find the first opening parenthesis
            $openParenPos = strpos($handlerString, '(');
            $functionName = trim(substr($handlerString, 0, $openParenPos));
            
            // Find the matching closing parenthesis
            $paramsString = $this->extractBalancedParentheses($handlerString, $openParenPos);
            
            if ($paramsString !== null) {
                // Parse parameters
                $params = $this->parseHandlerParameters($paramsString);
                
                return [
                    'handler' => $functionName,
                    'params' => $params
                ];
            }
        } else {
            // Simple function call without parameters
            return [
                'handler' => $handlerString,
                'params' => []
            ];
        }
        
        return null;
    }
    
    /**
     * Extract balanced parentheses content
     */
    protected function extractBalancedParentheses($string, $startPos)
    {
        $parenCount = 0;
        $bracketCount = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = $startPos; $i < strlen($string); $i++) {
            $char = $string[$i];
            
            if (($char === '"' || $char === "'") && !$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar && $inQuotes) {
                // Check if this is an escaped quote
                if ($i > 0 && $string[$i - 1] === '\\') {
                    continue;
                }
                $inQuotes = false;
                $quoteChar = '';
            } elseif (!$inQuotes) {
                if ($char === '(') {
                    $parenCount++;
                } elseif ($char === ')') {
                    $parenCount--;
                    if ($parenCount === 0) {
                        // Found matching closing parenthesis
                        return substr($string, $startPos + 1, $i - $startPos - 1);
                    }
                } elseif ($char === '[') {
                    $bracketCount++;
                } elseif ($char === ']') {
                    $bracketCount--;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Parse handler parameters: "Event, $user->id, 'User deleted'"
     * Nếu param là function call → dùng object config
     * Nếu param là biến đơn giản → dùng () => variable
     */
    protected function parseHandlerParameters($paramsString)
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
                    $params[] = $this->processParameter(trim($current), true);
                    $current = '';
                    continue;
                }
            }
            
            $current .= $char;
        }
        
        if (!empty(trim($current))) {
            $params[] = $this->processParameter(trim($current), true);
        }
        
        return $params;
    }
    
    /**
     * Kiểm tra xem param có chứa $event với property access hoặc method call không
     * Ví dụ: $event.target, $event.preventDefault(), test($event.type)
     */
    protected function hasEventWithAccess($param)
    {
        // Pattern: $event theo sau bởi . hoặc chữ khác (property/method access)
        // Hoặc $event nằm trong function call: func($event.prop)
        return (bool)preg_match('/\$(?:event|Event|EVENT)\s*\./', $param);
    }
    
    /**
     * Convert expression có $event.property hoặc $event.method() thành arrow function
     * Input: $event.target → Output: (event) => event.target
     * Input: test($event.type, $message) → Output: (event) => test(event.type, message)
     */
    protected function convertEventWithAccessToArrow($param)
    {
        // Convert $event/$Event/$EVENT thành event (JavaScript variable)
        $result = preg_replace('/\$(?:event|Event|EVENT)(?![a-zA-Z])/', 'event', $param);
        
        // Convert các PHP variables khác ($var → var)
        $result = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $result);
        
        // Convert PHP array syntax to JS object (nếu có)
        $result = $this->convertPhpArrayToJsInString($result);
        
        // Wrap trong arrow function
        return '(event) => ' . $result;
    }
    
    /**
     * Kiểm tra xem function call có chứa $event trong params không
     */
    protected function functionContainsEventParam($paramsString)
    {
        return (bool)preg_match('/\$(?:event|Event|EVENT)(?![a-zA-Z])/', $paramsString);
    }
    
    /**
     * Process individual parameter - xử lý đặc biệt cho Event parameters và @val/@value
     * @param bool $inParamsContext - True nếu đang xử lý params trong object config
     */
    protected function processParameter($param, $inParamsContext = false)
    {
        // Nếu đã là PHP array string (có dạng ['handler' => ...]), giữ nguyên
        if (is_string($param) && (strpos($param, "['handler'") === 0 || strpos($param, '["handler"') === 0)) {
            return $param;
        }
        
        // Nếu đã là object config string (có dạng {"handler":...}), giữ nguyên
        if (is_string($param) && strpos($param, '{"handler"') === 0) {
            return $param;
        }
        
        // Nếu parameter đã được bao trong quotes thì giữ nguyên
        if (is_string($param) && strlen($param) >= 2 &&
            (($param[0] === '"' && $param[-1] === '"') || 
             ($param[0] === "'" && $param[-1] === "'") ||
             ($param[0] === '`' && $param[-1] === '`'))) {
            return $param;
        }
        
        // SPECIAL CASE: Nếu có $event với property/method access → convert thành arrow function
        // Ví dụ: $event.target → (event) => event.target
        // Phải làm TRƯỚC các bước normalize khác
        if ($this->hasEventWithAccess($param)) {
            return $this->convertEventWithAccessToArrow($param);
        }
        
        // Kiểm tra xem có phải là function call không (có hoặc không có $ prefix)
        // Nếu là function call → parse thành object config HOẶC arrow function (nếu có $event)
        if ($this->isFunctionCallInParam($param)) {
            $handler = $this->parseFunctionCallInParam($param);
            if ($handler) {
                // Kiểm tra xem function có chứa $event trong params không
                $paramsString = $handler['original_params'] ?? '';
                if (!empty($paramsString) && $this->functionContainsEventParam($paramsString)) {
                    // Có $event trong params → wrap toàn bộ trong arrow function
                    // Convert $event → event và các PHP vars → JS vars
                    $jsParams = preg_replace('/\$(?:event|Event|EVENT)(?![a-zA-Z])/', 'event', $paramsString);
                    $jsParams = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $jsParams);
                    $jsParams = $this->convertPhpArrayToJsInString($jsParams);
                    
                    return '(event) => ' . $handler['handler'] . '(' . $jsParams . ')';
                }
                
                // Không có $event → dùng object config format
                // Build object config string
                $processedHandlerParams = [];
                foreach ($handler['params'] as $p) {
                    $processedHandlerParams[] = $this->processParameter(trim($p), true);
                }
                $paramsStr = implode(', ', $processedHandlerParams);
                return "['handler' => '{$handler['handler']}', 'params' => [{$paramsStr}]]";
            }
        }
        
        // Với PHP compiler, giữ nguyên PHP variables ($item) để PHP xử lý
        // Chỉ convert sang JavaScript format khi cần thiết (cho arrow functions)
        
        // Xử lý @val(...) -> "#VALUE:..." - xử lý recursive để handle trong arrays
        $param = $this->processAttrPropInString($param, '@val', '#VALUE');
        
        // Xử lý @value(...) -> "#VALUE:..." - xử lý recursive để handle trong arrays
        $param = $this->processAttrPropInString($param, '@value', '#VALUE');
        
        // Xử lý @attr(...) -> "#ATTR:..." - xử lý recursive để handle trong arrays
        $param = $this->processAttrPropInString($param, '@attr', '#ATTR');
        
        // Xử lý @prop(...) -> "#PROP:..." - xử lý recursive để handle trong arrays
        $param = $this->processAttrPropInString($param, '@prop', '#PROP');
        
        // Xử lý Event parameters trong mọi context
        $param = $this->processEventInString($param);
        
        // Chuẩn hóa @event và $event thành @EVENT (alias support)
        $param = preg_replace('/@(?:event|Event|EVENT)(?![a-zA-Z])/i', '@EVENT', $param);
        $param = preg_replace('/\$(?:event|Event|EVENT)(?![a-zA-Z])/i', '@EVENT', $param);
        
        // @EVENT luôn là chuỗi "@EVENT"
        if (trim($param) === '@EVENT') {
            return '"@EVENT"';
        }
        
        // Thay thế @EVENT thành "@EVENT" nếu chưa có quotes
        $param = preg_replace('/(?<!")@EVENT(?!")/', '"@EVENT"', $param);
        
        // Với PHP compiler, giữ nguyên PHP variables và expressions
        // Không cần wrap trong () => vì PHP sẽ xử lý trực tiếp
        return $param;
    }
    
    /**
     * Kiểm tra xem param có phải là function call không (có hoặc không có $ prefix)
     */
    protected function isFunctionCallInParam($param)
    {
        $param = trim($param);
        // Match function call pattern: name(...) hoặc $name(...)
        return (bool)preg_match('/^(\$?[a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $param);
    }
    
    /**
     * Parse function call trong param thành handler object
     */
    protected function parseFunctionCallInParam($param)
    {
        $param = trim($param);
        
        // Match function call với hoặc không có $ prefix
        if (preg_match('/^(\$?[a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/s', $param, $matches)) {
            $funcNameWithDollar = $matches[1];
            $paramsString = trim($matches[2]);
            
            // Xử lý tên hàm
            if (strpos($funcNameWithDollar, '$') === 0) {
                $funcName = substr($funcNameWithDollar, 1); // Bỏ $ prefix
                // TODO: Kiểm tra xem có phải là state variable không
                // Nếu là state setter → dùng tên setter
                $handlerName = $funcName; // Tạm thời giữ nguyên
            } else {
                // Không có $ prefix
                $handlerName = $funcNameWithDollar;
            }
            
            // Parse parameters
            $params = [];
            if (!empty($paramsString)) {
                $params = $this->splitByComma($paramsString);
            }
            
            return [
                'handler' => $handlerName,
                'params' => $params,
                'original_params' => $paramsString  // Lưu params string gốc để check $event
            ];
        }
        
        return null;
    }
    
    /**
     * Process Event parameters trong string một cách recursive
     */
    protected function processEventInString($param)
    {
        // Tìm tất cả Event variations trong string (@event, @Event, @EVENT, event, Event, EVENT)
        $event_pattern = '/(?<![@])(?:@)?(?:Event|EVENT|event)(?:\(\))?(?![a-zA-Z])/i';
        $param = preg_replace($event_pattern, '@EVENT', $param);
        
        // Xử lý $event variations (alias support)
        $param = preg_replace('/\$(?:event|Event|EVENT)(?![a-zA-Z])/i', '@EVENT', $param);
        
        return $param;
    }
    
    /**
     * Convert PHP array syntax to JavaScript object/array syntax trong string
     * ['key' => 'value'] -> {"key": "value"}
     * ['a', 'b'] -> ["a", "b"]
     */
    protected function convertPhpArrayToJsInString($param)
    {
        // Convert PHP associative array to JS object
        $param = preg_replace('/\[\s*\'([^\']+)\'\s*=>\s*/', '{"$1": ', $param);
        $param = preg_replace('/\[\s*"([^"]+)"\s*=>\s*/', '{"$1": ', $param);
        
        // Convert closing ] to } for objects
        if (strpos($param, '{') !== false) {
            $param = preg_replace('/\]/', '}', $param);
        }
        
        // Convert single quotes to double quotes for JSON compatibility
        $param = preg_replace("/'/", '"', $param);
        
        return $param;
    }
    
    /**
     * Process @attr, @prop, @val, @value trong string một cách recursive
     */
    protected function processAttrPropInString($param, $directive, $prefix)
    {
        // Tìm tất cả @directive(...) hoặc @directive() trong string
        $pattern = '/' . preg_quote($directive, '/') . '\s*\(\s*([^)]*)\s*\)/i';
        
        $param = preg_replace_callback($pattern, function($matches) use ($prefix) {
            $value = trim($matches[1]);
            
            if (empty($value)) {
                // Trường hợp @directive() - không có tham số
                return '"' . $prefix . '"';
            } else {
                // Loại bỏ quotes nếu có
                $value = trim($value, '"\'');
                // Thay thế @directive(...) bằng "#PREFIX:..." hoặc "#PREFIX"
                return '"' . $prefix . ':' . $value . '"';
            }
        }, $param);
        
        return $param;
    }
    
    
    /**
     * Split expression by comma, respecting nested parentheses, square brackets and quotes
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
     * Build config array JSON string từ config array
     * Config array chứa: arrow functions và handler objects
     */
    protected function buildConfigArrayJson($configArray)
    {
        $items = [];
        
        foreach ($configArray as $item) {
            if ($item['type'] === 'handler') {
                // Handler object: {"handler": "name", "params": [...]}
                $handler = $item['data'];
                $handlerName = $handler['handler'];
                $params = $handler['params'];
                
                $paramStrings = [];
                foreach ($params as $param) {
                    $paramStrings[] = $this->buildParamString($param);
                }
                
                $items[] = "['handler' => '{$handlerName}', 'params' => [" . implode(', ', $paramStrings) . "]]";
            } else if ($item['type'] === 'arrow') {
                // Arrow function: "(event) => ..." hoặc "() => ..."
                // Với PHP compiler, arrow functions là strings trong PHP array
                // Nhưng cần đảm bảo format đúng
                $arrowFunction = $item['data'];
                // Nếu arrow function đã có quotes thì giữ nguyên, nếu không thì wrap
                if ((strpos($arrowFunction, "'") === 0 && substr($arrowFunction, -1) === "'") ||
                    (strpos($arrowFunction, '"') === 0 && substr($arrowFunction, -1) === '"')) {
                    $items[] = $arrowFunction;
                } else {
                    $items[] = "'" . addslashes($arrowFunction) . "'";
                }
            }
        }
        
        return '[' . implode(', ', $items) . ']';
    }
    
    /**
     * Build param string cho handler params
     */
    protected function buildParamString($param)
    {
        // Nếu đã là PHP array string (có dạng ['handler' => ...]), giữ nguyên
        if (is_string($param) && (strpos($param, "['handler'") === 0 || strpos($param, '["handler"') === 0)) {
            return $param;
        }
        
        // Nếu đã là object config string (có dạng {"handler":...}), giữ nguyên
        if (is_string($param) && strpos($param, '{"handler"') === 0) {
            return $param;
        }
        
        // Nếu là PHP variable (bắt đầu với $), giữ nguyên để PHP xử lý
        if (is_string($param) && strpos($param, '$') === 0) {
            return $param;
        }
        
        // Nếu đã có quotes thì giữ nguyên
        if (is_string($param) && 
            ((strpos($param, "'") === 0 && substr($param, -1) === "'") ||
             (strpos($param, '"') === 0 && substr($param, -1) === '"'))) {
            return $param;
        }
        
        // Nếu là arrow function, giữ nguyên
        if (is_string($param) && strpos($param, '=>') !== false) {
            return "'" . addslashes($param) . "'";
        }
        
        // Kiểm tra nếu là array hoặc object phức tạp
        if (is_string($param) && $this->isComplexParameter($param)) {
            return $param;
        }
        
        // Tham số đơn giản (string, number) - thêm quotes đơn
        return "'" . addslashes($param) . "'";
    }
    
    /**
     * Kiểm tra xem có phải là function call không có $ prefix không
     */
    protected function isFunctionCallWithoutDollar($expr)
    {
        $expr = trim($expr);
        
        // Match function call pattern: name(...)
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $expr, $matches)) {
            $funcName = $matches[1];
            // Không có $ prefix
            if (strpos($funcName, '$') !== 0) {
                return true;
            }
        }
        
        // Match simple function name: name
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)$/', $expr, $matches)) {
            $funcName = $matches[1];
            if (strpos($funcName, '$') !== 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Kiểm tra xem expression có chứa nested function calls không
     */
    protected function hasNestedFunctionCalls($expr)
    {
        $expr = trim($expr);
        
        // Tìm function call pattern: name(...) hoặc $name(...)
        if (preg_match('/^(\$?[a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/s', $expr, $matches)) {
            $paramsString = trim($matches[2]);
            if (empty($paramsString)) {
                return false;
            }
            
            // Đếm số function calls trong params (không tính function call ngoài cùng)
            // Pattern: tên hàm + ( (có thể có $ prefix)
            $nestedPattern = '/\$?[a-zA-Z_][a-zA-Z0-9_]*\s*\(/';
            preg_match_all($nestedPattern, $paramsString, $nestedMatches);
            // Nếu có nhiều hơn 0 function calls trong params → có nested calls
            return count($nestedMatches[0]) > 0;
        }
        
        return false;
    }
    
    /**
     * Parse handler có $ prefix (state setter)
     */
    protected function parseHandlerWithDollar($handlerString)
    {
        $handlerString = trim($handlerString);
        
        // Match function call với $ prefix: $name(...)
        if (preg_match('/^\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/s', $handlerString, $matches)) {
            $funcName = $matches[1];
            $paramsString = trim($matches[2]);
            
            // TODO: Kiểm tra xem có phải là state variable không
            // Nếu là state setter → dùng tên setter
            $handlerName = $funcName; // Tạm thời giữ nguyên
            
            // Parse parameters
            $params = [];
            if (!empty($paramsString)) {
                $params = $this->splitByComma($paramsString);
            }
            
            return [
                'handler' => $handlerName,
                'params' => $params
            ];
        }
        
        return null;
    }
    
    /**
     * Xử lý expression thành arrow function format
     * Nếu là state setter với nested calls trong params → xử lý params thành object config
     */
    protected function processExpressionToArrow($expr)
    {
        $originalExpr = trim($expr);
        
        // Kiểm tra xem có phải là function call với $ prefix không
        // Ví dụ: $setCount(nestedCall($count, $count + 1))
        if (preg_match('/^\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)\)$/s', $originalExpr, $matches)) {
            $funcName = $matches[1];
            $paramsStr = trim($matches[2]);
            
            // TODO: Kiểm tra xem có phải là state variable không
            // Nếu là state setter → xử lý params
            // Parse params - nếu param là function call (không phải state variable) → dùng object config
            $params = $this->splitByComma($paramsStr);
            $processedParams = [];
            
            foreach ($params as $param) {
                $param = trim($param);
                
                // Kiểm tra xem có phải là function call không
                if ($this->isFunctionCallInParam($param)) {
                    // Function call → parse thành object config
                    $handler = $this->parseFunctionCallInParam($param);
                    if ($handler) {
                        // Build object config string
                        $processedHandlerParams = [];
                        foreach ($handler['params'] as $p) {
                            $processedHandlerParams[] = $this->processParameter(trim($p), true);
                        }
                        $paramsStrInner = implode(', ', $processedHandlerParams);
                        $handlerStr = "['handler' => '{$handler['handler']}', 'params' => [{$paramsStrInner}]]";
                        $processedParams[] = $handlerStr;
                        continue;
                    }
                }
                
                // Không phải function call → giữ nguyên PHP variable format
                $paramProcessed = $this->processEventInString($param);
                // Chuẩn hóa @event và $event thành @EVENT (alias support)
                $paramProcessed = preg_replace('/@(?:event|Event|EVENT)(?![a-zA-Z])/i', '@EVENT', $paramProcessed);
                $paramProcessed = preg_replace('/\$(?:event|Event|EVENT)(?![a-zA-Z])/i', '@EVENT', $paramProcessed);
                $paramProcessed = $this->processAttrPropInString($paramProcessed, '@attr', '#ATTR');
                $paramProcessed = $this->processAttrPropInString($paramProcessed, '@prop', '#PROP');
                $paramProcessed = $this->processAttrPropInString($paramProcessed, '@val', '#VALUE');
                $paramProcessed = $this->processAttrPropInString($paramProcessed, '@value', '#VALUE');
                $paramProcessed = preg_replace('/(?<!")@EVENT(?!")/', '"@EVENT"', $paramProcessed);
                $processedParams[] = $paramProcessed;
            }
            
            $paramsStrJs = implode(', ', $processedParams);
            // TODO: Setter name format: setStateKey (capitalize first letter)
            $setterName = $funcName; // Tạm thời giữ nguyên
            return "(event) => {$setterName}({$paramsStrJs})";
        }
        
        // Với PHP compiler, giữ nguyên PHP variables trong arrow functions
        // Convert PHP variable to JavaScript ($item -> item) chỉ cho arrow function body
        $exprJs = preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $originalExpr);
        
        // Xử lý Event parameters
        $exprJs = $this->processEventInString($exprJs);
        
        // Xử lý @attr, @prop, @val, @value
        $exprJs = $this->processAttrPropInString($exprJs, '@attr', '#ATTR');
        $exprJs = $this->processAttrPropInString($exprJs, '@prop', '#PROP');
        $exprJs = $this->processAttrPropInString($exprJs, '@val', '#VALUE');
        $exprJs = $this->processAttrPropInString($exprJs, '@value', '#VALUE');
        
        // Chuẩn hóa @event và $event thành @EVENT (alias support)
        $exprJs = preg_replace('/@(?:event|Event|EVENT)(?![a-zA-Z])/i', '@EVENT', $exprJs);
        $exprJs = preg_replace('/\$(?:event|Event|EVENT)(?![a-zA-Z])/i', '@EVENT', $exprJs);
        
        // Thay thế @EVENT thành "@EVENT" nếu chưa có quotes
        $exprJs = preg_replace('/(?<!")@EVENT(?!")/', '"@EVENT"', $exprJs);
        
        // Kiểm tra xem có phải là biểu thức không
        if ($this->looksLikeExpression($exprJs)) {
            return "(event) => {$exprJs}";
        }
        
        return "() => {$exprJs}";
    }
    
    /**
     * Kiểm tra xem có phải là biểu thức không
     */
    protected function looksLikeExpression($s)
    {
        $s = trim($s);
        
        // Nếu đã là arrow function, không phải biểu thức đơn giản
        if (strpos($s, '=>') !== false) {
            return false;
        }
        
        // @EVENT là special directive, không phải biểu thức
        if ($s === '@EVENT' || $s === '"@EVENT"') {
            return false;
        }
        
        // Literals
        if (is_numeric($s)) {
            return false;
        }
        if (in_array(strtolower($s), ['true', 'false', 'null'])) {
            return false;
        }
        
        // Nếu có toán tử hoặc function call → là biểu thức
        if (preg_match('/[+\-*\/%=<>!&|]/', $s) || strpos($s, '(') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Build handlers JSON string với PHP variables (từ file cũ)
     * @deprecated - Sử dụng buildConfigArrayJson thay thế
     */
    protected function buildHandlersJson($handlers)
    {
        $handlerStrings = [];
        
        foreach ($handlers as $handler) {
            $handlerName = $handler['handler'];
            $params = $handler['params'];
            
            $paramStrings = [];
            foreach ($params as $param) {
                // Nếu là PHP variable (bắt đầu với $), giữ nguyên để PHP xử lý
                if (strpos($param, '$') === 0) {
                    $paramStrings[] = $param;
                } else {
                    // Nếu đã có quotes thì giữ nguyên
                    if ((strpos($param, "'") === 0 && substr($param, -1) === "'") ||
                        (strpos($param, '"') === 0 && substr($param, -1) === '"')) {
                        $paramStrings[] = $param;
                    } else {
                        // Kiểm tra nếu là array hoặc object phức tạp
                        if ($this->isComplexParameter($param)) {
                            // Giữ nguyên array syntax thay vì convert thành JSON
                            $paramStrings[] = $param;
                        } else {
                            // Tham số đơn giản (string, number) - thêm quotes đơn
                            $paramStrings[] = "'{$param}'";
                        }
                    }
                }
            }
            
            $handlerStrings[] = "['handler' => '{$handlerName}', 'params' => [" . implode(', ', $paramStrings) . "]]";
        }
        
        return '[' . implode(', ', $handlerStrings) . ']';
    }

    /**
     * Kiểm tra tham số có phức tạp không (array, object, function call) - cải tiến
     */
    protected function isComplexParameter($param)
    {
        $param = trim($param);
        
        // Nếu bắt đầu với [ hoặc { thì là array/object
        if (strpos($param, '[') === 0 || strpos($param, '{') === 0) {
            return true;
        }
        
        // Nếu có chứa function call (có dấu ngoặc đơn và không phải string)
        if (strpos($param, '(') !== false && 
            !((strpos($param, "'") === 0 && substr($param, -1) === "'") ||
              (strpos($param, '"') === 0 && substr($param, -1) === '"'))) {
            return true;
        }
        
        // Nếu có chứa array syntax (có [ và ])
        if (strpos($param, '[') !== false && strpos($param, ']') !== false) {
            return true;
        }
        
        // Nếu là number thì không phức tạp
        if (is_numeric($param)) {
            return false;
        }
        
        // Các trường hợp khác coi như đơn giản
        return false;
    }

    /**
     * Quyết định expression là Quick Handle
     */
    protected function looksLikeQuickHandle(string $expr): bool
    {
        $t = trim($expr);
        if ($t === '') return false;
        if (strpos($t, ';') !== false) return true;
        $parts = $this->splitByComma($t);
        if (empty($parts)) return true;
        foreach ($parts as $p) {
            $p = trim($p);
            // Check if it's a simple function call
            if (preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*\s*\((.*)\)$/', $p, $matches)) {
                $paramsStr = $matches[1];
                // If params contain expressions (operators like +, -, *, /, %), use quickHandle
                if ($this->containsExpressions($paramsStr)) {
                    return true;
                }
            } elseif (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $p)) {
                // Not a simple variable or function call
                return true;
            }
        }
        return false;
    }

    /**
     * Check if parameter string contains expressions (operators, method calls, etc.)
     */
    protected function containsExpressions(string $paramsStr): bool
    {
        $paramsStr = trim($paramsStr);
        if (empty($paramsStr)) return false;
        
        // Check for mathematical operators
        if (preg_match('/[+\-*\/%]/', $paramsStr)) {
            return true;
        }
        
        // Check for method calls like $obj->method()
        if (strpos($paramsStr, '->') !== false) {
            return true;
        }
        
        // Check for array access like $arr[0]
        if (preg_match('/\[.*\]/', $paramsStr)) {
            return true;
        }
        
        // Check for ternary operators
        if (strpos($paramsStr, '?') !== false && strpos($paramsStr, ':') !== false) {
            return true;
        }
        
        return false;
    }

    /** Build array for Quick Handle output */
    protected function buildQuickHandleArray(string $expr): string
    {
        $items = [];
        $topParts = $this->splitByComma($expr);
        foreach ($topParts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $stmts = $this->splitBySemicolon($part);
            foreach ($stmts as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') continue;
                if ($this->isFunctionCall($stmt)) {
                    [$name, $paramsStr] = $this->splitFunctionCall($stmt);
                    $params = $this->parseHandlerParameters($paramsStr);
                    // Keep PHP variables as-is (e.g., $i) in params
                    $paramStr = implode(', ', $params);
                    $items[] = "['handle' => '{$name}', 'params' => [{$paramStr}]]";
                } else {
                    $exprJs = $this->phpVarsToJs($stmt);
                    $bindings = $this->buildBindingsFromExpression($stmt);
                    $items[] = "['expression' => '" . addslashes($exprJs) . "', 'bindings' => {$bindings}]";
                }
            }
        }
        return '[' . implode(', ', $items) . ']';
    }

    protected function splitBySemicolon(string $s): array
    {
        $parts = [];
        $current = '';
        $paren = 0; $bracket = 0; $inQuotes = false; $q='';
        $len = strlen($s);
        for ($i=0;$i<$len;$i++){
            $ch = $s[$i];
            if (($ch==='"' || $ch==="'") && !$inQuotes){ $inQuotes=true; $q=$ch; $current.=$ch; continue; }
            if ($inQuotes){
                $current.=$ch;
                if ($ch===$q && ($i===0 || $s[$i-1] !== '\\')){ $inQuotes=false; $q=''; }
                continue;
            }
            if ($ch==='(') $paren++;
            elseif ($ch===')') $paren--;
            elseif ($ch==='[') $bracket++;
            elseif ($ch===']') $bracket--;
            if ($ch===';' && $paren===0 && $bracket===0){
                $parts[] = trim($current);
                $current = '';
                continue;
            }
            $current.=$ch;
        }
        if (trim($current) !== '') $parts[] = trim($current);
        return $parts;
    }

    protected function isFunctionCall(string $s): bool
    {
        return (bool)preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*\s*\(.*\)$/', trim($s));
    }

    protected function splitFunctionCall(string $s): array
    {
        $s = trim($s);
        $open = strpos($s, '(');
        $name = trim(substr($s, 0, $open));
        $params = $this->extractBalancedParentheses($s, $open) ?? '';
        return [$name, $params];
    }

    protected function phpVarsToJs(string $code): string
    {
        return preg_replace('/\\$([a-zA-Z_][a-zA-Z0-9_]*)/', '$1', $code);
    }

    protected function normalizePhpVarToName(string $param): string
    {
        if (preg_match('/^\\$([a-zA-Z_][a-zA-Z0-9_]*)$/', trim($param), $m)) {
            return $m[1];
        }
        return $param;
    }

    protected function buildBindingsFromExpression(string $expr): string
    {
        preg_match_all('/\\$([a-zA-Z_][a-zA-Z0-9_]*)/', $expr, $matches);
        $vars = array_unique($matches[1] ?? []);
        if (empty($vars)) return '[]';
        $pairs = array_map(function($v){ return "'{$v}' => 'states.{$v}'"; }, $vars);
        return '[' . implode(', ', $pairs) . ']';
    }
}
