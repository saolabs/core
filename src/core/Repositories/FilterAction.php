<?php

namespace Saola\Core\Repositories;

use ReflectionClass;

use Saola\Core\asks\Mask;
use Saola\Core\Masks\MaskCollection;
use Saola\Core\Magic\Arr;
use Saola\Core\Masks\ExampleCollection;
use Saola\Core\odels\Model;
use Saola\Core\odels\MongoModel;
use Saola\Core\odels\SQLModel;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * các phương thức filter
 * @method BaseRepository beforeFillter(\Iluminate\Http\Request $request)
 * @property bool $isMLCFilter filter da ngon ngu
 */
trait FilterAction
{
    use DatabaseHelper;
    /**
     * @var array $searchable danh sach cac cot co the tim kiem
     * ví dụ: ['id', 'name', 'column' => 'table.column']
     */
    protected $searchable = [];
    /**
     * @var array $searchable danh sach cac cot co the tim kiem
     * ví dụ: ['id', 'name', 'column' => 'table.column']
     */
    protected $searchDisable = [];

    /**
     * @var array $sortable
     * ví dụ: ['id', 'name', 'column' => 'table.column']
     */
    protected $sortable = []; // danh sach cot de sap xep

    /**
     * @var array $sortRawable
     * ví dụ: ['id', 'name', 'column' => 'table.column']
     */
    protected $sortRawable = []; // danh sach cot de sap xep

    /**
     * @var array tất cả các menh de, cac bang dc liet ke duoi dang mang
     * ví dụ: [
     *      ['join', 'main_table', 'main_table.id', '=', 'this_table.main_id'],
     *      ... 
     * ]
     */
    protected $joinable = [];

    /**
     * @var array tất cả các cot duoc select
     * ví dụ: ['posts.*', 'categories.name as cate_name', ...]
     */

    protected $selectable = [];

    /**
     * @var array 
     * ví dụ: ['(Select count(1) from comments where comments.post_id = posts.id) as comment_count', ...]
     */

    protected $selectRawable = [];

    /**
     * @var array $whereable các cot hoặc dịnh danh cột có thể được bind theo request
     * ví dụ: ['id', 'name', 'column' => 'table.column']
     * 'column' => 'table.column'
     * column là định danh được select hoặc tên biến trong request cho gắng gọn
     * table.column tên cột bao gồm tên bảng để bind nếu ko select hoặc select ko dinh danh 
     */
    protected $whereable = [];

    protected $ignoreValues = [];

    /**
     * @var array $groupable mảng các cột cần group
     */
    protected $groupable = [];

    /**
     * @var array $groupable mảng các cột cần group
     */
    protected $groupableRaw = [];

    protected $_ignoreFilter = [];

    /**
     * @var array $withable
     */
    protected $withable = [];

    /**
     * @var array $withCountable
     */
    protected $withCountable = [];

    /**
     * @var array $loadable
     */
    protected $loadable = [];


    protected $ignoreRequestParams = [];

    /**
     * @var int $perPage số kết quả dc show ra mỗi request
     */
    protected $perPage = 10;

    /**
     * @var boolean $paginate cho phep truy vấn sử dụng phân trang tự động
     */
    protected $paginate = true;


    protected $resourceNamespace = 'App\Http\Resources';

    /**
     * @var string $resource
     */
    protected $resourceClass = 'ExampleResource';

    /**
     * @var string $collectionClass
     */
    protected $collectionClass = 'ExampleCollection';

    /**
     * namespace của mặt nạ
     *
     * @var string
     */
    protected $maskNamespace = 'Saola\Core\Masks';


    /**
     * namespace của mặt nạ
     *
     * @var string
     */
    protected $appMaskNamespace = 'App\Masks';

    /**
     * tên class mặt nạ. Thược có tiền tố [tên thư mục] + \ vá hậu tố Mask
     *
     * @var string
     */
    protected $maskClass = 'ExampleMask';

    /**
     * tên collection mặt nạ
     *
     * @var string
     */
    protected $maskCollectionClass = 'MaskCollection';

    /**
     * @var boolean $hasSortby
     */
    protected $hasSortby = false;

    /**
     * @var array $sortByRules kiểu sắp xếp
     */

    protected $sortByRules = [
        1 => 'created_at-DESC'
    ];
    /**
     * phương thức sap91 xếp
     *
     * @var array [type => Method]
     */
    protected $sortByMethods = [];
    /**
     * @var array $defaultSortBy
     */
    protected $defaultSortBy = [];

