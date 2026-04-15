# Báo Cáo Chi Tiết: Các Hàm Public Truy Vấn Dữ Liệu - BaseRepository

## Tổng Quan

`BaseRepository` là class cơ sở cung cấp các phương thức truy vấn dữ liệu mạnh mẽ cho Laravel. Class này sử dụng nhiều trait để tổ chức các chức năng:

- **BaseQuery**: Xây dựng query builder
- **GettingAction**: Lấy dữ liệu từ database
- **FilterAction**: Lọc và filter dữ liệu
- **CRUDAction**: Thao tác CRUD
- **DataAction**: Xử lý dữ liệu
- **OwnerAction**: Quản lý owner
- **CacheAction**: Cache dữ liệu
- **FileAction**: Xử lý file

---

## 📋 DANH SÁCH CÁC HÀM PUBLIC TRUY VẤN DỮ LIỆU

### 1. NHÓM LẤY DỮ LIỆU CƠ BẢN (GettingAction)

#### 1.1. `getAll()`
**Mô tả**: Lấy tất cả bản ghi từ database  
**Tham số**: Không  
**Trả về**: `Model[]` - Mảng các model  
**Sự kiện**: `beforegetAll`, `aftergetAll`  
**Đặc biệt**: Tự động check multi-language contents nếu model hỗ trợ

```php
$allUsers = $repository->getAll();
```

#### 1.2. `find($id)`
**Mô tả**: Tìm một bản ghi theo ID  
**Tham số**: 
- `$id` (int|string): ID của bản ghi

**Trả về**: `Model|null` - Model hoặc null nếu không tìm thấy

```php
$user = $repository->find(1);
```

#### 1.3. `findBy($prop, $value)`
**Mô tả**: Tìm một bản ghi theo thuộc tính  
**Tham số**: 
- `$prop` (string): Tên cột, mặc định 'name'
- `$value` (mixed): Giá trị cần tìm

**Trả về**: `Model|null`  
**Sự kiện**: `beforfindBy`, `afterfindBy`

```php
$user = $repository->findBy('email', 'user@example.com');
```

#### 1.4. `getBy($prop, $value)`
**Mô tả**: Lấy nhiều bản ghi theo thuộc tính  
**Tham số**: 
- `$prop` (string): Tên cột, mặc định 'name'
- `$value` (mixed): Giá trị cần tìm

**Trả về**: `Model[]` - Mảng các model  
**Sự kiện**: `beforgetBy`, `aftergetBy`

```php
$users = $repository->getBy('status', 'active');
```

#### 1.5. `get($args = [])`
**Mô tả**: Lấy dữ liệu với các điều kiện phức tạp  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `Model[]|LengthAwarePaginator|MaskCollection`  
**Sự kiện**: `prepareget`, `beforeget`, `afterget`  
**Đặc biệt**: 
- Hỗ trợ phân trang nếu có `@paginate` trong args
- Hỗ trợ limit nếu có `@limit` trong args
- Tự động check multi-language contents
- Cập nhật `totalCount` sau khi lấy dữ liệu

**Ví dụ tham số**:
```php
$results = $repository->get([
    'status' => 'active',
    'age' => ['>', 18],
    '@search' => ['keywords' => 'john', 'by' => ['name', 'email']],
    '@order_by' => 'created_at-DESC',
    '@paginate' => 20,
    '@limit' => [0, 10]
]);
```

#### 1.6. `getOnly($args = [])`
**Mô tả**: Lấy dữ liệu nhưng không đếm tổng số (nhanh hơn)  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `Model[]|LengthAwarePaginator`  
**Đặc biệt**: Không cập nhật `totalCount`, phù hợp khi chỉ cần lấy dữ liệu

#### 1.7. `first($args = [])`
**Mô tả**: Lấy bản ghi đầu tiên thỏa mãn điều kiện  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `Model|null`  
**Sự kiện**: `preparefirst`, `beforefirst`, `afterfirst`  
**Đặc biệt**: Tự động check multi-language contents

```php
$user = $repository->first(['email' => 'user@example.com']);
```

#### 1.8. `exists(...$args)`
**Mô tả**: Kiểm tra bản ghi có tồn tại không  
**Tham số**: 
- `...$args`: Các tham số truy vấn

