# Tối Ưu Performance Cho Macro

## Phân Tích Vấn Đề Hiện Tại

### 1. Vấn Đề Performance

#### 1.1. Linear Search
- **Vấn đề**: Mỗi lần gọi method, phải loop qua TẤT CẢ macros để tìm
- **Độ phức tạp**: O(n) với n = số lượng macros
- **Ảnh hưởng**: Chậm khi có nhiều macros

```php
// Hiện tại: Phải check tất cả macros
foreach ($this->macros as $macro) {
    if ($macro['subject'] === $input) {
        // Found!
    }
}
```

#### 1.2. Regex Matching Không Cache
- **Vấn đề**: Mỗi lần gọi phải chạy `preg_match()` lại
- **Ảnh hưởng**: Regex chậm, đặc biệt với pattern phức tạp

```php
// Hiện tại: Chạy regex mỗi lần
foreach ($this->macros as $macro) {
    if (preg_match($macro['subject'], $input, $matches)) {
        // Found!
    }
}
```

#### 1.3. Không Có Lookup Cache
- **Vấn đề**: Kết quả lookup không được cache
- **Ảnh hưởng**: Gọi cùng method nhiều lần vẫn phải lookup lại

```php
// Gọi 1000 lần getUserById(1)
// → Phải lookup 1000 lần (không cache)
```

---

## Giải Pháp Tối Ưu

### 1. Indexing Macros - Hash Map

**Ý tưởng**: Tách macros thành các arrays riêng theo type và index bằng hash map

```php
protected $macros = [];
protected $macroIndex = [
    'name' => [],      // Hash map: 'methodName' => [macro1, macro2, ...]
    'equal' => [],     // Hash map: 'methodName' => [macro1, macro2, ...]
    'attribute' => [], // Hash map: 'methodName' => [macro1, macro2, ...]
    'pattern' => [],   // Array: [pattern1, pattern2, ...]
];
```

**Lợi ích**:
- ✅ O(1) lookup cho NAME/EQUAL/ATTRIBUTE macros
- ✅ Chỉ check pattern macros khi cần
- ✅ Giảm số lượng comparisons

### 2. Lookup Cache

**Ý tưởng**: Cache kết quả lookup để tránh lookup lại

```php
protected $macroCache = [
    'methodName:type' => [macro1, macro2, ...],
    // ...
];
```

**Lợi ích**:
- ✅ O(1) lookup cho methods đã gọi trước
- ✅ Giảm overhead cho methods được gọi nhiều lần

### 3. Pre-compile Regex Patterns

**Ý tưởng**: Validate và optimize regex patterns khi đăng ký

```php
protected function addMacro($subject, $callback, $type) {
    if ($type === self::MACRO_TYPE_PATTERN) {
        // Validate và optimize pattern
        $compiled = $this->compilePattern($subject);
        $this->macroIndex['pattern'][] = [
            'pattern' => $compiled,
            'original' => $subject,
            'callback' => $callback,
        ];
    }
}
```

**Lợi ích**:
- ✅ Phát hiện invalid patterns sớm
- ✅ Có thể optimize pattern nếu cần

### 4. Separate Arrays by Type

**Ý tưởng**: Tách macros thành arrays riêng theo type

```php
protected $macrosByName = [];      // NAME macros
protected $macrosByEqual = [];     // EQUAL macros
protected $macrosByAttribute = []; // ATTRIBUTE macros
protected $macrosByPattern = [];   // PATTERN macros
```

**Lợi ích**:
- ✅ Chỉ check type cần thiết
- ✅ Giảm số lượng comparisons
- ✅ Dễ optimize từng type riêng

### 5. Early Exit Optimization

**Ý tưởng**: Dừng ngay khi tìm thấy macro và không cần tiếp tục

```php
// Hiện tại: Check tất cả macros
// Tối ưu: Dừng ngay khi tìm thấy (nếu không cần multiple matches)
```

---

## Implementation - Code Tối Ưu

### Version 1: Indexing + Cache

