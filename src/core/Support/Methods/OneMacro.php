<?php

namespace Saola\Core\Support\Methods;

trait OneMacro
{
    protected $macros = [];
    protected static array $cachedSetupMacroMethods = [];
    
    /**
     * Indexed macros for O(1) lookup
     * Format: [
     *   'name' => ['methodName' => [macro1, macro2, ...]],
     *   'equal' => ['methodName' => [macro1, macro2, ...]],
     *   'attribute' => ['methodName' => [macro1, macro2, ...]],
     *   'pattern' => [macro1, macro2, ...],
     * ]
     */
    protected $macroIndex = [
        'name' => [],
        'equal' => [],
        'attribute' => [],
        'pattern' => [],
    ];
    
    /**
     * Lookup cache for macro results
     * Format: ['methodName:type' => [macro1, macro2, ...]]
     */
    protected $macroCache = [];
    
    const NO_MACRO_VALUE_RETURN = '<--!!!no-macro-value-return!!!-->';
    const NEXT_MACRO = '<--!!!next-macro-value-return!!!-->';
    const STOP_MACRO = '<--!!!stop-macro-value-return!!!-->';
    const NO_MACRO_EXIST = '<--!!!no-macro-exist!!!-->';
    

    const MACRO_TYPE_COMPARE = 'compare';
    const MACRO_TYPE_EQUAL = 'equal';
    const MACRO_TYPE_PATTERN = 'pattern';
    const MACRO_TYPE_NAME = 'name';
    const MACRO_TYPE_ATTRIBUTE = 'attribute';
    const SET_ATTRIBUTE = '__setAttribute__';
    const GET_ATTRIBUTE = '__getAttribute__';
    const ISSET_ATTRIBUTE = '__issetAttribute__';
    const UNSET_ATTRIBUTE = '__unsetAttribute__';
    const HAS_ATTRIBUTE = '__hasAttribute__';
    /**
     * Lấy danh sách các method init
     * @return array
     */
    protected function getSetupMacroMethods()
    {
        $class = static::class;

        if (!isset(self::$cachedSetupMacroMethods[$class])) {
            $reflection = new \ReflectionClass($class);
            $methods = $reflection->getMethods(
                \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED
            );

            self::$cachedSetupMacroMethods[$class] = [];

            $className = $reflection->getName(); // chỉ mình class name không có namespace

            foreach ($methods as $method) {

                if (

                    preg_match('/^(setup|set)[A-Z]+(.*)Macro$/', $method->name, $matches) && count($matches) > 1 &&
                    $method->getNumberOfRequiredParameters() === 0
                ) {
                    self::$cachedSetupMacroMethods[$class][] = $method->name;
                }
            }
        }


        return self::$cachedSetupMacroMethods[$class];
    }
    public function addMacro($subject, $callback, $type = self::MACRO_TYPE_COMPARE)
    {
        if (is_string($subject) && is_callable($callback)) {
            $macro = [
                'type' => $type,
                'subject' => $subject,
                'callback' => $callback,
            ];
            
            // Lưu vào array chính (backward compatibility)
            $this->macros[] = $macro;
            
            // Index theo type để tối ưu lookup
            // Tạo copy riêng cho index để tránh modify $macro gốc
            switch ($type) {
                case self::MACRO_TYPE_PATTERN:
                    if (preg_match('/\/.*\/[a-z]*$/i', $subject)) {
                        $indexedMacro = $macro;
                        $indexedMacro['type'] = self::MACRO_TYPE_PATTERN;
                        $this->macroIndex['pattern'][] = $indexedMacro;
                    }
                    break;
                    
                case self::MACRO_TYPE_COMPARE:
                    if (preg_match('/^[A-z_]+[A-z0-9_]*$/i', $subject)) {
                        // Name type - index vào 'name'
                        $indexedMacro = $macro;
                        $indexedMacro['type'] = self::MACRO_TYPE_NAME;
                        if (!isset($this->macroIndex['name'][$subject])) {
                            $this->macroIndex['name'][$subject] = [];
                        }
                        $this->macroIndex['name'][$subject][] = $indexedMacro;
                    } elseif (preg_match('/\/.*\/[a-z]*$/i', $subject)) {
                        // Pattern type
                        $indexedMacro = $macro;
                        $indexedMacro['type'] = self::MACRO_TYPE_PATTERN;
                        $this->macroIndex['pattern'][] = $indexedMacro;
                    }
                    break;
                    
                case self::MACRO_TYPE_NAME:
                    if (!isset($this->macroIndex['name'][$subject])) {
                        $this->macroIndex['name'][$subject] = [];
                    }
                    $this->macroIndex['name'][$subject][] = $macro;
                    break;
                    
                case self::MACRO_TYPE_EQUAL:
                    if (!isset($this->macroIndex['equal'][$subject])) {
                        $this->macroIndex['equal'][$subject] = [];
                    }
                    $this->macroIndex['equal'][$subject][] = $macro;
                    break;
                    
                case self::MACRO_TYPE_ATTRIBUTE:
                    if (!isset($this->macroIndex['attribute'][$subject])) {
                        $this->macroIndex['attribute'][$subject] = [];
                    }
                    $this->macroIndex['attribute'][$subject][] = $macro;
                    break;
                    
                default:
                    throw new \Exception('Invalid macro type');
            }
            
            // Clear cache khi thêm macro mới
            $this->macroCache = [];
        }
        
        return $this;
    }