    protected $responseMode = 'default';
    protected $isBuildJoin = false;
    protected $isBuildSelect = false;

    protected $isMLCFilter = false;


    final public function mode($mode = null)
    {
        if (in_array($mode, ['resource', 'mask', 'collection', 'default', 'raw'])) $this->responseMode = $mode;
        return $this;
    }

    /**
     * tương tự filter
     * lấy ra kết quả bao gồm paginate
     * @param Illuminate\Http\Request $request
     * @param array $args
     * 
     * @return \Saola\Core\asks\MaskCollection
     */
    final public function getResults($request, array $args = [])
    {
        $this->fire('prepareGetResults', $this, $request, $args);
        // xu ly truc khi loc data
        $this->beforeFilter($request);
        // build query
        $this->buildFilter($request);
        // merge tham so vs paginate
        $args = $this->parsePaginateParam($request, $args);
        // lấy kết qua
        // dd($args);
        $this->fire('beforeGetResults', $this, $request, $args);

        if (!$this->hasSortby && !isset($args['@orderBy']) && !isset($args['@order_by']) && $this->defaultSortBy) {
            $args['@order_by'] = $this->defaultSortBy;
        }
        $results = $this->get($args);
        // nếu tham số có yêu cau paginate
        if ($this->hasPaginateParam) {
            if ($params = array_remove_key($request->all(), 'page')) {
                // them query string vào url
                $results->appends($params);
            }
        }

        $rs = $this->parseCollection($results);
        $this->fire('afterGetResults', $this, $request, $rs);
        return $rs;
    }


    /**
     * tương tự filter
     * lấy ra kết quả bao gồm paginate
     * @param Illuminate\Http\Request $request
     * @param array $args
     * 
     * @return \Saola\Core\asks\MaskCollection
     */
    final public function countResults($request, array $args = [])
    {
        $this->fire('prepareCountResults', $this, $request, $args);
        $this->beforeFilter($request);
        $this->buildFilter($request);
        $this->fire('beforeCountResults', $this, $request, $args);
        return $this->count($args);
    }


    /**
     * lấy dữ liễu theo tham số 
     *
     * @param array $args
     * @return \Saola\Core\asks\MaskCollection
     */
    final public function getData(array $args = [])
    {
        $this->fire('beforegetData', $this, $args);
        $rs = $this->parseCollection($this->get($args));
        $this->fire('aftergetResults', $this, $args, $rs);
        return $rs;
    }


    /**
     * theme tham số lấy dữ liệu đã xóa hoặc chưa
     *
     * @param boolean $status
     * @return static
     */
    final public function trashed($status = true)
    {
        $this->params['@trashed'] = $status;
        return $this;
    }

    /**
     * thêm tham số lấy dử liệu chưa xóa
     * @param int $day
     * @return static
     */
    final public function notTrashed($day = null)
    {
        $this->params['@trashed'] = is_numeric($day) && $day > 0 ? $day : false;
        return $this;
    }

    final public function resetTrashed()
    {
        unset($this->params['@trashed']);
        return $this;
    }

    /**
     * chuẩn bị cho sự kiện filter
     * @param Request
     * 
     * @return void
     */
    final public function buildFilter($request)
    {
        $this->buildSearch($request);
        $this->prepareFilter($request);
        $this->buildEager();
        $this->buildJoin();
        $this->buildSelect();
    }

    /**
     * ham lấy và lọc dử liệu
     * @param illuminate\Http\Request $request
     * @param array $args
     * 
     * @return LengthAwarePaginator|MaskCollection|Model[]|SQLModel[]|MongoModel[]
     */
    final public function getFilter($request, array $args = [])
    {
        $this->fire('preparegetFilter', $this, $request, $args);
        $this->beforeFilter($request);
        $this->buildFilter($request);
        $args = array_merge($this->getPaginateArgs($request), $args);
        $this->fire('beforegetFilter', $this, $request, $args);
        if (!$this->hasSortby && !isset($args['@orderBy']) && !isset($args['@order_by']) && $this->defaultSortBy) {
            $args['@order_by'] = $this->defaultSortBy;
        }

        $rs = $this->get($args);
        $this->fire('aftergetFilter', $this, $request, $args, $rs);
        return $rs;
    }

