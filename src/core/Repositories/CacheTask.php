<?php

namespace Saola\Core\Repositories;

use Saola\Core\Engines\CacheEngine;
use Saola\Core\Masks\Mask;
use Saola\Core\Masks\MaskCollection;
use Saola\Core\Models\Model;

/**
 * danh sách method
 * @method $this select(...$columns) Chọn các cột để truy vấn
 * @method $this selectRaw($string) Chọn cột với biểu thức SQL thô
 * @method $this addSelect(...$columns) Thêm cột vào câu lệnh SELECT
 * @method $this addSelectRaw($string) Thêm biểu thức SQL thô vào câu lệnh SELECT
 * @method $this from($table) Chỉ định bảng để truy vấn
 * @method $this fromRaw($string) Chỉ định bảng với biểu thức SQL thô
 * @method $this join(\Cloure $callback) Join bảng với callback
 * @method $this join(string $table, string $tableColumn, string $operator, string $leftTableColumn) Join bảng bằng điều kiện
 * @method $this leftJoin($table, $tableColumn, $operator, $leftTableColumn) LEFT JOIN bảng
 * @method $this rightJoin($table, $tableColumn, $operator, $leftTableColumn) RIGHT JOIN bảng
 * @method $this crossJoin($_ = null) CROSS JOIN bảng
 * @method $this joinRaw($string) JOIN bảng với câu lệnh SQL thô
 * @method $this leftJoinRaw($string) LEFT JOIN bảng với câu lệnh SQL thô
 * @method $this rightJoinRaw($string) RIGHT JOIN bảng với câu lệnh SQL thô
 * @method $this when($_ = null) Thêm điều kiện khi giá trị đúng
 * @method $this unless($condition, $callback) Thêm điều kiện khi giá trị sai
 * @method $this tap($callback) Thực thi callback với query hiện tại
 * @method $this where($_ = null) Thêm điều kiện WHERE
 * @method $this whereNot($column, $operator = null, $value = null) Thêm điều kiện WHERE NOT
 * @method $this whereRaw($_ = null) Thêm điều kiện WHERE với SQL thô
 * @method $this whereIn($column, $values = []) Thêm điều kiện WHERE IN
 * @method $this whereNotIn($column, $values = []) Thêm điều kiện WHERE NOT IN
 * @method $this whereBetween($column, $values = []) Thêm điều kiện WHERE BETWEEN
 * @method $this whereNotBetween($column, $values = []) Thêm điều kiện WHERE NOT BETWEEN
 * @method $this whereDay($_ = null) Thêm điều kiện WHERE cho ngày
 * @method $this whereMonth($_ = null) Thêm điều kiện WHERE cho tháng
 * @method $this whereYear($_ = null) Thêm điều kiện WHERE cho năm
 * @method $this whereDate($_ = null) Thêm điều kiện WHERE cho ngày tháng
 * @method $this whereTime($_ = null) Thêm điều kiện WHERE cho thời gian
 * @method $this whereNull($column) Thêm điều kiện WHERE NULL
 * @method $this whereNotNull($column) Thêm điều kiện WHERE NOT NULL
 * @method $this whereColumn($_ = null) Thêm điều kiện WHERE so sánh 2 cột
 * @method $this whereJsonContains($column, $value) Thêm điều kiện WHERE JSON_CONTAINS
 * @method $this whereJsonLength($column, $operator, $value = null) Thêm điều kiện WHERE JSON_LENGTH
 * @method $this whereExists($callback) Thêm điều kiện WHERE EXISTS
 * @method $this whereNotExists($callback) Thêm điều kiện WHERE NOT EXISTS
 * @method $this whereIntegerInRaw($column, $values) Thêm điều kiện WHERE IN với số nguyên
 * @method $this whereIntegerNotInRaw($column, $values) Thêm điều kiện WHERE NOT IN với số nguyên
 * @method $this whereFullText($columns, $value, $options = []) Thêm điều kiện tìm kiếm full-text
 * @method $this orWhere($_ = null) Thêm điều kiện OR WHERE
 * @method $this orWhereNot($column, $operator = null, $value = null) Thêm điều kiện OR WHERE NOT
 * @method $this orWhereRaw($_ = null) Thêm điều kiện OR WHERE với SQL thô
 * @method $this orWhereIn($column, $values = []) Thêm điều kiện OR WHERE IN
 * @method $this orWhereNotIn($column, $values = []) Thêm điều kiện OR WHERE NOT IN
 * @method $this orWhereBetween($column, $values = []) Thêm điều kiện OR WHERE BETWEEN
 * @method $this orWhereNotBetween($column, $values = []) Thêm điều kiện OR WHERE NOT BETWEEN
 * @method $this orWhereDay($_ = null) Thêm điều kiện OR WHERE cho ngày
 * @method $this orWhereMonth($_ = null) Thêm điều kiện OR WHERE cho tháng
 * @method $this orWhereYear($_ = null) Thêm điều kiện OR WHERE cho năm
 * @method $this orWhereDate($_ = null) Thêm điều kiện OR WHERE cho ngày tháng
 * @method $this orWhereTime($_ = null) Thêm điều kiện OR WHERE cho thời gian
 * @method $this orWhereNull($column) Thêm điều kiện OR WHERE NULL
 * @method $this orWhereNotNull($column) Thêm điều kiện OR WHERE NOT NULL
 * @method $this orWhereColumn($leftColumn, $operator = '=', $rightColumn) Thêm điều kiện OR WHERE so sánh 2 cột
 * @method $this orWhereJsonContains($column, $value) Thêm điều kiện OR WHERE JSON_CONTAINS
 * @method $this orWhereJsonLength($column, $operator, $value = null) Thêm điều kiện OR WHERE JSON_LENGTH
 * @method $this orWhereExists($callback) Thêm điều kiện OR WHERE EXISTS
 * @method $this orWhereNotExists($callback) Thêm điều kiện OR WHERE NOT EXISTS
 * @method $this orWhereFullText($columns, $value, $options = []) Thêm điều kiện OR WHERE full-text
 * @method $this groupBy($column) Nhóm kết quả theo cột
 * @method $this groupByRaw($string) Nhóm kết quả với biểu thức SQL thô
 * @method $this having($_ = null) Thêm điều kiện HAVING
 * @method $this havingRaw($_ = null) Thêm điều kiện HAVING với SQL thô
 * @method $this havingBetween($column, $values) Thêm điều kiện HAVING BETWEEN
 * @method $this orHaving($column, $operator = null, $value = null) Thêm điều kiện OR HAVING
 * @method $this orHavingRaw($string) Thêm điều kiện OR HAVING với SQL thô
 * @method $this orderBy($_ = null) Sắp xếp kết quả
 * @method $this orderByRaw($_ = null) Sắp xếp kết quả với biểu thức SQL thô
 * @method $this orderByDesc($column) Sắp xếp kết quả theo thứ tự giảm dần
 * @method $this latest($column = 'created_at') Sắp xếp theo thời gian mới nhất
 * @method $this oldest($column = 'created_at') Sắp xếp theo thời gian cũ nhất
 * @method $this inRandomOrder($seed = null) Sắp xếp ngẫu nhiên
 * @method $this reorder($column = null, $direction = 'asc') Ghi đè thứ tự sắp xếp
 * @method $this skip($_ = null) Bỏ qua số bản ghi đầu tiên
 * @method $this take($_ = null) Lấy số bản ghi chỉ định
 * @method $this limit($value) Giới hạn số bản ghi trả về
 * @method $this offset($value) Bỏ qua số bản ghi đầu tiên
 * @method $this forPage($page, $perPage = 15) Phân trang kết quả
 * @method $this distinct() Lấy các giá trị duy nhất
 * @method $this with($_ = null) Eager load các mối quan hệ
 * @method $this without($relations) Loại bỏ eager load đã định nghĩa
 * @method $this load($_ = null) Lazy load các mối quan hệ
 * @method $this withCount($relations) Đếm số bản ghi trong quan hệ
 * @method $this withAvg($relation, $column) Tính trung bình của cột trong quan hệ
 * @method $this withSum($relation, $column) Tính tổng của cột trong quan hệ
 * @method $this withMin($relation, $column) Tìm giá trị nhỏ nhất của cột trong quan hệ
 * @method $this withMax($relation, $column) Tìm giá trị lớn nhất của cột trong quan hệ
 * @method $this withExists($relation) Kiểm tra sự tồn tại của quan hệ
 * @method $this whereMorph($relation, $types, $callback = null) Thêm điều kiện WHERE cho quan hệ đa hình
 * @method $this orWhereMorph($relation, $types, $callback = null) Thêm điều kiện OR WHERE cho quan hệ đa hình
 * @method $this whereMorphIn($relation, $types, $callback = null) Thêm điều kiện WHERE IN cho quan hệ đa hình
 * @method $this whereMorphNotIn($relation, $types, $callback = null) Thêm điều kiện WHERE NOT IN cho quan hệ đa hình
 * @method $this orWhereMorphIn($relation, $types, $callback = null) Thêm điều kiện OR WHERE IN cho quan hệ đa hình
 * @method $this orWhereMorphNotIn($relation, $types, $callback = null) Thêm điều kiện OR WHERE NOT IN cho quan hệ đa hình
 * @method $this lockForShare() Khóa bản ghi để chia sẻ
 * @method $this lockForUpdate() Khóa bản ghi để cập nhật
 * @method $this withCTE($name, $query) Thêm Common Table Expression
 * @method $this withRecursive($name, $query) Thêm Recursive Common Table Expression
 * @method $this window($name, $callback = null) Định nghĩa cửa sổ cho phân tích
 * @method $this union($_ = null) Kết hợp kết quả của các truy vấn
 * @method $this unionAll($_ = null) Kết hợp tất cả kết quả của các truy vấn
 * @method MaskCollection filter(\Illuminate\Http\Request $request, array $args) Lọc dữ liệu từ request
 * @method Mask detail(\Illuminate\Http\Request $request, array $args) Lấy chi tiết từ request
 * @method MaskCollection getData(array $args) Lấy danh sách bản ghi theo điều kiện và gán mask
 * @method Model[] get(array $args) Lấy danh sách bản ghi
 * @method Model[] getBy(array $args) Lấy danh sách bản ghi theo điều kiện
 * @method Model find($id) Tìm bản ghi theo ID
 * @method Model findBy(string $column, mixed $value) Tìm bản ghi theo cột và giá trị
 * @method Model first(array $args) Lấy bản ghi đầu tiên
 * @method int count(array $args) Đếm số bản ghi
 * @method $this trashed(boolean|numeric $status) set trang thai lay du lieu
 * @method $this notTrashed() set trang thai lay du lieu chua xoa
 */