    public function addAttributeMacro($subject, $callback)
    {
        return $this->addMacro($subject, $callback, self::MACRO_TYPE_ATTRIBUTE);
    }
    public function addMethodMacro($subject, $callback)
    {
        return $this->addMacro($subject, $callback, self::MACRO_TYPE_COMPARE);
    }

    public function getMacro($input, $type = self::MACRO_TYPE_COMPARE)
    {
        $cacheKey = "{$input}:{$type}";
        
        // Check cache first
        if (isset($this->macroCache[$cacheKey])) {
            return $this->macroCache[$cacheKey];
        }
        
        $macros = [];
        
        switch ($type) {
            case self::MACRO_TYPE_NAME:
                // O(1) lookup từ index
                if (isset($this->macroIndex['name'][$input])) {
                    foreach ($this->macroIndex['name'][$input] as $macro) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => [], // Không có matches cho so sánh ===
                        ];
                    }
                }
                break;
                
            case self::MACRO_TYPE_EQUAL:
                // O(1) lookup từ index
                if (isset($this->macroIndex['equal'][$input])) {
                    foreach ($this->macroIndex['equal'][$input] as $macro) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => [], // Không có matches cho so sánh ===
                        ];
                    }
                }
                break;
                
            case self::MACRO_TYPE_ATTRIBUTE:
                // O(1) lookup từ index
                if (isset($this->macroIndex['attribute'][$input])) {
                    foreach ($this->macroIndex['attribute'][$input] as $macro) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => [], // Không có matches cho so sánh ===
                        ];
                    }
                }
                break;
                
            case self::MACRO_TYPE_PATTERN:
                // O(n) - phải check tất cả patterns
                foreach ($this->macroIndex['pattern'] as $macro) {
                    if (preg_match($macro['subject'], $input, $matches)) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => count($matches) > 1 ? $matches : [], // Chỉ lưu nếu có capture groups
                        ];
                    }
                }
                break;
                
            case self::MACRO_TYPE_COMPARE:
                // Check name first (O(1))
                if (isset($this->macroIndex['name'][$input])) {
                    foreach ($this->macroIndex['name'][$input] as $macro) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => [], // Không có matches cho so sánh ===
                        ];
                    }
                }
                
                // Then check patterns (O(n))
                foreach ($this->macroIndex['pattern'] as $macro) {
                    if (preg_match($macro['subject'], $input, $matches)) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => count($matches) > 1 ? $matches : [], // Chỉ lưu nếu có capture groups
                        ];
                    }
                }
                break;
        }
        
        // Cache result
        $this->macroCache[$cacheKey] = $macros;
        
        return $macros;
    }
    
    /**
     * Clear macro cache
     * 
     * Useful khi thêm macro động trong runtime hoặc testing
     * 
     * @return $this
     */
    public function clearMacroCache()
    {
        $this->macroCache = [];
        return $this;
    }

    protected function initMacros()
    {
        foreach ($this->getSetupMacroMethods() as $method) {
            $this->{$method}();
        }
    }

    protected function callMacro($name, ?array $arguments = [], $type = self::MACRO_TYPE_COMPARE)
    {
        if(!($macros = $this->getMacro($name, $type)) || count($macros) === 0) {
            return self::NO_MACRO_EXIST;
        }
        
        return $this->executeMacros($macros, $arguments ?? []);
    }
    
    public function __call($name, $arguments)
    {
        // Tối ưu: Check NAME macros trực tiếp từ index (O(1))
        $cacheKey = "{$name}:" . self::MACRO_TYPE_NAME;
        $macros = [];
        
        // Check cache first
        if (isset($this->macroCache[$cacheKey])) {
            $macros = $this->macroCache[$cacheKey];
        } else {
            // Direct index lookup (O(1))
            if (isset($this->macroIndex['name'][$name])) {
                foreach ($this->macroIndex['name'][$name] as $macro) {
                    $macros[] = [
                        'callback' => $macro['callback'],
                        'matches' => [],
                    ];
                }
                // Cache result
                $this->macroCache[$cacheKey] = $macros;
            }
        }
        
        if (!empty($macros)) {
            $result = $this->executeMacros($macros, $arguments ?? []);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return $result;
            }
        }
        
        // Fallback to COMPARE type (check name + patterns)
        $cacheKey = "{$name}:" . self::MACRO_TYPE_COMPARE;
        if (isset($this->macroCache[$cacheKey])) {
            $macros = $this->macroCache[$cacheKey];
        } else {
            $macros = [];
            
            // Check name first (O(1))
            if (isset($this->macroIndex['name'][$name])) {
                foreach ($this->macroIndex['name'][$name] as $macro) {
                    $macros[] = [
                        'callback' => $macro['callback'],
                        'matches' => [],
                    ];
                }
            }
            
            // Then check patterns (O(n))
            foreach ($this->macroIndex['pattern'] as $macro) {
                if (preg_match($macro['subject'], $name, $matches)) {
                    $macros[] = [
                        'callback' => $macro['callback'],
                        'matches' => count($matches) > 1 ? $matches : [],
                    ];
                }
            }
            
            // Cache result
            $this->macroCache[$cacheKey] = $macros;
        }
        
        if (!empty($macros)) {
            $result = $this->executeMacros($macros, $arguments ?? []);
            if ($result !== self::NO_MACRO_EXIST) {
                return $result;
            }
        }
        
        throw new \Exception('Method ' . $name . ' not found in class ' . static::class);
    }

    public function __get($name)
    {
        // Tối ưu: Check cache trước khi gọi callMacro
        $cacheKey1 = "{$name}:" . self::MACRO_TYPE_ATTRIBUTE;
        if (isset($this->macroCache[$cacheKey1])) {
            $macros = $this->macroCache[$cacheKey1];
            if (!empty($macros)) {
                $result = $this->executeMacros($macros, []);
                if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                    return $result;
                }
            }
        }
        
        // Try direct attribute macro first (O(1) lookup)
        if (isset($this->macroIndex['attribute'][$name])) {
            $macros = [];
            foreach ($this->macroIndex['attribute'][$name] as $macro) {
                $macros[] = ['callback' => $macro['callback'], 'matches' => []];
            }
            $result = $this->executeMacros($macros, []);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return $result;
            }
        }
        
        // Fallback to GET_ATTRIBUTE macro
        $cacheKey2 = self::GET_ATTRIBUTE . ':' . self::MACRO_TYPE_ATTRIBUTE;
        if (isset($this->macroCache[$cacheKey2])) {
            $macros = $this->macroCache[$cacheKey2];
            if (!empty($macros)) {
                $result = $this->executeMacros($macros, [$name]);
                if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                    return $result;
                }
            }
        }
        
        if (isset($this->macroIndex['attribute'][self::GET_ATTRIBUTE])) {
            $macros = [];
            foreach ($this->macroIndex['attribute'][self::GET_ATTRIBUTE] as $macro) {
                $macros[] = ['callback' => $macro['callback'], 'matches' => []];
            }
            $result = $this->executeMacros($macros, [$name]);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return $result;
            }
        }
        
        return null;
    }
    
    public function __set($name, $value)
    {
        // Tối ưu: Check cache và index trước
        if (isset($this->macroIndex['attribute'][$name])) {
            $macros = [];
            foreach ($this->macroIndex['attribute'][$name] as $macro) {
                $macros[] = ['callback' => $macro['callback'], 'matches' => []];
            }
            $result = $this->executeMacros($macros, [$value]);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return $result;
            }
        }
        
        // Fallback to SET_ATTRIBUTE macro
        if (isset($this->macroIndex['attribute'][self::SET_ATTRIBUTE])) {
            $macros = [];
            foreach ($this->macroIndex['attribute'][self::SET_ATTRIBUTE] as $macro) {
                $macros[] = ['callback' => $macro['callback'], 'matches' => []];
            }
            $result = $this->executeMacros($macros, [$name, $value]);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return $result;
            }
        }
        
        return null;
    }
    
    public function __isset($name)
    {
        // Tối ưu: Direct lookup từ index
        if (isset($this->macroIndex['attribute'][self::ISSET_ATTRIBUTE])) {
            $macros = [];
            foreach ($this->macroIndex['attribute'][self::ISSET_ATTRIBUTE] as $macro) {
                $macros[] = ['callback' => $macro['callback'], 'matches' => []];
            }
            $result = $this->executeMacros($macros, [$name]);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return (bool) $result;
            }
        }
        
        return false;
    }
    
    public function __unset($name)
    {
        // Tối ưu: Direct lookup từ index
        if (isset($this->macroIndex['attribute'][self::UNSET_ATTRIBUTE])) {
            $macros = [];
            foreach ($this->macroIndex['attribute'][self::UNSET_ATTRIBUTE] as $macro) {
                $macros[] = ['callback' => $macro['callback'], 'matches' => []];
            }
            $result = $this->executeMacros($macros, [$name]);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return $result;
            }
        }
        
        return null;
    }
    
    public function __hasAttribute($name)
    {
        // Tối ưu: Direct lookup từ index
        if (isset($this->macroIndex['attribute'][self::HAS_ATTRIBUTE])) {
            $macros = [];
            foreach ($this->macroIndex['attribute'][self::HAS_ATTRIBUTE] as $macro) {
                $macros[] = ['callback' => $macro['callback'], 'matches' => []];
            }
            $result = $this->executeMacros($macros, [$name]);
            if ($result !== self::NO_MACRO_EXIST && $result !== self::NO_MACRO_VALUE_RETURN) {
                return (bool) $result;
            }
        }
        
        return false;
    }
    
    /**
     * Execute macros (helper method để tránh duplicate code)
     * 
     * @param array $macros Array of macro data
     * @param array $arguments Arguments to pass to callbacks
     * @return mixed
     */
    protected function executeMacros(array $macros, array $arguments)
    {
        foreach ($macros as $macroData) {
            $callback = is_array($macroData) && isset($macroData['callback']) 
                ? $macroData['callback'] 
                : $macroData;
            $matches = is_array($macroData) && isset($macroData['matches']) 
                ? $macroData['matches'] 
                : [];
            
            $callbackArgs = array_merge(
                array_slice($matches, 1),
                $arguments
            );
            
            $result = call_user_func_array($callback, $callbackArgs);
            if ($result === self::NEXT_MACRO) {
                continue;
            }
            if ($result !== self::NO_MACRO_VALUE_RETURN) {
                return $result;
            }
        }
        
        return self::NO_MACRO_VALUE_RETURN;
    }
}