    /**
     * ham lấy và lọc dử liệu
     * @param array $args
     * @param boolean $useConfig
     * @return Model|SQLModel|MongoModel
     */
    final public function getDetail(array $args = [], $useConfig = true)
    {
        $this->fire('beforegetDetail', $this, $args);
        if ($useConfig) {
            $this->buildJoin();
            $this->buildSelect();
            $this->buildEager();
            $this->buildGroupBy();
        }
        $rs = $this->first($args);
        $this->fire('aftergetDetail', $this, $args, $rs);
        return $rs;
    }


    /**
     * ham lấy và lọc dử liệu
     * 
     * @return Model|SQLModel|MongoModel
     */
    final public function getFormData(array $args = [])
    {
        $this->fire('beforegetFormData', $this, $args);
        $this->beforeGetFormData($args);
        $this->buildJoin();
        $this->buildSelect();
        $this->buildGroupBy();
        $rs = $this->first($args);
        $this->fire('aftergetFormData', $this, $args, $rs);
        return $rs;
    }



    /**
     * ham lấy và lọc dử liệu
     * @param Request $request
     * @param array $args
     * 
     * @return Resource Collection 
     */
    final public function filter($request, array $args = [])
    {
        $this->fire('beforefilter', $this, $request, $args);
        $rs = $this->parseCollection(
            $this->getFilter($request, $args)
        );
        $this->fire('afterfilter', $this, $request, $args, $rs);
        return $rs;
    }



    /**
     * ham lấy và lọc dử liệu
     * @param int|array $args
     * @param bool $useConfig
     * @return Mask
     */
    final public function detail($args, $useConfig = true)
    {
        $d = [];
        if (is_array($args)) {
            $d = $args;
        } else {
            $d[$this->_primaryKeyName] = $args;
        }
        $this->fire('beforedetail', $this, $args);
        if ($data = $this->getDetail($d, $useConfig)) {
            $rs = $this->parseDetail($data);
            $this->fire('afterdetail', $this, $args, $rs);
            return $rs;
        }

        return null;
    }






    /**
     * Chuẩn hóa thành mask hoặc resource
     *
     * @param LengthAwarePaginator $collection
     * @return \Saola\Core\asks\MaskCollectionExamples
     */
    final public function parseCollection($collection)
    {
        return $this->responseMode == 'mask' ? $this->maskCollection($collection, $this->total()) : ($this->responseMode == 'resource' ? $this->resourceCollection($collection) : ($collection
        )
        );
    }

    /**
     * chuẩn hóa chi tiết bản ghi
     *
     * @param Model|SQLModel|MongoModel $data
     * @return Mask
     */
    final public function parseDetail($data)
    {
        if (!$data) return null;
        $rs = $this->responseMode == 'mask' ? $this->mask($data) : ($this->responseMode == 'resource' ? $this->resource($data) : ($data));
        if ($rs && is_object($rs) && method_exists($rs, '__lock')) $rs->__lock();
        return $rs;
    }





    final protected function getResourceClass($class = null)
    {
        if (!$class) $class = $this->resourceClass;
        if (class_exists($class)) {
            return $class;
        } elseif (class_exists($c = $this->resourceNamespace . "\\" . $class)) {
            return $c;
        }
        return null;
    }

    final protected function getResourceCollectionClass($class = null)
    {
        if (!$class) $class = $this->collectionClass;
        if (class_exists($class)) {
            return $class;
        } elseif (class_exists($c = $this->resourceNamespace . "\\" . $class)) {
            return $c;
        }
        return null;
    }





    /**
     * tạo resource cho api
     * @param Model|SQLModel|MongoModel
     * @return resource
     */
    final public function resource($data)
    {
        if (!$data) return $data;

        if ($resourceClass = $this->getResourceClass()) {
            $rc = new ReflectionClass($resourceClass);
            return $rc->newInstanceArgs([$data]);
        }
        return $data;
    }



    /**
     * tạo resource collection
     * @param collection
     * 
     * @return ResourceCollection
     */
    final public function resourceCollection($data)
    {
        if (!count($data)) return [];
        if ($collectionClass = $this->getResourceCollectionClass()) {
            $rc = new ReflectionClass($collectionClass);
            return $rc->newInstanceArgs([$data]);
        }
        return $data;
    }



    final protected function getMaskClass($class = null)
    {
        if (!$class) $class = $this->maskClass;
        if (class_exists($class)) {
            return $class;
        } elseif (class_exists($c = $this->appMaskNamespace . "\\" . $class)) {
            return $c;
        } elseif (class_exists($c = $this->maskNamespace . "\\" . $class)) {
            return $c;
        }
        return null;
    }