class CacheTask
{
    /**
     * doi tuong repository
     *
     * @var static
     */
    protected $repository;

    /**
     * khóa để truy cập cache
     *
     * @var string
     */
    protected $key = null;
    /**
     * tham số
     *
     * @var array
     */
    protected $params = [];
    /**
     * expired time (minute)
     *
     * @var integer
     */
    protected $time = 0;

    /**
     * các phương thúc lấy dữ liệu
     *
     * @var array
     */
    protected static $getDataMethods = [
        'get' => 'get', 'getby' => 'getBy', 'findby' => 'findBy', 'first' => 'first', 'count' => 'count',
        'countby' => 'coumtBy', 'getresults' => 'getResults', 'detail' => 'detail', 'getdata' => 'getData'
    ];


    /**
     * khoi tạo task
     *
     * @param static|ApiRepository $repository
     * @param string $key
     * @param integer $time
     * @param array $params
     */
    public function __construct($repository, $key = null, $time = 0, $params = [])
    {
        $this->repository = $repository;
        $this->key = $key;
        $this->time = $time;
        $this->params = $params;
    }

    /**
     * lấy key đúng chuẩn
     *
     * @return string
     */
    protected function getKey()
    {
        return 'repository-' . (static::class). '-'. $this->repository->getTable() . '-' . $this->key;
    }
    /**
     * truy cập phần tử trong repository
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->repository->{$name};
    }


    /**
     * thêm tham số cho repository
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->repository->{$name} = $value;
    }

    /**
     * gọi các phương thức get data hoặc repository
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $dataMethods = $this->repository->getCacheMethods();
        // nếu tên phương thức trùng với một giá trị nào đó trong mảng các phương thúc lấy dữ liệu
        // của repository hiện tại thì gọi phương thức lấy dữ liệu cache
        if(in_array($name, $dataMethods)){
            return $this->getCache($name, $arguments);
        }
        // tương tự diều kiệm trên nhưng kiểm tra key, nhằm giảm độ khó trong việc viết hoa viết thường
        elseif(array_key_exists($key = strtolower($name), $dataMethods)){
            return $this->getCache($dataMethods[$key], $arguments);
        }
        // nếu tên phương thức trùng với một giá trị nào đó trong mảng các phương thúc lấy dữ liệu
        // của base repository mặc định thì gọi phương thức lấy dữ liệu cache
        elseif(in_array($name, static::$getDataMethods)){
            return $this->getCache($name, $arguments);
        }
        // tương tự diều kiệm trên nhưng kiểm tra key, nhằm giảm độ khó trong việc viết hoa viết thường
        elseif(array_key_exists($key = strtolower($name), static::$getDataMethods)){
            return $this->getCache(static::$getDataMethods[$key], $arguments);
        }
        // nếu không thuộc 2 trường hợp trên thì gọi đến các phương thức trong repository
        call_user_func_array([$this->repository, $name], $arguments);
        return $this;
        
    }
    /**
     * lấy cache hoặc dử liệu mới
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function getCache($method, array $arguments=[])
    {
        $time = $this->time ? $this->time : 0;
        // dump(system_setting());
        if(!$time){
            return call_user_func_array([$this->repository, $method], $arguments);
        }
        $key = $this->getKey();
        if(!($data = CacheEngine::get($key, $params = array_merge($this->params, $arguments)))){
            $data = call_user_func_array([$this->repository, $method], $arguments);
            CacheEngine::set($key, $data, $time, $params);
        }
        return $data;
    }
    
}