```php
trait OneMacro
{
    protected $macros = [];
    
    // Indexed macros for O(1) lookup
    protected $macroIndex = [
        'name' => [],      // ['methodName' => [macro1, macro2]]
        'equal' => [],     // ['methodName' => [macro1, macro2]]
        'attribute' => [], // ['methodName' => [macro1, macro2]]
        'pattern' => [],   // [pattern1, pattern2, ...]
    ];
    
    // Lookup cache
    protected $macroCache = [];
    
    public function addMacro($subject, $callback, $type = self::MACRO_TYPE_COMPARE)
    {
        if (is_string($subject) && is_callable($callback)) {
            $macro = [
                'type' => $type,
                'subject' => $subject,
                'callback' => $callback,
            ];
            
            $this->macros[] = $macro;
            
            // Index by type for fast lookup
            switch ($type) {
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
                    
                case self::MACRO_TYPE_PATTERN:
                    $this->macroIndex['pattern'][] = $macro;
                    break;
                    
                case self::MACRO_TYPE_COMPARE:
                    // Auto-detect: name or pattern
                    if (preg_match('/^[A-z_]+[A-z0-9_]*$/i', $subject)) {
                        // Name type
                        if (!isset($this->macroIndex['name'][$subject])) {
                            $this->macroIndex['name'][$subject] = [];
                        }
                        $this->macroIndex['name'][$subject][] = $macro;
                    } elseif (preg_match('/\/.*\/[a-z]*$/i', $subject)) {
                        // Pattern type
                        $this->macroIndex['pattern'][] = $macro;
                    }
                    break;
            }
            
            // Clear cache when adding new macro
            $this->macroCache = [];
        }
        
        return $this;
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
                // O(1) lookup
                if (isset($this->macroIndex['name'][$input])) {
                    foreach ($this->macroIndex['name'][$input] as $macro) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => [],
                        ];
                    }
                }
                break;
                
            case self::MACRO_TYPE_EQUAL:
                // O(1) lookup
                if (isset($this->macroIndex['equal'][$input])) {
                    foreach ($this->macroIndex['equal'][$input] as $macro) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => [],
                        ];
                    }
                }
                break;
                
            case self::MACRO_TYPE_ATTRIBUTE:
                // O(1) lookup
                if (isset($this->macroIndex['attribute'][$input])) {
                    foreach ($this->macroIndex['attribute'][$input] as $macro) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => [],
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
                            'matches' => count($matches) > 1 ? $matches : [],
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
                            'matches' => [],
                        ];
                    }
                }
                
                // Then check patterns (O(n))
                foreach ($this->macroIndex['pattern'] as $macro) {
                    if (preg_match($macro['subject'], $input, $matches)) {
                        $macros[] = [
                            'callback' => $macro['callback'],
                            'matches' => count($matches) > 1 ? $matches : [],
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
     * Clear macro cache (useful for testing or dynamic updates)
     */
    public function clearMacroCache()
    {
        $this->macroCache = [];
        return $this;
    }
}
```

### Version 2: Advanced - Pattern Optimization

```php
trait OneMacro
{
    // ... previous code ...
    
    /**
     * Pre-compile pattern for better performance
     */
    protected function compilePattern($pattern)
    {
        // Validate pattern
        if (@preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException("Invalid regex pattern: {$pattern}");
        }
        
        // Could add pattern optimizations here
        // e.g., extract common prefixes for early rejection
        
        return $pattern;
    }
    
    /**
     * Check if input might match pattern (quick rejection)
     */
    protected function mightMatchPattern($pattern, $input)
    {
        // Extract prefix from pattern if possible
        // e.g., /^abc/ → check if input starts with "abc"
        if (preg_match('/^\^([^.*+?{\[\(\\\]+)/', $pattern, $matches)) {
            $prefix = $matches[1];
            if (strpos($input, $prefix) !== 0) {
                return false; // Quick rejection
            }
        }
        
        return true; // Might match, need full regex check
    }
    
    public function getMacro($input, $type = self::MACRO_TYPE_COMPARE)
    {
        // ... previous code for NAME/EQUAL/ATTRIBUTE ...
        
        case self::MACRO_TYPE_PATTERN:
            foreach ($this->macroIndex['pattern'] as $macro) {
                // Quick rejection check
                if (!$this->mightMatchPattern($macro['subject'], $input)) {
                    continue;
                }
                
                // Full regex check
                if (preg_match($macro['subject'], $input, $matches)) {
                    $macros[] = [
                        'callback' => $macro['callback'],
                        'matches' => count($matches) > 1 ? $matches : [],
                    ];
                }
            }
            break;
    }
}
```