    final protected function getMaskCollectionClass($class = null)
    {
        if (!$class) $class = $this->maskCollectionClass;
        if (class_exists($class)) {
            return $class;
        } elseif (class_exists($c = $this->appMaskNamespace . "\\" . $class)) {
            return $c;
        } elseif (class_exists($c = $this->maskNamespace . "\\" . $class)) {
            return $c;
        }
        return null;
    }





    /**
     * tạo resource cho api
     * @param model
     * @return resource
     */
    final public function mask($data)
    {
        if (!$data) return $data;

        if ($resourceClass = $this->getMaskClass()) {
            $rc = new ReflectionClass($resourceClass);
            $mask = $rc->newInstanceArgs([$data]);
            $mask->__lock();
            return $mask;
        }
        return $data;
    }



    /**
     * tạo resource collection
     * @param collection
     * 
     * @return MaskCollection|null
     */
    final public function maskCollection($data, $total = 0)
    {
        if ($collectionClass = $this->getMaskCollectionClass()) {
            $rc = new ReflectionClass($collectionClass);
            return $rc->newInstanceArgs([$data, $total]);
        }
        return new ExampleCollection($data, $total);
    }

    final public function ignoreFilter(...$args)
    {
        if (count($args)) {
            foreach ($args as $arg) {
                if (is_array($arg))
                    foreach ($arg as $key => $value) {
                        if (!is_numeric($key)) {
                            $this->_ignoreFilter[] = $key;
                            $this->_ignoreFilter[] = $value;
                        } else {
                            $this->_ignoreFilter[] = $value;
                        }
                    }
                else
                    $this->_ignoreFilter[] = $arg;
            }
        }
        return $this;
    }

    /**
     * chuẩn bị để thực hiện filter
     * @param Request $request
     */
    final public function prepareFilter($request)
    {
        $fields = array_merge([$this->required], $this->getFields());
        $this->buildOrderBy($request);
        $disableWhereColumns = is_array($this->ignoreRequestParams) ? $this->ignoreRequestParams : [];
        if ($data = $request->all()) {
            $prefix = '';
            $modelType = $this->_model->__getModelType__();
            if ($modelType == 'default' && ($pre = $this->getTable())) {
                $prefix = $pre . '.';
            }
            foreach ($data as $key => $value) {
                // build order by from request
                if (preg_match('/^orderby_/i', $key)) {
                    $f = preg_replace('/^orderby_/i', '', $key);
                    if (in_array($f, $this->_ignoreFilter))
                        continue;
                    $t = strtoupper($value) != 'DESC' ? 'ASC' : 'DESC';
                    if ($this->sortable && is_array($this->sortable) && (isset($this->sortable[$f]) || in_array($f, $this->sortable))) {

                        if (in_array($this->sortable[$f], $this->_ignoreFilter))
                            continue;
                        $this->hasSortby = true;
                        if (isset($this->sortable[$f])) {
                            $this->orderBy($this->sortable[$f], $t);
                        } else {
                            $this->orderBy($f, $t);
                        }
                    } elseif ($this->sortRawable && is_array($this->sortRawable) && (isset($this->sortRawable[$f]) || in_array($f, $this->sortRawable))) {

                        if (in_array($this->sortRawable[$f], $this->_ignoreFilter))
                            continue;
                        $this->hasSortby = true;
                        if (isset($this->sortRawable[$f])) {
                            $this->orderByRaw($this->sortRawable[$f] . ' ' . $t);
                        } else {
                            $this->orderByRaw($f . ' ' . $t);
                        }
                    } elseif (!preg_match('/\w\.\w/', $f)) {
                        if (in_array($f, $fields)) {
                            $this->hasSortby = true;
                            $this->orderBy($f, $t);
                        }
                    }
                } elseif (is_string($value) && strlen($value)) {
                    // lấy theo tham số request (set where)

                    if (in_array($value, $this->_ignoreFilter))
                        continue;

                    if (!(array_key_exists($key, $this->ignoreValues) && ((is_array($this->ignoreValues[$key]) && in_array($value, $this->ignoreValues[$key])) || (!is_array($this->ignoreValues[$key]) && $this->ignoreValues[$key] == $value))) && !in_array($key, $disableWhereColumns)) {

                        if ($this->whereable && is_array($this->whereable) && ((isset($this->whereable[$key]) && !in_array($this->whereable[$key], $this->_ignoreFilter)) || in_array($key, $this->whereable))) {

                            if (isset($this->whereable[$key])) {
                                $this->where($this->whereable[$key], $value);
                            } else {
                                $this->where($key, $value);
                            }
                        } elseif (in_array($key, $fields)) {
                            $this->where($prefix . $key, $value);
                        }
                    }
                } elseif (is_array($value) && count($value)) {
                    $value = array_values($value);
                    // lấy theo tham số request (set where)
                    if (!(array_key_exists($key, $this->ignoreValues) && ((is_array($this->ignoreValues[$key]) && in_array($value, $this->ignoreValues[$key])) || (!is_array($this->ignoreValues[$key]) && $this->ignoreValues[$key] == $value))) && !in_array($key, $disableWhereColumns)) {
                        if ($this->whereable && is_array($this->whereable) && (isset($this->whereable[$key]) || in_array($key, $this->whereable))) {
                            if (isset($this->whereable[$key])) {
                                $this->whereIn($this->whereable[$key], $value);
                            } else {
                                $this->whereIn($key, $value);
                            }
                        } elseif (in_array($key, $fields)) {
                            $this->whereIn($prefix . $key, $value);
                        }
                    }
                }
            }
        }


        $this->buildGroupBy();
        return $this;
    }