**Trả về**: `bool`  
**Đặc biệt**: 
- Nếu 1 tham số: kiểm tra theo primary key
- Nếu nhiều tham số: kiểm tra theo `countBy()`

```php
if ($repository->exists(1)) { ... }
if ($repository->exists('email', 'user@example.com')) { ... }
```

#### 1.9. `checkExists($id)`
**Mô tả**: Static method kiểm tra tồn tại  
**Tham số**: 
- `$id` (int|string|array): ID hoặc mảng điều kiện

**Trả về**: `bool`

```php
if (UserRepository::checkExists(1)) { ... }
```

---

### 2. NHÓM ĐẾM VÀ TÍNH TOÁN (GettingAction)

#### 2.1. `count($args = [])`
**Mô tả**: Đếm số bản ghi thỏa mãn điều kiện  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `int`  
**Sự kiện**: `prepareCount`, `beforeCount`  
**Đặc biệt**: Tự động loại bỏ `@paginate` và `@limit` khỏi args

```php
$total = $repository->count(['status' => 'active']);
```

#### 2.2. `countBy($prop, $value)`
**Mô tả**: Đếm số bản ghi theo thuộc tính  
**Tham số**: 
- `$prop` (string): Tên cột, mặc định 'name'
- `$value` (mixed): Giá trị cần đếm

**Trả về**: `int`

```php
$count = $repository->countBy('status', 'active');
```

#### 2.3. `countLast()`
**Mô tả**: Đếm số bản ghi với tham số lần truy vấn gần nhất  
**Tham số**: Không  
**Trả về**: `int`  
**Đặc biệt**: Sử dụng `lastParams` từ lần truy vấn trước

#### 2.4. `sum($column, $args = [])`
**Mô tả**: Tính tổng giá trị của một cột  
**Tham số**: 
- `$column` (string): Tên cột cần tính tổng
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `int|float`

```php
$totalPrice = $repository->sum('price', ['status' => 'active']);
```

#### 2.5. `avg($column, $args = [])`
**Mô tả**: Tính trung bình giá trị của một cột  
**Tham số**: 
- `$column` (string): Tên cột cần tính trung bình
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `int|float`

```php
$avgPrice = $repository->avg('price', ['status' => 'active']);
```

#### 2.6. `total()`
**Mô tả**: Lấy tổng số bản ghi từ lần truy vấn gần nhất  
**Tham số**: Không  
**Trả về**: `int`  
**Đặc biệt**: Trả về giá trị `totalCount` được cập nhật sau `get()`

---

### 3. NHÓM LẤY DỮ LIỆU OPTIONS (GettingAction)

#### 3.1. `getDataOptions($args, $defaultFirst, $valueKey, $textKey)`
**Mô tả**: Lấy dữ liệu dạng options cho select/dropdown  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn
- `$defaultFirst` (string|null): Text hiển thị cho option đầu tiên (rỗng)
- `$valueKey` (string): Tên cột làm giá trị, mặc định primary key
- `$textKey` (string): Tên cột hiển thị, mặc định 'name'

**Trả về**: `array` - Mảng key-value  
**Sự kiện**: `beforegetDataOptions`  
**Đặc biệt**: 
- Hỗ trợ template trong `$textKey` với `{$field}`
- Tự động lọc các giá trị rỗng

```php
$options = $repository->getDataOptions(
    ['status' => 'active'],
    '-- Chọn --',
    'id',
    'name'
);
// Kết quả: ['1' => 'User 1', '2' => 'User 2', ...]
```

#### 3.2. `getRequestDataOptions($request, $args, $defaultFirst, $valueKey, $textKey)`
**Mô tả**: Lấy dữ liệu options từ request  
**Tham số**: 
- `$request` (Request): HTTP Request object
- `$args` (array): Mảng các tham số truy vấn bổ sung
- `$defaultFirst` (string|null): Text hiển thị cho option đầu tiên
- `$valueKey` (string): Tên cột làm giá trị
- `$textKey` (string): Tên cột hiển thị

**Trả về**: `array`  
**Sự kiện**: `beforegetRequestDataOptions`, `aftergetRequestDataOptions`  
**Đặc biệt**: 
- Tự động build filter từ request
- Hỗ trợ `ignore` parameter để loại trừ một số giá trị
- Tự động merge paginate args

