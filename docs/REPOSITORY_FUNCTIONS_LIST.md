# Danh Sách Đầy Đủ Các Hàm Repository

## 📋 Tổng Quan

Tài liệu này liệt kê tất cả các hàm có sẵn trong Repository system, bao gồm cả hỗ trợ MySQL và PostgreSQL.

---

## 🔍 Query Methods

### **SELECT**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `select($columns)` | Chọn cột | ✅ | ✅ |
| `selectRaw($sql)` | Chọn với SQL raw | ✅ | ✅ |
| `addSelect($column)` | Thêm cột vào SELECT | ✅ | ✅ |
| `addSelectRaw($sql)` | Thêm SQL raw vào SELECT | ✅ | ✅ |
| `distinct()` | Loại bỏ duplicate | ✅ | ✅ |

### **FROM**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `from($table)` | Chỉ định bảng | ✅ | ✅ |
| `fromRaw($sql)` | FROM với SQL raw | ✅ | ✅ |

### **JOIN**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `join($table, $first, $operator, $second)` | INNER JOIN | ✅ | ✅ |
| `leftJoin($table, $first, $operator, $second)` | LEFT JOIN | ✅ | ✅ |
| `rightJoin($table, $first, $operator, $second)` | RIGHT JOIN | ✅ | ✅ |
| `crossJoin($table)` | CROSS JOIN | ✅ | ✅ |
| `joinRaw($sql)` | JOIN với SQL raw | ✅ | ✅ |
| `leftJoinRaw($sql)` | LEFT JOIN với SQL raw | ✅ | ✅ |
| `rightJoinRaw($sql)` | RIGHT JOIN với SQL raw | ✅ | ✅ |

### **WHERE**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `where($column, $operator, $value)` | Điều kiện WHERE | ✅ | ✅ |
| `whereNot($column, $operator, $value)` | WHERE NOT | ✅ | ✅ |
| `whereRaw($sql, $bindings)` | WHERE với SQL raw | ✅ | ✅ |
| `whereIn($column, $values)` | WHERE IN | ✅ | ✅ |
| `whereNotIn($column, $values)` | WHERE NOT IN | ✅ | ✅ |
| `whereBetween($column, $values)` | WHERE BETWEEN | ✅ | ✅ |
| `whereNotBetween($column, $values)` | WHERE NOT BETWEEN | ✅ | ✅ |
| `whereNull($column)` | WHERE IS NULL | ✅ | ✅ |
| `whereNotNull($column)` | WHERE IS NOT NULL | ✅ | ✅ |
| `whereColumn($first, $operator, $second)` | So sánh 2 cột | ✅ | ✅ |
| `whereDate($column, $operator, $value)` | So sánh DATE | ✅ | ✅ |
| `whereTime($column, $operator, $value)` | So sánh TIME | ✅ | ✅ |
| `whereDay($column, $value)` | So sánh DAY | ✅ | ✅ |
| `whereMonth($column, $value)` | So sánh MONTH | ✅ | ✅ |
| `whereYear($column, $value)` | So sánh YEAR | ✅ | ✅ |
| `whereJsonContains($column, $value)` | JSON contains | ✅ | ✅ |
| `whereJsonLength($column, $operator, $length)` | JSON length | ✅ | ✅ |
| `whereExists($callback)` | WHERE EXISTS | ✅ | ✅ |
| `whereNotExists($callback)` | WHERE NOT EXISTS | ✅ | ✅ |
| `whereIntegerInRaw($column, $values)` | WHERE IN với integers | ✅ | ✅ |
| `whereIntegerNotInRaw($column, $values)` | WHERE NOT IN với integers | ✅ | ✅ |
| `whereFullText($columns, $value)` | Full-text search | ✅ | ✅ (Auto-convert) |
| `whereRowNum($rowNumber, $orderBy)` | Row number filter | ✅ | ✅ |
| `like($column, $value)` | LIKE search | ✅ | ✅ |
| `ilike($column, $value)` | Case-insensitive LIKE | ✅ (LOWER) | ✅ (Native) |
| `orILike($column, $value)` | OR ILIKE | ✅ (LOWER) | ✅ (Native) |