    final public function buildEager()
    {
        if (count($this->withable)) {
            foreach ($this->withable as $key => $rela) {
                $this->with($rela);
            }
        }
        if (count($this->withCountable)) {
            foreach ($this->withCountable as $key => $rela) {
                $this->withCount($rela);
            }
        }

        if (count($this->loadable)) {
            foreach ($this->loadable as $key => $rela) {
                $this->load($rela);
            }
        }
    }



    /**
     * build search
     * @param Request
     * 
     */
    final protected function buildSearch($request)
    {
        $s = strlen($request->search) ? $request->search : (
            strlen($request->s) ? $request->s : (
                strlen($request->keyword) ? $request->keyword : (
                    strlen($request->keywords) ? $request->keywords : (
                        strlen($request->tim) ? $request->tim : ($request->timkiem)
                    )
                )
            )
        );
        if (strlen($s)) {
            if ($sb = $this->getSearchFields($request)) {
                $this->mlcSearchActive = true;
                $this->addSearch($s, $sb);
            }
        }
    }




    /**
     * order by
     * @param Request
     */
    final protected function buildOrderBy($request)
    {
        $odb = $request->orderby ?? $request->sortby;

        $odb = $request->orderby ?? $request->sortby;
        $sortBy = is_array($odb) ? array_map('strtolower', $odb) : ($odb ? [strtolower($odb)] : []);

        $orderBy = [];
        $needOrderBy = true;
        if ($sortBy) {
            foreach ($sortBy as $key) {
                if (array_key_exists($key, $this->sortByRules) && !in_array($key, $this->_ignoreFilter)) {

                    $o = $this->sortByRules[$key];

                    // if (in_array($o, $this->_ignoreFilter))
                    //     continue;
                    if (is_array($o))
                        $orderBy = array_merge($orderBy, $o);
                    else {
                        if (count($sbp = explode('-', $o)) == 2) {
                            $o = $sbp[0];
                            $type = $sbp[1];
                        } else {
                            $type = $request->sorttype;
                        }
                        if (strtoupper($type) != 'DESC') $type = 'ASC';
                        else $type = 'DESC';
                        $orderBy[$o] = $type;
                    }
                    $needOrderBy = false;
                }
            }
        }
        if ($orderBy) {
            $fields = array_merge([$this->required], $this->getFields());
            /**
             * orderby = [
             * column => ASC|DESC
             * ]
             */
            if (is_array($orderBy)) {
                foreach ($orderBy as $field => $type) {
                    if (in_array($field, $this->_ignoreFilter))
                        continue;
                    $t = strtoupper($type) != 'DESC' ? 'ASC' : 'DESC';
                    if ($this->sortable && is_array($this->sortable) && ((isset($this->sortable[$field]) && !in_array($this->sortable[$field], $this->_ignoreFilter))|| in_array($field, $this->sortable))) {
                        $this->hasSortby = true;
                        if (isset($this->sortable[$field])) {
                            $this->orderBy($this->sortable[$field], $t);
                        } else {
                            $this->orderBy($field, $t);
                        }
                    } elseif ($this->sortRawable && is_array($this->sortRawable) && ((isset($this->sortRawable[$field]) && !in_array($this->sortRawable[$field], $this->_ignoreFilter)) || in_array($field, $this->sortRawable))) {
                        $this->hasSortby = true;
                        if (isset($this->sortRawable[$field])) {
                            $this->orderByRaw($this->sortRawable[$field] . ' ' . $t);
                        } else {
                            $this->orderByRaw($field . ' ' . $t);
                        }
                    } elseif (!preg_match('/\w\.\w/', $field)) {
                        if (in_array($field, $fields)) {
                            $this->hasSortby = true;
                            $this->orderBy($field, $t);
                        }
                    }
                }
            } else {
                // nếu có trong bảng sortable
                if (count($sbp = explode('-', $orderBy)) == 2) {
                    $orderBy = $sbp[0];
                    $type = $sbp[1];
                } else {
                    $type = $request->sorttype;
                }
                if (strtoupper($type) != 'DESC') $type = 'ASC';
                else $type = 'DESC';
                $odb = $orderBy;
                if ($this->sortable && is_array($this->sortable) && (isset($this->sortable[$odb]) || in_array($odb, $this->sortable))) {
                    $this->hasSortby = true;
                    if (isset($this->sortable[$odb])) {
                        $this->orderBy($this->sortable[$odb], $type);
                    } else {
                        $this->orderBy($odb, $type);
                    }
                } elseif ($this->sortRawable && is_array($this->sortRawable) && (isset($this->sortRawable[$odb]) || in_array($odb, $this->sortRawable))) {
                    $this->hasSortby = true;
                    if (isset($this->sortRawable[$odb])) {
                        $this->orderByRaw($this->sortRawable[$odb] . ' ' . $type);
                    } else {
                        $this->orderByRaw($odb . ' ' . $type);
                    }
                } elseif (!preg_match('/\w\.\w/', $odb)) {
                    // có trong danh sách cột
                    if (in_array($odb, $fields)) {
                        $this->hasSortby = true;
                        $this->orderBy($odb, $type);
                    }
                } elseif (in_array(strtolower($odb), ['random', 'rand()', '@rand', '@random'])) {
                    // Tự động chuyển đổi RAND() cho PostgreSQL
                    $randomFunc = method_exists($this, 'getRandomFunction') 
                        ? $this->getRandomFunction() 
                        : (method_exists($this, 'isPostgreSQL') && $this->isPostgreSQL() ? 'RANDOM()' : 'RAND()');
                    $this->orderByRaw($randomFunc);
                }
            }
        }
    }