---

### 4. NHÓM CHUNK (GettingAction)

#### 4.1. `chunk($callback, $count = 1000)`
**Mô tả**: Xử lý dữ liệu theo từng batch để tránh quá tải memory  
**Tham số**: 
- `$callback` (callable): Hàm callback xử lý mỗi batch
- `$count` (int): Số lượng bản ghi mỗi batch, mặc định 1000

**Trả về**: `void`  
**Đặc biệt**: Tự động check multi-language contents

```php
$repository->chunk(function ($users) {
    foreach ($users as $user) {
        // Xử lý từng user
    }
}, 500);
```

#### 4.2. `chunkById($callback, $count = 1000, $column = null, $alias = null)`
**Mô tả**: Xử lý dữ liệu theo batch sử dụng ID  
**Tham số**: 
- `$callback` (callable): Hàm callback
- `$count` (int): Số lượng bản ghi mỗi batch
- `$column` (string|null): Tên cột ID, mặc định primary key
- `$alias` (string|null): Alias cho cột

**Trả về**: `void`  
**Đặc biệt**: Hiệu quả hơn `chunk()` với dữ liệu lớn

---

### 5. NHÓM FILTER VÀ LỌC DỮ LIỆU (FilterAction)

#### 5.1. `filter($request, $args = [])`
**Mô tả**: Lọc dữ liệu từ request và trả về collection đã parse  
**Tham số**: 
- `$request` (Request): HTTP Request object
- `$args` (array): Mảng các tham số truy vấn bổ sung

**Trả về**: `MaskCollection|ResourceCollection|Collection`  
**Sự kiện**: `beforefilter`, `afterfilter`  
**Đặc biệt**: 
- Tự động parse collection theo `responseMode` (mask/resource/default)
- Tự động build filter từ request

```php
$results = $repository->filter($request, ['status' => 'active']);
```

#### 5.2. `getFilter($request, $args = [])`
**Mô tả**: Lọc dữ liệu từ request (không parse collection)  
**Tham số**: 
- `$request` (Request): HTTP Request object
- `$args` (array): Mảng các tham số truy vấn bổ sung

**Trả về**: `LengthAwarePaginator|Collection`  
**Sự kiện**: `preparegetFilter`, `beforegetFilter`, `aftergetFilter`  
**Đặc biệt**: 
- Tự động apply default sort nếu chưa có sort
- Tự động merge paginate args

#### 5.3. `getResults($request, $args = [])`
**Mô tả**: Lấy kết quả đã filter và parse  
**Tham số**: 
- `$request` (Request): HTTP Request object
- `$args` (array): Mảng các tham số truy vấn bổ sung

**Trả về**: `MaskCollection|ResourceCollection|Collection`  
**Sự kiện**: `prepareGetResults`, `beforeGetResults`, `afterGetResults`  
**Đặc biệt**: 
- Tự động append query string vào pagination links
- Parse collection theo `responseMode`

#### 5.4. `countResults($request, $args = [])`
**Mô tả**: Đếm số kết quả sau khi filter  
**Tham số**: 
- `$request` (Request): HTTP Request object
- `$args` (array): Mảng các tham số truy vấn bổ sung

**Trả về**: `int`  
**Sự kiện**: `prepareCountResults`, `beforeCountResults`

#### 5.5. `getData($args = [])`
**Mô tả**: Lấy dữ liệu và parse collection  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `MaskCollection|ResourceCollection|Collection`  
**Sự kiện**: `beforegetData`, `aftergetResults`

#### 5.6. `getDetail($args = [], $useConfig = true)`
**Mô tả**: Lấy chi tiết một bản ghi với cấu hình đầy đủ  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn
- `$useConfig` (bool): Có sử dụng cấu hình join/select/eager không

**Trả về**: `Model|null`  
**Sự kiện**: `beforegetDetail`, `aftergetDetail`  
**Đặc biệt**: 
- Tự động build join, select, eager loading, group by nếu `$useConfig = true`

#### 5.7. `getFormData($args = [])`
**Mô tả**: Lấy dữ liệu cho form  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `Model|null`  
**Sự kiện**: `beforegetFormData`, `aftergetFormData`  
**Đặc biệt**: 
- Tự động build join, select, group by
- Gọi `beforeGetFormData()` hook