### **OR WHERE**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `orWhere($column, $operator, $value)` | OR WHERE | ✅ | ✅ |
| `orWhereNot($column, $operator, $value)` | OR WHERE NOT | ✅ | ✅ |
| `orWhereRaw($sql, $bindings)` | OR WHERE raw | ✅ | ✅ |
| `orWhereIn($column, $values)` | OR WHERE IN | ✅ | ✅ |
| `orWhereNotIn($column, $values)` | OR WHERE NOT IN | ✅ | ✅ |
| `orWhereBetween($column, $values)` | OR WHERE BETWEEN | ✅ | ✅ |
| `orWhereNotBetween($column, $values)` | OR WHERE NOT BETWEEN | ✅ | ✅ |
| `orWhereNull($column)` | OR WHERE IS NULL | ✅ | ✅ |
| `orWhereNotNull($column)` | OR WHERE IS NOT NULL | ✅ | ✅ |
| `orWhereColumn($first, $operator, $second)` | OR so sánh 2 cột | ✅ | ✅ |
| `orWhereDate($column, $operator, $value)` | OR so sánh DATE | ✅ | ✅ |
| `orWhereTime($column, $operator, $value)` | OR so sánh TIME | ✅ | ✅ |
| `orWhereDay($column, $value)` | OR so sánh DAY | ✅ | ✅ |
| `orWhereMonth($column, $value)` | OR so sánh MONTH | ✅ | ✅ |
| `orWhereYear($column, $value)` | OR so sánh YEAR | ✅ | ✅ |
| `orWhereJsonContains($column, $value)` | OR JSON contains | ✅ | ✅ |
| `orWhereJsonLength($column, $operator, $length)` | OR JSON length | ✅ | ✅ |
| `orWhereExists($callback)` | OR WHERE EXISTS | ✅ | ✅ |
| `orWhereNotExists($callback)` | OR WHERE NOT EXISTS | ✅ | ✅ |
| `orWhereFullText($columns, $value)` | OR Full-text search | ✅ | ✅ (Auto-convert) |

### **GROUP BY**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `groupBy($columns)` | GROUP BY | ✅ | ✅ |
| `groupByRaw($sql)` | GROUP BY raw | ✅ | ✅ |

### **HAVING**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `having($column, $operator, $value)` | HAVING | ✅ | ✅ |
| `havingRaw($sql, $bindings)` | HAVING raw | ✅ | ✅ |
| `havingBetween($column, $values)` | HAVING BETWEEN | ✅ | ✅ |
| `orHaving($column, $operator, $value)` | OR HAVING | ✅ | ✅ |
| `orHavingRaw($sql, $bindings)` | OR HAVING raw | ✅ | ✅ |

### **ORDER BY**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `orderBy($column, $direction)` | ORDER BY | ✅ | ✅ |
| `orderByRaw($sql)` | ORDER BY raw | ✅ | ✅ |
| `orderByDesc($column)` | ORDER BY DESC | ✅ | ✅ |
| `latest($column)` | ORDER BY DESC (mới nhất) | ✅ | ✅ |
| `oldest($column)` | ORDER BY ASC (cũ nhất) | ✅ | ✅ |
| `inRandomOrder($seed)` | Sắp xếp ngẫu nhiên | ✅ | ✅ |
| `randomOrder()` | Sắp xếp ngẫu nhiên | ✅ (RAND) | ✅ (RANDOM) |
| `reorder($column, $direction)` | Reset và ORDER BY mới | ✅ | ✅ |