    /**
     * join auto
     */
    final protected function buildJoin()
    {
        if ($this->isBuildJoin) return $this;
        if ($this->joinable) {
            foreach ($this->joinable as $join) {
                $args = $join;
                $fun = array_shift($args);
                call_user_func_array([$this, $fun], $args);
            }
        }
        $this->isBuildJoin = true;
        return $this;
    }

    /**
     * build select
     */
    final protected function buildSelect()
    {
        if ($this->isBuildSelect) return $this;
        if ($this->selectable) {
            $select = [];
            foreach ($this->selectable as $mask => $column) {
                if (is_numeric($mask)) {
                    $select[] = $column;
                } else {
                    $select[] = $column . ' AS ' . $mask;
                }
            }
            $this->select(...$select);
        }

        if ($this->selectRawable) {
            foreach ($this->selectRawable as $select) {
                $this->selectRaw($select);
            }
        }
        $this->isBuildSelect = true;
        return $this;
    }

    final public function buildGroupBy()
    {
        if (count($this->groupable)) {
            foreach ($this->groupable as $column) {
                $this->groupBy($column);
            }
        }
        if (count($this->groupableRaw)) {
            foreach ($this->groupableRaw as $column) {
                $this->groupByRaw($column);
            }
        }
    }


    final public function getSearchFields($request)
    {
        $fields = $this->getFields();
        $sb = $this->searchable;
        $sl = [];
        if ($s = $request->searchby??($request->searchBy??$request->search_by)) {
            
            if ($sb) {
                if (isset($sb[$s])) {
                    if (is_array($sb[$s])) {
                        foreach ($sb[$s] as $key) {
                            $sl[] = $key;
                        }
                    } else {
                        $sl[] = $sb[$s];
                    }
                } elseif (in_array($s, $sb)) {
                    $sl[] = $s;
                }
            } elseif (in_array($s, $fields)) {
                $sl[] = $s;
            }
        } elseif ($sb) {
            foreach ($sb as $key => $value) {
                $sl[] = $value;
            }
        } else {
            $sl = $fields;
        }
        return $sl;
    }