#### 5.8. `detail($args, $useConfig = true)`
**Mô tả**: Lấy chi tiết và parse thành mask/resource  
**Tham số**: 
- `$args` (int|array): ID hoặc mảng điều kiện
- `$useConfig` (bool): Có sử dụng cấu hình không

**Trả về**: `Mask|Resource|Model|null`  
**Sự kiện**: `beforedetail`, `afterdetail`  
**Đặc biệt**: 
- Tự động lock mask sau khi parse
- Parse theo `responseMode`

```php
$userDetail = $repository->detail(1);
```

---

### 6. NHÓM QUERY BUILDER (BaseQuery)

#### 6.1. `query($args = [])`
**Mô tả**: Tạo query builder với các điều kiện phức tạp  
**Tham số**: 
- `$args` (array): Mảng các tham số truy vấn

**Trả về**: `Builder` - Eloquent Query Builder  
**Sự kiện**: `beforequery`, `query`  
**Đặc biệt**: 
- Hỗ trợ rất nhiều tham số đặc biệt bắt đầu bằng `@`
- Hỗ trợ search, order by, limit, actions, eager loading
- Hỗ trợ multi-language search
- Hỗ trợ soft delete filter

**Các tham số đặc biệt**:
- `@search`: Tìm kiếm
- `@mlcsearch`: Tìm kiếm multi-language
- `@mlcslug`: Tìm kiếm slug multi-language
- `@order_by` hoặc `@sortby`: Sắp xếp
- `@limit`: Giới hạn số lượng
- `@actions`: Các hành động với query builder
- `@trashed`: Lọc bản ghi đã xóa
- `@softdelete`: Lọc soft delete

**Ví dụ**:
```php
$query = $repository->query([
    'status' => 'active',
    '@search' => ['keywords' => 'john', 'by' => ['name', 'email']],
    '@order_by' => 'created_at-DESC',
    '@limit' => [0, 20],
    '@actions' => [
        ['with', 'profile'],
        ['withCount', 'comments']
    ]
]);
```

#### 6.2. `reset($all = false)`
**Mô tả**: Reset các tham số và query builder  
**Tham số**: 
- `$all` (bool): Có reset tất cả không (bao gồm fixable params)

**Trả về**: `$this`

#### 6.3. `param($key, $value = null)`
**Mô tả**: Thêm tham số truy vấn  
**Tham số**: 
- `$key` (string|array): Tên tham số hoặc mảng tham số
- `$value` (mixed): Giá trị (nếu $key là string)

**Trả về**: `$this`

```php
$repository->param('status', 'active')
    ->param(['age' => 18, 'city' => 'Hanoi']);
```

#### 6.4. `addsearch($keywords, $search_by, $rules = null)`
**Mô tả**: Thêm điều kiện tìm kiếm  
**Tham số**: 
- `$keywords` (string): Từ khóa tìm kiếm
- `$search_by` (string|array): Cột hoặc mảng cột tìm kiếm
- `$rules` (array|null): Quy tắc tìm kiếm tùy chỉnh

**Trả về**: `$this`

```php
$repository->addsearch('john', ['name', 'email']);
```

#### 6.5. `like($column, $value)`
**Mô tả**: Tìm kiếm với LIKE '%value%'  
**Tham số**: 
- `$column` (string): Tên cột
- `$value` (string): Giá trị tìm kiếm

**Trả về**: `$this`

#### 6.6. `orLike($column, $value)`
**Mô tả**: Tìm kiếm với OR LIKE '%value%'  
**Tham số**: 
- `$column` (string): Tên cột
- `$value` (string): Giá trị tìm kiếm

**Trả về**: `$this`

#### 6.7. `order_by($column, $type = 'asc')`
**Mô tả**: Sắp xếp kết quả  
**Tham số**: 
- `$column` (string|array): Tên cột hoặc mảng cột
- `$type` (string): Loại sắp xếp (asc/desc)

**Trả về**: `$this`

```php
$repository->order_by('created_at', 'desc')
    ->order_by(['name' => 'asc', 'age' => 'desc']);
```

#### 6.8. `sortBy($column, $type = 'asc')`
**Mô tả**: Tương tự `order_by()`  
**Tham số**: 
- `$column` (string|array): Tên cột hoặc mảng cột
- `$type` (string): Loại sắp xếp

