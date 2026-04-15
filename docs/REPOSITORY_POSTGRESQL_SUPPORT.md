# Hỗ Trợ PostgreSQL trong Repository

## 📋 Tổng Quan

Repository system đã được cập nhật để hỗ trợ đầy đủ cả **MySQL** và **PostgreSQL**, với khả năng tự động chuyển đổi các hàm SQL-specific giữa 2 database.

---

## 🔧 DatabaseHelper Trait

### **Tính Năng**

`DatabaseHelper` trait tự động:
- Phát hiện database driver (MySQL/PostgreSQL)
- Chuyển đổi các hàm SQL-specific
- Cung cấp các helper methods

### **Cách Sử Dụng**

Trait đã được tích hợp tự động vào:
- `BaseQuery`
- `FilterAction`
- `EloquentQuery`

Không cần khai báo thêm, chỉ cần sử dụng các method.

---

## 🔄 Các Hàm Đã Được Chuyển Đổi

### **1. Random Order**

```php
// Tự động chuyển đổi
$repository->randomOrder();
// MySQL: ORDER BY RAND()
// PostgreSQL: ORDER BY RANDOM()

// Hoặc sử dụng
$repository->orderByRaw('rand()'); // Tự động chuyển thành RANDOM() cho PostgreSQL
```

### **2. ILIKE (Case-Insensitive LIKE)**

```php
// PostgreSQL: ILIKE native
// MySQL: Mô phỏng bằng LOWER()
$repository->ilike('name', 'john');
// PostgreSQL: WHERE name ILIKE '%john%'
// MySQL: WHERE LOWER(name) LIKE LOWER('%john%')

$repository->orILike('email', 'test');
```

### **3. Full-Text Search**

```php
// MySQL: MATCH ... AGAINST
// PostgreSQL: to_tsvector ... @@ to_tsquery
$repository->whereFullText(['title', 'content'], 'search term');
// MySQL: MATCH(title,content) AGAINST(? IN BOOLEAN MODE)
// PostgreSQL: to_tsvector('simple', title || ' ' || content) @@ to_tsquery('simple', ?)

$repository->orWhereFullText('description', 'keyword');
```

### **4. CONCAT**

```php
// Sử dụng helper
$concat = $this->getConcatFunction('first_name', ' ', 'last_name');
// MySQL: CONCAT(first_name, ' ', last_name)
// PostgreSQL: first_name || ' ' || last_name
```

### **5. IFNULL / COALESCE**

```php
// Sử dụng helper
$ifnull = $this->getIfNullFunction('column', 'default_value');
// MySQL: IFNULL(column, 'default_value')
// PostgreSQL: COALESCE(column, 'default_value')
```

### **6. DATE_FORMAT / TO_CHAR**

```php
// Sử dụng helper
$dateFormat = $this->getDateFormatFunction('created_at', '%Y-%m-%d');
// MySQL: DATE_FORMAT(created_at, '%Y-%m-%d')
// PostgreSQL: TO_CHAR(created_at, 'YYYY-MM-DD')
```

### **7. GROUP_CONCAT / STRING_AGG**

```php
// Sử dụng helper
$agg = $this->getStringAggFunction('name', ',');
// MySQL: GROUP_CONCAT(name SEPARATOR ',')
// PostgreSQL: STRING_AGG(name, ',')
```

### **8. REGEXP**

```php
// Sử dụng helper
$operator = $this->getRegexpOperator(true); // case-insensitive
// MySQL: REGEXP
// PostgreSQL: ~* (case-insensitive) hoặc ~ (case-sensitive)
```

### **9. DATE Functions**

```php
// DATE_ADD
$dateAdd = $this->getDateAddFunction('created_at', 7, 'DAY');
// MySQL: DATE_ADD(created_at, INTERVAL 7 DAY)
// PostgreSQL: created_at + INTERVAL '7 DAY'

// DATE_SUB
$dateSub = $this->getDateSubFunction('created_at', 1, 'MONTH');
// MySQL: DATE_SUB(created_at, INTERVAL 1 MONTH)
// PostgreSQL: created_at - INTERVAL '1 MONTH'

// DATEDIFF
$diff = $this->getDateDiffFunction('date1', 'date2');
// MySQL: DATEDIFF(date1, date2)
// PostgreSQL: (date1::date - date2::date)

// TIMESTAMPDIFF
$tsDiff = $this->getTimestampDiffFunction('HOUR', 'date1', 'date2');
// MySQL: TIMESTAMPDIFF(HOUR, date1, date2)
// PostgreSQL: EXTRACT(EPOCH FROM (date2 - date1)) / 3600
```

### **10. Current Date/Time**