    /**
     * xử lý order by cho hàm lấy sản phẩm
     *
     * @param array|string $sortBy
     * @return void
     */
    public function parseSortBy($sortBy)
    {
        if (is_array($sortBy)) {
            // truong hop mang toan index la so
            if (Arr::isNumericKeys($sortBy)) {
                foreach ($sortBy as $by) {
                    $this->checkSortBy($by);
                }
            } else {
                foreach ($sortBy as $column => $type) {
                    if (is_numeric($column)) {
                        $this->checkSortBy($type);
                    } else {
                        $this->orderBy($column, $type);
                    }
                }
            }
        } else {
            $this->checkSortBy($sortBy);
        }
    }


    /**
     * kiểm tra tính hợp lệ của tham sớ truyền vào
     *
     * @param string $sortBy
     * @param string $type
     * @return void
     */
    protected function checkSortBy($sortBy = null, $type = null)
    {
        if (in_array($sortBy, $this->sortByRules)) {
            $this->orderByRule($sortBy);
        } elseif (array_key_exists($sortBy, $this->sortByRules)) {
            $this->orderByRule($this->sortByRules[$sortBy]);
        } elseif (array_key_exists($sortBy, $this->sortByMethods)) {
            if (method_exists($this, $this->sortByMethods[$sortBy])) {
                call_user_func_array([$this, $this->sortByMethods[$sortBy]], [$type]);
            }
        } elseif ($sortBy) {
            $a = explode('-', $sortBy);
            if (count($a) == 2) {
                $this->orderBy($a[0], $a[1]);
            } else {
                $this->orderBy($sortBy, $type ? $type : 'ASC');
            }
        }
    }


    /**
     * order by rule
     *
     * @param string $rule
     * @return void
     */
    protected function orderByRule($rule)
    {
        if ($rule == 'rand()' || $rule == 'random()') {
            $this->orderByRaw($this->getRandomFunction());
        } else {
            $a = explode('-', $rule);
            $this->orderBy($a[0], $a[1]);
        }
    }





    /**
     * lấy tham số paginate
     * @param Request $request
     * @param array $args
     * @param array
     */
    final public function parsePaginateParam($request, array $args = [])
    {
        if (isset($args['@paginate']) || isset($args['@limit']) || !$this->paginate) return $args;
        if ($request->paginate && ($pn = to_number($request->paginate)) > 0) {
            $args['@paginate'] = $pn;
        } elseif ($request->per_page && ($pz = to_number($request->per_page)) > 0) {
            $args['@paginate'] = $pz;
        } elseif ($this->_paginate) {
            $args['@paginate'] = $this->_paginate;
        } elseif ($this->perPage) {
            $args['@paginate'] = $this->perPage;
        }
        return $args;
    }

    /**
     * lay thong so phan trang
     * @param Request $request
     * 
     * @return array        
     * [
     *     'per_page' => integer
     *     'page' => integer
     * ]
     */

    final public function getPaginateInfo($request)
    {
        $page = 1;
        $per_page = $this->perPage;

        // nếu có tham số per page
        if ($request->per_page) {
            $per_page = (int) $request->per_page;
            if ($per_page < 1) $per_page = $this->perPage;
            // start

        }
        if ($request->page) {
            $page = (int) $request->page;
            if ($page < 1) $page = 1;
        } elseif ($request->page) {
            $page = (int) $request->page;
            if ($page < 1) $page = 1;
        }
        $current_page = $page;
        return compact('page', 'per_page', 'current_page');
    }


    /**
     * lấy tham số phân trang từ request
     * @param Request
     * 
     * @return array      [vị trí bắt dâu, số lượng]
     */

    final public function getPaginateArgs($request)
    {
        $args = []; // mảng truy vấn
        $paginate = $this->getPaginateInfo($request);
        // $pos = ($paginate['page'] - 1) * $paginate['per_page'];
        // limit bang skip và take 
        // $args['@limit'] = [$pos, $paginate['per_page']];
        $args['@paginate'] = $paginate['per_page'];
        return $args;
    }