**Trả về**: `$this`

#### 6.9. `limit($start, $length = 0)`
**Mô tả**: Giới hạn số lượng kết quả  
**Tham số**: 
- `$start` (int|array|string): Vị trí bắt đầu hoặc mảng [start, length] hoặc string "start,length"
- `$length` (int): Số lượng (nếu $start là int)

**Trả về**: `$this`

```php
$repository->limit(0, 20); // Lấy 20 bản ghi đầu tiên
$repository->limit([10, 20]); // Bỏ qua 10, lấy 20
$repository->limit('10,20'); // Tương tự
```

#### 6.10. `paginate($paginate = null)`
**Mô tả**: Bật/tắt phân trang  
**Tham số**: 
- `$paginate` (int|bool|null): Số lượng mỗi trang, false để tắt, null để giữ nguyên

**Trả về**: `$this`

```php
$repository->paginate(20); // Phân trang 20 bản ghi/trang
$repository->paginate(false); // Tắt phân trang
```

#### 6.11. `trashed($status = true)`
**Mô tả**: Lọc bản ghi đã xóa (soft delete)  
**Tham số**: 
- `$status` (bool): true = chỉ lấy đã xóa, false = chỉ lấy chưa xóa

**Trả về**: `$this`

#### 6.12. `notTrashed($day = null)`
**Mô tả**: Lọc bản ghi chưa xóa hoặc xóa trong N ngày  
**Tham số**: 
- `$day` (int|null): Số ngày (nếu có)

**Trả về**: `$this`

#### 6.13. `resetTrashed()`
**Mô tả**: Bỏ điều kiện trashed  
**Tham số**: Không  
**Trả về**: `$this`

#### 6.14. `eager($type, $relation, $func = null, $queryBuilder = null)`
**Mô tả**: Eager loading relationships  
**Tham số**: 
- `$type` (string): Loại eager ('with', 'load', 'withCount', etc.)
- `$relation` (string): Tên relationship
- `$func` (callable|int|array|null): Callback hoặc limit hoặc mảng điều kiện
- `$queryBuilder` (mixed): Query builder tùy chỉnh

**Trả về**: `$this`

```php
$repository->eager('with', 'profile')
    ->eager('withCount', 'comments', function($query) {
        $query->where('status', 'approved');
    });
```

#### 6.15. `queryAfter($action)`
**Mô tả**: Thêm callback sau khi build query  
**Tham số**: 
- `$action` (callable): Hàm callback

**Trả về**: `$this`

```php
$repository->queryAfter(function($query) {
    $query->where('status', 'active');
});
```

#### 6.16. `searchMode($mode)`
**Mô tả**: Thiết lập chế độ tìm kiếm  
**Tham số**: 
- `$mode` (string): 'all', 'raw', 'multiple', 'analytic'

**Trả về**: `$this`

#### 6.17. `searchType($type)`
**Mô tả**: Thiết lập kiểu tìm kiếm  
**Tham số**: 
- `$type` (string): 'all', 'ward', 'start', 'end', 'match'

**Trả về**: `$this`

#### 6.18. `searchRule($column, $rule)`
**Mô tả**: Thiết lập quy tắc tìm kiếm cho cột  
**Tham số**: 
- `$column` (string|array): Tên cột hoặc mảng cột => rule
- `$rule` (string): Quy tắc tìm kiếm

**Trả về**: `$this`

#### 6.19. `disableSearchColumn($column)`
**Mô tả**: Vô hiệu hóa cột khỏi tìm kiếm  
**Tham số**: 
- `$column` (string|array): Tên cột hoặc mảng cột

**Trả về**: `$this`

#### 6.20. `getFields()`
**Mô tả**: Lấy danh sách các cột của model  
**Tham số**: Không  
**Trả về**: `array`

#### 6.21. `checkField($field)`
**Mô tả**: Kiểm tra cột có tồn tại không  
**Tham số**: 
- `$field` (string): Tên cột

**Trả về**: `bool`

#### 6.22. `getTable()`
**Mô tả**: Lấy tên bảng  
**Tham số**: Không  
**Trả về**: `string`

---

### 7. NHÓM XỬ LÝ DỮ LIỆU (DataAction)