```php
// NOW()
$now = $this->getNowFunction(); // Cả 2 đều hỗ trợ NOW()

// CURDATE()
$date = $this->getCurrentDateFunction();
// MySQL: CURDATE()
// PostgreSQL: CURRENT_DATE

// CURTIME()
$time = $this->getCurrentTimeFunction();
// MySQL: CURTIME()
// PostgreSQL: CURRENT_TIME
```

### **11. CAST**

```php
// Sử dụng helper
$cast = $this->getCastFunction('column', 'VARCHAR');
// MySQL: CAST(column AS VARCHAR)
// PostgreSQL: column::VARCHAR
```

### **12. LENGTH**

```php
// Sử dụng helper
$length = $this->getLengthFunction('column', false); // characters
// MySQL: CHAR_LENGTH(column)
// PostgreSQL: LENGTH(column)

$bytes = $this->getLengthFunction('column', true); // bytes
// MySQL: LENGTH(column)
// PostgreSQL: OCTET_LENGTH(column)
```

---

## 📝 Danh Sách Đầy Đủ Các Hàm Hỗ Trợ

### **Query Methods**

| Method | MySQL | PostgreSQL | Status |
|--------|-------|------------|--------|
| `where()` | ✅ | ✅ | Native |
| `whereIn()` | ✅ | ✅ | Native |
| `whereBetween()` | ✅ | ✅ | Native |
| `whereDate()` | ✅ | ✅ | Native |
| `whereTime()` | ✅ | ✅ | Native |
| `whereNull()` | ✅ | ✅ | Native |
| `whereJsonContains()` | ✅ | ✅ | Native |
| `whereFullText()` | ✅ | ✅ | Auto-convert |
| `ilike()` | ✅ | ✅ | Auto-convert |
| `orILike()` | ✅ | ✅ | Auto-convert |
| `randomOrder()` | ✅ | ✅ | Auto-convert |
| `orderByRaw('rand()')` | ✅ | ✅ | Auto-convert |

### **SQL Functions (Helper Methods)**

| Function | MySQL | PostgreSQL | Helper Method |
|----------|-------|------------|---------------|
| Random | `RAND()` | `RANDOM()` | `getRandomFunction()` |
| Concat | `CONCAT()` | `\|\|` | `getConcatFunction()` |
| IfNull | `IFNULL()` | `COALESCE()` | `getIfNullFunction()` |
| DateFormat | `DATE_FORMAT()` | `TO_CHAR()` | `getDateFormatFunction()` |
| GroupConcat | `GROUP_CONCAT()` | `STRING_AGG()` | `getStringAggFunction()` |
| Regexp | `REGEXP` | `~` / `~*` | `getRegexpOperator()` |
| DateAdd | `DATE_ADD()` | `+ INTERVAL` | `getDateAddFunction()` |
| DateSub | `DATE_SUB()` | `- INTERVAL` | `getDateSubFunction()` |
| DateDiff | `DATEDIFF()` | `::date - ::date` | `getDateDiffFunction()` |
| TimestampDiff | `TIMESTAMPDIFF()` | `EXTRACT(EPOCH)` | `getTimestampDiffFunction()` |
| CurDate | `CURDATE()` | `CURRENT_DATE` | `getCurrentDateFunction()` |
| CurTime | `CURTIME()` | `CURRENT_TIME` | `getCurrentTimeFunction()` |
| Cast | `CAST()` | `::type` | `getCastFunction()` |
| Length | `CHAR_LENGTH()` / `LENGTH()` | `LENGTH()` / `OCTET_LENGTH()` | `getLengthFunction()` |
| FullText | `MATCH() AGAINST()` | `to_tsvector() @@ to_tsquery()` | `getFullTextSearch()` |

---

## 🎯 Ví Dụ Sử Dụng

### **Ví Dụ 1: Random Order**

```php
class UserRepository extends BaseRepository
{
    public function getRandomUsers($limit = 10)
    {
        return $this->randomOrder()
                    ->take($limit)
                    ->get();
    }
}
```

### **Ví Dụ 2: Case-Insensitive Search**

```php
public function searchUsers($keyword)
{
    return $this->ilike('name', $keyword)
                 ->orILike('email', $keyword)
                 ->get();
}
```

### **Ví Dụ 3: Full-Text Search**

```php
public function searchArticles($term)
{
    return $this->whereFullText(['title', 'content'], $term)
                ->get();
}
```

### **Ví Dụ 4: Sử Dụng Helper Functions**

```php
public function getUserStats()
{
    // Sử dụng helper trong selectRaw
    $concat = $this->getConcatFunction('first_name', ' ', 'last_name');
    $ifnull = $this->getIfNullFunction('avatar', "'default.jpg'");
    
    return $this->selectRaw("{$concat} as full_name, {$ifnull} as avatar_url")
                ->get();
}
```

### **Ví Dụ 5: Date Functions**