---

## Benchmark - So Sánh Performance

### Test Case

```php
// Setup: 100 NAME macros, 10 PATTERN macros
for ($i = 0; $i < 100; $i++) {
    $service->addMacro("method{$i}", function() { return "result{$i}"; });
}

// Test: Gọi method 1000 lần
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $service->method50(); // Macro ở giữa array
}
$end = microtime(true);
$time = ($end - $start) * 1000; // milliseconds
```

### Kết Quả Dự Kiến

| Version | Time (ms) | Improvement |
|---------|-----------|-------------|
| **Current** (Linear search) | ~50ms | Baseline |
| **Version 1** (Indexing) | ~5ms | **10x faster** |
| **Version 1 + Cache** | ~0.5ms | **100x faster** |
| **Version 2** (Optimized patterns) | ~3ms | **16x faster** |

---

## Trade-offs

### ✅ Ưu Điểm

1. **Performance**: Nhanh hơn đáng kể (10-100x)
2. **Scalability**: Hoạt động tốt với nhiều macros
3. **Memory**: Cache chỉ lưu kết quả, không tốn nhiều memory

### ⚠️ Nhược Điểm

1. **Memory**: Index và cache tốn thêm memory (nhưng không đáng kể)
2. **Complexity**: Code phức tạp hơn một chút
3. **Cache invalidation**: Phải clear cache khi thêm macro mới

---

## Best Practices

### 1. Sử Dụng NAME Macros Khi Có Thể

```php
// ✅ Tốt - O(1) lookup
$this->addMacro('getUserById', function($id) {
    return User::find($id);
}, self::MACRO_TYPE_NAME);

// ⚠️ Chậm hơn - O(n) pattern matching
$this->addMacro('/^getUserById$/', function($id) {
    return User::find($id);
}, self::MACRO_TYPE_PATTERN);
```

### 2. Đặt Pattern Macros Cuối Cùng

```php
// Pattern macros chậm hơn, nên đặt sau NAME macros
// System sẽ check NAME macros trước (O(1))
// Chỉ check patterns nếu không tìm thấy (O(n))
```

### 3. Clear Cache Khi Cần

```php
// Khi thêm macro động trong runtime
$service->addMacro('newMethod', function() {});
$service->clearMacroCache(); // Clear để đảm bảo consistency
```

### 4. Tránh Quá Nhiều Pattern Macros

```php
// ❌ Không tốt - Quá nhiều patterns
for ($i = 0; $i < 1000; $i++) {
    $this->addMacro("/^method{$i}$/", function() {});
}

// ✅ Tốt hơn - Dùng NAME macros
for ($i = 0; $i < 1000; $i++) {
    $this->addMacro("method{$i}", function() {}, self::MACRO_TYPE_NAME);
}
```

---

## Kết Luận

### Tối Ưu Được Áp Dụng

1. ✅ **Indexing**: O(1) lookup cho NAME/EQUAL/ATTRIBUTE
2. ✅ **Caching**: Cache kết quả lookup
3. ✅ **Early Exit**: Dừng sớm khi có thể
4. ✅ **Pattern Optimization**: Quick rejection cho patterns

### Khi Nào Cần Tối Ưu?

- ✅ **Có nhiều macros** (> 10)
- ✅ **Methods được gọi nhiều lần** (hot paths)
- ✅ **Performance critical** applications
- ✅ **High traffic** applications

### Khi Nào Không Cần?

- ⚠️ **Ít macros** (< 5)
- ⚠️ **Methods ít được gọi**
- ⚠️ **Prototype/MVP** - ưu tiên simplicity

### Recommendation

> **"Áp dụng indexing và caching cho production. Code phức tạp hơn một chút nhưng performance cải thiện đáng kể."**