#### 7.1. `getSlug($str, $id, $col, $value)`
**Mô tả**: Tạo slug duy nhất  
**Tham số**: 
- `$str` (string): Chuỗi cần tạo slug
- `$id` (int|null): ID bản ghi (để loại trừ khi check)
- `$col` (string|null): Tên cột điều kiện
- `$value` (mixed): Giá trị điều kiện

**Trả về**: `string|null` - Slug duy nhất

```php
$slug = $repository->getSlug('Hello World', null, 'category_id', 1);
// Kết quả: 'hello-world' hoặc 'hello-world-1', 'hello-world-2', ...
```

#### 7.2. `checkSlug($str, $id, $col, $value)`
**Mô tả**: Kiểm tra slug có hợp lệ và duy nhất không  
**Tham số**: 
- `$str` (string): Slug cần kiểm tra
- `$id` (int|null): ID bản ghi (để loại trừ)
- `$col` (string|null): Tên cột điều kiện
- `$value` (mixed): Giá trị điều kiện

**Trả về**: 
- `1`: Slug hợp lệ và duy nhất
- `0`: Slug đã tồn tại
- `-1`: Slug rỗng
- `-2`: Slug không hợp lệ (chứa ký tự đặc biệt)

#### 7.3. `replace($columns, $find, $replace)`
**Mô tả**: Thay thế nội dung trong các cột  
**Tham số**: 
- `$columns` (string|array): Tên cột hoặc mảng cột
- `$find` (string): Chuỗi cần tìm
- `$replace` (string): Chuỗi thay thế

**Trả về**: `int|false` - Số bản ghi đã cập nhật hoặc false

```php
$count = $repository->replace('content', 'old text', 'new text');
```

---

### 8. NHÓM CACHE (CacheAction)

#### 8.1. `cache($key, $time, $params = [])`
**Mô tả**: Đăng ký repository vào cache task  
**Tham số**: 
- `$key` (string|null): Key cache
- `$time` (int): Thời gian cache (phút), 0 = không cache
- `$params` (array): Tham số bổ sung

**Trả về**: `CacheTask|$this` - CacheTask nếu có cache, $this nếu không

```php
$cached = $repository->cache('users_list', 60)->get();
```

#### 8.2. `registerCacheMethods(...$methods)`
**Mô tả**: Đăng ký các phương thức sẽ được cache  
**Tham số**: 
- `...$methods` (string): Danh sách tên phương thức

**Trả về**: `void`

#### 8.3. `registerCacheMethod($methods)`
**Mô tả**: Đăng ký một phương thức cache  
**Tham số**: 
- `$methods` (string|array): Tên phương thức hoặc mảng alias => method

**Trả về**: `void`

#### 8.4. `getCacheMethods()`
**Mô tả**: Lấy danh sách các phương thức đã đăng ký cache  
**Tham số**: Không  
**Trả về**: `array`

---

### 9. NHÓM FILE (FileAction)

#### 9.1. `deleteAttachFile($id)`
**Mô tả**: Xóa file đính kèm của bản ghi  
**Tham số**: 
- `$id` (int): ID bản ghi

**Trả về**: `bool`  
**Sự kiện**: `beforedeleteAttachFile`, `afterdeleteAttachFile`

#### 9.2. `getAttachFilename($id)`
**Mô tả**: Lấy tên file đính kèm  
**Tham số**: 
- `$id` (int): ID bản ghi

**Trả về**: `string|null`

---

### 10. NHÓM OWNER (OwnerAction)

#### 10.1. `setOwnerID($id)`
**Mô tả**: Thiết lập owner ID  
**Tham số**: 
- `$id` (int): Owner ID

**Trả về**: `void`

#### 10.2. `getOwnerID()`
**Mô tả**: Lấy owner ID hiện tại  
**Tham số**: Không  
**Trả về**: `int`

#### 10.3. `unOwnerQuery()`
**Mô tả**: Bỏ điều kiện owner trong query  
**Tham số**: Không  
**Trả về**: `$this`

---

### 11. NHÓM FILTER HELPER (FilterAction)

#### 11.1. `mode($mode)`
**Mô tả**: Thiết lập chế độ response  
**Tham số**: 
- `$mode` (string): 'resource', 'mask', 'collection', 'default', 'raw'