```php
public function getRecentUsers($days = 7)
{
    $dateAdd = $this->getDateAddFunction('created_at', $days, 'DAY');
    
    return $this->whereRaw("{$dateAdd} >= NOW()")
                ->get();
}
```

---

## 🔍 Detection Methods

### **Kiểm Tra Database Driver**

```php
// Kiểm tra driver
$driver = $this->getDatabaseDriver(); // 'mysql' | 'pgsql' | 'sqlite' | 'sqlsrv'

// Kiểm tra cụ thể
if ($this->isPostgreSQL()) {
    // Logic cho PostgreSQL
}

if ($this->isMySQL()) {
    // Logic cho MySQL
}
```

---

## ⚠️ Lưu Ý Quan Trọng

### **1. RAND() / RANDOM()**

```php
// ✅ Đúng - Tự động chuyển đổi
$repository->randomOrder();
$repository->orderByRaw('rand()'); // Tự động thành RANDOM() cho PostgreSQL

// ❌ Sai - Hardcode
$repository->orderByRaw('RAND()'); // Sẽ lỗi trên PostgreSQL
```

### **2. ILIKE**

```php
// ✅ Đúng - Tự động chuyển đổi
$repository->ilike('name', 'john');

// ❌ Sai - Hardcode
$repository->where('name', 'ilike', 'john'); // MySQL không hỗ trợ ILIKE
```

### **3. Full-Text Search**

```php
// ✅ Đúng - Tự động chuyển đổi
$repository->whereFullText(['title', 'content'], 'search');

// ❌ Sai - Hardcode
$repository->whereRaw("MATCH(title,content) AGAINST(?)", ['search']); // Sẽ lỗi trên PostgreSQL
```

### **4. CONCAT**

```php
// ✅ Đúng - Sử dụng helper
$concat = $this->getConcatFunction('first', ' ', 'last');
$this->selectRaw("{$concat} as full_name");

// ❌ Sai - Hardcode
$this->selectRaw("CONCAT(first, ' ', last)"); // PostgreSQL không hỗ trợ CONCAT()
```

---

## 📊 So Sánh MySQL vs PostgreSQL

### **Functions Tương Đương**

| MySQL | PostgreSQL | Notes |
|-------|------------|-------|
| `RAND()` | `RANDOM()` | Random number |
| `CONCAT(a, b)` | `a \|\| b` | String concatenation |
| `IFNULL(a, b)` | `COALESCE(a, b)` | Null handling |
| `DATE_FORMAT(d, f)` | `TO_CHAR(d, f)` | Date formatting |
| `GROUP_CONCAT(c)` | `STRING_AGG(c, ',')` | String aggregation |
| `REGEXP` | `~` / `~*` | Regular expression |
| `DATE_ADD(d, i)` | `d + INTERVAL i` | Date addition |
| `DATEDIFF(d1, d2)` | `d1::date - d2::date` | Date difference |
| `CURDATE()` | `CURRENT_DATE` | Current date |
| `CURTIME()` | `CURRENT_TIME` | Current time |
| `CHAR_LENGTH(s)` | `LENGTH(s)` | String length |
| `LENGTH(s)` | `OCTET_LENGTH(s)` | Byte length |
| `MATCH() AGAINST()` | `to_tsvector() @@ to_tsquery()` | Full-text search |
| `LIKE` (case-sensitive) | `LIKE` | Case-sensitive |
| N/A | `ILIKE` | Case-insensitive (MySQL dùng `LOWER()`) |

---

## 🚀 Migration Guide

### **Từ MySQL Sang PostgreSQL**

1. **Không cần thay đổi code** - Tất cả đã tự động chuyển đổi
2. **Chỉ cần đổi connection** trong config:

```php
// config/database.php
'connections' => [
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        // ...
    ],
],
```

3. **Model sử dụng connection mới**:

```php
class User extends Model
{
    protected $connection = 'pgsql';
}
```

---

## ✅ Checklist

- [x] DatabaseHelper trait đã được tạo
- [x] Tự động detect database driver
- [x] RAND() → RANDOM() tự động chuyển đổi
- [x] ILIKE tự động chuyển đổi
- [x] Full-text search tự động chuyển đổi
- [x] Tất cả helper functions đã được implement
- [x] whereFullText() và orWhereFullText() đã được thêm
- [x] randomOrder() đã được thêm
- [x] Tài liệu đã được tạo

---

## 📚 Tài Liệu Liên Quan

- [BASE_REPOSITORY_QUERY_METHODS.md](./BASE_REPOSITORY_QUERY_METHODS.md) - Các method query
- [REPOSITORY_TAP_IMPROVEMENTS.md](./REPOSITORY_TAP_IMPROVEMENTS.md) - RepositoryTap

---

**Cập nhật:** 2025-01-XX