### **LIMIT / OFFSET**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `skip($count)` | OFFSET | ✅ | ✅ |
| `take($count)` | LIMIT | ✅ | ✅ |
| `limit($start, $length)` | LIMIT với offset | ✅ | ✅ |
| `offset($count)` | OFFSET | ✅ | ✅ |
| `forPage($page, $perPage)` | Phân trang | ✅ | ✅ |

### **UNION**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `union($query)` | UNION | ✅ | ✅ |
| `unionAll($query)` | UNION ALL | ✅ | ✅ |

### **EAGER LOADING**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `with($relations)` | Eager load relations | ✅ | ✅ |
| `without($relations)` | Exclude relations | ✅ | ✅ |
| `load($relations)` | Load relations | ✅ | ✅ |
| `withCount($relations)` | Count relations | ✅ | ✅ |
| `withAvg($relation, $column)` | AVG của relation | ✅ | ✅ |
| `withSum($relation, $column)` | SUM của relation | ✅ | ✅ |
| `withMin($relation, $column)` | MIN của relation | ✅ | ✅ |
| `withMax($relation, $column)` | MAX của relation | ✅ | ✅ |
| `withExists($relation)` | EXISTS relation | ✅ | ✅ |

### **CONDITIONAL**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `when($value, $callback)` | Conditional clause | ✅ | ✅ |
| `unless($value, $callback)` | Negative conditional | ✅ | ✅ |
| `tap($callback)` | Tap into query | ✅ | ✅ |

### **LOCKS**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `lockForShare()` | SELECT ... FOR SHARE | ✅ | ✅ |
| `lockForUpdate()` | SELECT ... FOR UPDATE | ✅ | ✅ |

### **CTE (Common Table Expression)**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `withCTE($name, $query)` | WITH clause | ✅ (8.0+) | ✅ |
| `withRecursive($name, $query)` | WITH RECURSIVE | ✅ (8.0+) | ✅ |

### **WINDOW FUNCTIONS**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `window($name, $callback)` | Window function | ✅ (8.0+) | ✅ |

---

## 🔧 Helper Functions (DatabaseHelper)

### **String Functions**

| Method | MySQL | PostgreSQL | Mô Tả |
|--------|-------|------------|-------|
| `getConcatFunction(...$strings)` | `CONCAT()` | `\|\|` | Nối chuỗi |
| `getLengthFunction($expr, $bytes)` | `CHAR_LENGTH()` / `LENGTH()` | `LENGTH()` / `OCTET_LENGTH()` | Độ dài chuỗi |
| `getStringAggFunction($column, $sep)` | `GROUP_CONCAT()` | `STRING_AGG()` | Aggregate strings |

### **Null Handling**

| Method | MySQL | PostgreSQL | Mô Tả |
|--------|-------|------------|-------|
| `getIfNullFunction($expr1, $expr2)` | `IFNULL()` | `COALESCE()` | Xử lý NULL |

### **Date/Time Functions**

| Method | MySQL | PostgreSQL | Mô Tả |
|--------|-------|------------|-------|
| `getDateFormatFunction($date, $format)` | `DATE_FORMAT()` | `TO_CHAR()` | Format date |
| `getDateAddFunction($date, $value, $unit)` | `DATE_ADD()` | `+ INTERVAL` | Thêm thời gian |
| `getDateSubFunction($date, $value, $unit)` | `DATE_SUB()` | `- INTERVAL` | Trừ thời gian |
| `getDateDiffFunction($date1, $date2)` | `DATEDIFF()` | `::date - ::date` | Chênh lệch ngày |
| `getTimestampDiffFunction($unit, $date1, $date2)` | `TIMESTAMPDIFF()` | `EXTRACT(EPOCH)` | Chênh lệch timestamp |
| `getCurrentDateFunction()` | `CURDATE()` | `CURRENT_DATE` | Ngày hiện tại |
| `getCurrentTimeFunction()` | `CURTIME()` | `CURRENT_TIME` | Giờ hiện tại |
| `getNowFunction()` | `NOW()` | `NOW()` | Timestamp hiện tại |