**Trả về**: `$this`

#### 11.2. `buildFilter($request)`
**Mô tả**: Build filter từ request  
**Tham số**: 
- `$request` (Request): HTTP Request

**Trả về**: `void`  
**Đặc biệt**: Gọi `buildSearch()`, `prepareFilter()`, `buildEager()`, `buildJoin()`, `buildSelect()`

#### 11.3. `prepareFilter($request)`
**Mô tả**: Chuẩn bị filter từ request  
**Tham số**: 
- `$request` (Request): HTTP Request

**Trả về**: `$this`  
**Đặc biệt**: 
- Tự động parse orderby từ request (orderby_*)
- Tự động parse where từ request
- Tự động build group by

#### 11.4. `getSearchFields($request)`
**Mô tả**: Lấy danh sách cột tìm kiếm từ request  
**Tham số**: 
- `$request` (Request): HTTP Request

**Trả về**: `array`

#### 11.5. `parsePaginateParam($request, $args)`
**Mô tả**: Parse tham số phân trang từ request  
**Tham số**: 
- `$request` (Request): HTTP Request
- `$args` (array): Mảng args hiện tại

**Trả về**: `array`

#### 11.6. `getPaginateInfo($request)`
**Mô tả**: Lấy thông tin phân trang từ request  
**Tham số**: 
- `$request` (Request): HTTP Request

**Trả về**: `array` - ['page', 'per_page', 'current_page']

#### 11.7. `getPaginateArgs($request)`
**Mô tả**: Lấy tham số phân trang dạng args  
**Tham số**: 
- `$request` (Request): HTTP Request

**Trả về**: `array` - ['@paginate' => per_page]

#### 11.8. `getPaginateData($request, $count)`
**Mô tả**: Tính toán thông tin phân trang  
**Tham số**: 
- `$request` (Request): HTTP Request
- `$count` (int): Tổng số bản ghi

**Trả về**: `array` - ['page', 'per_page', 'current_page', 'page_total']

#### 11.9. `buildDateFilterQuery($request, $col, $ignore)`
**Mô tả**: Build query filter theo ngày  
**Tham số**: 
- `$request` (Request): HTTP Request
- `$col` (string): Tên cột ngày, mặc định 'date'
- `$ignore` (string|null): Bỏ qua filter nào

**Trả về**: `string` - View mode ('all', 'date', 'year', 'month', 'day')

#### 11.10. `ignoreFilter(...$args)`
**Mô tả**: Bỏ qua một số filter  
**Tham số**: 
- `...$args`: Danh sách filter cần bỏ qua

**Trả về**: `$this`

---

### 12. NHÓM PARSE RESPONSE (FilterAction)

#### 12.1. `parseCollection($collection)`
**Mô tả**: Parse collection theo response mode  
**Tham số**: 
- `$collection` (Collection|LengthAwarePaginator): Collection cần parse

**Trả về**: `MaskCollection|ResourceCollection|Collection`

#### 12.2. `parseDetail($data)`
**Mô tả**: Parse detail theo response mode  
**Tham số**: 
- `$data` (Model): Model cần parse

**Trả về**: `Mask|Resource|Model|null`  
**Đặc biệt**: Tự động lock mask sau khi parse

#### 12.3. `mask($data)`
**Mô tả**: Tạo mask từ model  
**Tham số**: 
- `$data` (Model): Model

**Trả về**: `Mask|Model`

#### 12.4. `maskCollection($data, $total)`
**Mô tả**: Tạo mask collection  
**Tham số**: 
- `$data` (Collection): Collection
- `$total` (int): Tổng số bản ghi

**Trả về**: `MaskCollection|ExampleCollection`

#### 12.5. `resource($data)`
**Mô tả**: Tạo resource từ model  
**Tham số**: 
- `$data` (Model): Model

**Trả về**: `Resource|Model`

#### 12.6. `resourceCollection($data)`
**Mô tả**: Tạo resource collection  
**Tham số**: 
- `$data` (Collection): Collection

**Trả về**: `ResourceCollection|array`

---

## 🔧 CÁC THAM SỐ ĐẶC BIỆT TRONG QUERY

Khi sử dụng `get()`, `first()`, `query()`, bạn có thể sử dụng các tham số đặc biệt bắt đầu bằng `@`:

### `@search`
Tìm kiếm trong các cột:
```php
['@search' => 'keyword']
['@search' => ['keywords' => 'keyword', 'by' => ['name', 'email']]]
```

### `@mlcsearch`
Tìm kiếm multi-language:
```php
['@mlcsearch' => 'keyword']
['@mlcsearch' => ['keywords' => 'keyword', 'by' => ['title']]]
```

### `@mlcslug`
Tìm kiếm slug multi-language:
```php
['@mlcslug' => 'my-slug']
```

### `@order_by` hoặc `@sortby`
Sắp xếp:
```php
['@order_by' => 'created_at-DESC']
['@order_by' => ['name' => 'ASC', 'age' => 'DESC']]
```

### `@limit`
Giới hạn:
```php
['@limit' => 20] // Lấy 20 bản ghi đầu
['@limit' => [10, 20]] // Bỏ qua 10, lấy 20
['@limit' => '10,20'] // Tương tự
```

### `@paginate`
Phân trang:
```php
['@paginate' => 20] // 20 bản ghi mỗi trang
```

### `@trashed` hoặc `@softdelete`
Lọc bản ghi đã xóa:
```php
['@trashed' => true] // Chỉ lấy đã xóa
['@trashed' => false] // Chỉ lấy chưa xóa
['@trashed' => 7] // Lấy chưa xóa hoặc xóa trong 7 ngày gần đây
```

### `@actions`
Thực hiện các hành động với query builder:
```php
['@actions' => [
    ['with', 'profile'],
    ['withCount', 'comments'],
    ['whereHas', 'orders', function($q) { ... }]
]]
```

### Các phương thức query builder khác
Bạn có thể gọi bất kỳ phương thức nào của Eloquent Query Builder thông qua `@methodName`:
```php
['@select' => ['id', 'name', 'email']]
['@join' => ['users', 'users.id', '=', 'posts.user_id']]
['@whereIn' => ['status', ['active', 'pending']]]
['@groupBy' => 'category_id']
['@having' => ['count', '>', 10]]
```

---

## 📝 LƯU Ý QUAN TRỌNG

1. **Magic Methods**: BaseRepository hỗ trợ magic methods, bạn có thể gọi các phương thức query builder trực tiếp:
   ```php
   $repository->where('status', 'active')
       ->whereIn('id', [1, 2, 3])
       ->orderBy('created_at', 'desc')
       ->get();
   ```

2. **Whereable Fields**: Bạn có thể định nghĩa `$whereable` trong repository để cho phép filter tự động từ request.

3. **Searchable Fields**: Định nghĩa `$searchable` để cho phép tìm kiếm tự động.

4. **Sortable Fields**: Định nghĩa `$sortable` để cho phép sắp xếp tự động.

5. **Events**: Hầu hết các phương thức đều có events để bạn can thiệp vào quá trình xử lý.

6. **Multi-language**: Repository tự động hỗ trợ multi-language nếu model có cấu hình MLC.

7. **Owner**: Repository tự động filter theo owner_id nếu có thiết lập.

---

## 🎯 VÍ DỤ SỬ DỤNG TỔNG HỢP

```php
// Lấy danh sách với filter và phân trang
$users = $repository
    ->where('status', 'active')
    ->where('age', '>=', 18)
    ->addsearch('john', ['name', 'email'])
    ->order_by('created_at', 'desc')
    ->paginate(20)
    ->get();

// Lấy chi tiết với relationships
$user = $repository
    ->with('profile')
    ->withCount('orders')
    ->getDetail(['id' => 1]);

// Filter từ request
$results = $repository->filter($request, [
    'status' => 'active'
]);

// Cache kết quả
$cached = $repository
    ->cache('users_active', 60)
    ->where('status', 'active')
    ->get();

// Chunk xử lý dữ liệu lớn
$repository->chunk(function($users) {
    foreach ($users as $user) {
        // Xử lý
    }
}, 500);
```

---

## 📚 TÀI LIỆU THAM KHẢO

- Laravel Eloquent Query Builder: https://laravel.com/docs/queries
- Laravel Relationships: https://laravel.com/docs/eloquent-relationships
- Laravel Pagination: https://laravel.com/docs/pagination