    /**
     * phân tich và lấy dữ liệu phân trang
     * @param Request $request
     * @param int $count                 Số lượng bản ghi
     * 
     * @return array       
     * 
     * [
     *     'per_page' => integer // so ket qua moi trang
     *     'page' => integer // trang hiện tại
     *     'page_total' => integer // tất cả các trang
     * ]
     */
    final public function getPaginateData($request, $count = 0)
    {
        $data = $this->getPaginateInfo($request);
        $page_total = (int) ($count / $data['per_page']);
        if ($count % $data['per_page']) {
            $page_total++;
        }

        $data['page_total'] = $page_total;
        return $data;
    }

    final public function buildDateFilterQuery($request, $col = 'date', $ignore = null)
    {
        $view_mode = 'all';
        $dateSet = 0;
        if ($ignore == 'date') {
            if ($request->date && strtolower($request->date) != 'all' && $date = strtodate($request->date)) {
                $this->where($col, "$date[year]-$date[month]-$date[day]");
                $dateSet = 1;
            } else {
                if ($request->from_date && $fd = strtodate($request->from_date)) {
                    $this->whereDate($col, '>=', "$fd[year]-$fd[month]-$fd[day]");
                    $dateSet = 1;
                }
                if ($request->to_date && $td = strtodate($request->to_date)) {
                    $this->whereDate($col, '<=', "$td[year]-$td[month]-$td[day]");
                    $dateSet = 1;
                }
            }
        }
        if ($dateSet == 1) $view_mode = 'date';
        else {
            $sy = 0;
            $sm = 0;
            $sd = 0;
            if ($request->year && $ignore != 'year') {
                $sy = 1;
                $this->whereYear($col, $request->year);
                $view_mode = 'year';
            }
            if ($sy && $request->month && $ignore != 'month') {
                $sm = 1;
                $this->whereMonth($col, $request->month);
                $view_mode = 'month';
            }
            if ($sm && $request->day && $ignore != 'day') {
                $sd = 1;
                $this->whereDay($col, $request->day);
                $view_mode = 'day';
            }
            if (!$sy && strtolower($request->date) != 'all' && $ignore != 'date') {
                $this->whereDate($col, date('Y-m-d'));
                $view_mode = 'day';
            }
        }
        return $view_mode;
    }

    public function setJoinable(array $joinable = [])
    {
        if (is_array($joinable) && count($joinable)) {
            $this->joinable = array_merge($this->joinable, $joinable);
        }
        return $this;
    }

    public function setSearchable(array $params = [])
    {
        if (is_array($params) && count($params)) {
            $this->searchable = $params;
        }
        return $this;
    }
    public function setWith(...$relas)
    {
        foreach ($relas as $key => $value) {
            $this->withable[] = $value;
        }
        return $this;
    }
    public function setWithCount(...$relas)
    {
        foreach ($relas as $key => $value) {
            $this->withCountable[] = $value;
        }
        return $this;
    }
    public function setLoad(...$relas)
    {
        foreach ($relas as $key => $value) {
            $this->loadable[] = $value;
        }
        return $this;
    }

    public function setSortable(array $params = [])
    {
        if (is_array($params) && count($params)) {
            $this->sortable = array_merge($this->sortable, $params);
        }
        return $this;
    }
    public function setWhereable(array $params = [])
    {
        if (is_array($params) && count($params)) {
            $this->whereable = array_merge($this->whereable, $params);
        }
        return $this;
    }
    public function addSelectable(array $params = [])
    {
        if (is_array($params) && count($params)) {
            $this->selectable = array_merge($this->selectable, $params);
        }
        return $this;
    }
    public function setSelectable(array $params = [])
    {
        if (is_array($params) && count($params)) {
            $this->selectable = $params;
        }
        return $this;
    }
    public function setSelectRaw(array $params = [])
    {
        if (is_array($params) && count($params)) {
            $this->selectRawable = array_merge($this->selectRawable, $params);
        }
        return $this;
    }
    public function setGroupBy(...$params)
    {
        if (is_array($params) && count($params)) {
            $this->groupable = array_merge($this->groupable, $params);
        }
        return $this;
    }
    public function setGroupByRaw(...$params)
    {
        if (is_array($params) && count($params)) {
            $this->groupableRaw = array_merge($this->groupableRaw, $params);
        }
        return $this;
    }

    public function setResourceClass(string $resourceClass = null)
    {
        $this->resourceClass = $resourceClass;
        return $this;
    }

    public function setCollectionClass(string $collectionClass = null)
    {
        $this->collectionClass = $collectionClass;
        return $this;
    }
}