### **Random**

| Method | MySQL | PostgreSQL | Mô Tả |
|--------|-------|------------|-------|
| `getRandomFunction()` | `RAND()` | `RANDOM()` | Random number |

### **Full-Text Search**

| Method | MySQL | PostgreSQL | Mô Tả |
|--------|-------|------------|-------|
| `getFullTextSearch($columns, $search)` | `MATCH() AGAINST()` | `to_tsvector() @@ to_tsquery()` | Full-text search |

### **Regular Expression**

| Method | MySQL | PostgreSQL | Mô Tả |
|--------|-------|------------|-------|
| `getRegexpOperator($caseInsensitive)` | `REGEXP` | `~` / `~*` | Regex operator |

### **Type Casting**

| Method | MySQL | PostgreSQL | Mô Tả |
|--------|-------|------------|-------|
| `getCastFunction($expr, $type)` | `CAST()` | `::type` | Type casting |

---

## 📊 CRUD Methods

### **Read**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `get()` | Lấy tất cả | ✅ | ✅ |
| `first()` | Lấy record đầu tiên | ✅ | ✅ |
| `find($id)` | Tìm theo ID | ✅ | ✅ |
| `findOrFail($id)` | Tìm hoặc throw exception | ✅ | ✅ |
| `count()` | Đếm số lượng | ✅ | ✅ |
| `exists()` | Kiểm tra tồn tại | ✅ | ✅ |
| `getResults($request, $args)` | Lấy kết quả với filter | ✅ | ✅ |
| `detail($id)` | Chi tiết record | ✅ | ✅ |
| `getTrashedResults($request, $args)` | Lấy records đã xóa | ✅ | ✅ |

### **Create**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `create($data)` | Tạo mới | ✅ | ✅ |
| `createMany($data)` | Tạo nhiều | ✅ | ✅ |

### **Update**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `update($id, $data)` | Cập nhật | ✅ | ✅ |

### **Delete**

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `delete($id)` | Xóa (soft delete nếu có) | ✅ | ✅ |
| `erase($id)` | Xóa vĩnh viễn | ✅ | ✅ |
| `moveToTrash($id)` | Chuyển vào trash | ✅ | ✅ |
| `restoreFromTrash($id)` | Khôi phục từ trash | ✅ | ✅ |

---

## 🎯 Filter Methods

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `filter($request, $args)` | Filter với request | ✅ | ✅ |
| `search($keyword, $fields)` | Tìm kiếm | ✅ | ✅ |
| `sortBy($column, $direction)` | Sắp xếp | ✅ | ✅ |

---

## 🔐 Security Methods

| Method | Mô Tả | MySQL | PostgreSQL |
|--------|-------|-------|------------|
| `notTrashed()` | Chỉ lấy chưa xóa | ✅ | ✅ |
| `trashed()` | Chỉ lấy đã xóa | ✅ | ✅ |
| `withTrashed()` | Bao gồm cả đã xóa | ✅ | ✅ |

---

## 📝 Notes

- ✅ **Native**: Hỗ trợ native bởi cả 2 database
- ✅ **(Auto-convert)**: Tự động chuyển đổi giữa MySQL và PostgreSQL
- ✅ **(LOWER)**: MySQL mô phỏng bằng LOWER()
- ✅ **(Native)**: PostgreSQL hỗ trợ native

---

## 🚀 Sử Dụng

Tất cả các hàm đều hoạt động tự động với cả MySQL và PostgreSQL. Chỉ cần sử dụng như bình thường:

```php
$repository->where('status', 'active')
           ->ilike('name', 'john')
           ->randomOrder()
           ->take(10)
           ->get();
```

Repository sẽ tự động chuyển đổi các hàm SQL-specific cho database đang sử dụng.

---

**Cập nhật:** 2025-01-XX

