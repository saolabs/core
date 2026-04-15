<?php

namespace Saola\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Saola\Core\Languages\Locale;

/**
 * @property \Saola\Core\Models\Model $_model
 * @method $this buildMLC(array $params) tạo query
 */
trait BaseQuery
{
    use DatabaseHelper;

    
    protected $table = '';

    /**
     * @var string
     */

    protected $required = MODEL_PRIMARY_KEY;

    /**
     * @var integer
     */

    public $totalCount = 0;

    /**
     * du lieu lan gan day
     * @var array
     */
    protected $lastParams = [];


    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */

    protected $lastQueryBuilder = null;



    /**
     * sql functions
     * @var array $sqlclause
     */
    protected $sqlclause = [
        // select
        'select' => 'select',
        'selectraw' => 'selectRaw',
        'addselect' => 'addSelect',
        'addselectraw' => 'addSelectRaw',
        // from
        'from' => 'from',
        'fromraw' => 'fromRaw',
        // join
        'join' => 'join',
        'leftjoin' => 'leftJoin',
        'rightjoin' => 'rightJoin',
        'fullouterjoin' => 'fullOuterJoin',
        'crossjoin' => 'crossJoin',
        'joinraw' => 'joinRaw',
        'leftjoinraw' => 'leftJoinRaw',
        'rightjoinraw' => 'rightJoinRaw',
        'leftjoinwhere' => 'leftJoinWhere',
        'rightjoinwhere' => 'rightJoinWhere',
        // where
        'where' => 'where',
        'wherenot' => 'whereNot',
        'whereraw' => 'whereRaw',
        'wherein' => 'whereIn',
        'wherenotin' => 'whereNotIn',
        'wherebetween' => 'whereBetween',
        'wherenotbetween' => 'whereNotBetween',
        'whereday' => 'whereDay',
        'wheremonth' => 'whereMonth',
        'whereyear' => 'whereYear',
        'wheredate' => 'whereDate',
        'wheretime' => 'whereTime',
        'wherenull' => 'whereNull',
        'wherenotnull' => 'whereNotNull',
        'wherecolumn' => 'whereColumn',
        'wherejsoncontains' => 'whereJsonContains',
        'wherejsondoesntcontain' => 'whereJsonDoesntContain',
        'wherejsonlength' => 'whereJsonLength',
        'wherejsonpath' => 'whereJsonPath',
        'wherejsonpathexists' => 'whereJsonPathExists',
        'wherejsonpathdoesntexist' => 'whereJsonPathDoesntExist',
        'whereexists' => 'whereExists',
        'wherenotexists' => 'whereNotExists',
        'whereintegerinraw' => 'whereIntegerInRaw',
        'whereintegernotinraw' => 'whereIntegerNotInRaw',
        'whererownumber' => 'whereRowNum',
        'wherefulltext' => 'whereFullText',
        'whererelation' => 'whereRelation',
        'wherehas' => 'whereHas',
        'wheredoesnthave' => 'whereDoesntHave',
        'wherehasmorph' => 'whereHasMorph',
        'wheredoesnthavemorph' => 'whereDoesntHaveMorph',
        'wherelike' => 'whereLike',
        'wherenotlike' => 'whereNotLike',
        'whereregexp' => 'whereRegexp',
        'wherenotregexp' => 'whereNotRegexp',
        // orwhere
        'orwhere' => 'orWhere',
        'orwherenot' => 'orWhereNot',
        'orwhereraw' => 'orWhereRaw',
        'orwherein' => 'orWhereIn',
        'orwherenotin' => 'orWhereNotIn',
        'orwherebetween' => 'orWhereBetween',
        'orwherenotbetween' => 'orWhereNotBetween',
        'orwhereday' => 'orWhereDay',
        'orwheremonth' => 'orWhereMonth',
        'orwhereyear' => 'orWhereYear',
        'orwheredate' => 'orWhereDate',
        'orwheretime' => 'orWhereTime',
        'orwherecolumn' => 'orWhereColumn',
        'orwherenull' => 'orWhereNull',
        'orwherenotnull' => 'orWhereNotNull',
        'orwherejsoncontains' => 'orWhereJsonContains',
        'orwherejsondoesntcontain' => 'orWhereJsonDoesntContain',
        'orwherejsonlength' => 'orWhereJsonLength',
        'orwherejsonpath' => 'orWhereJsonPath',
        'orwhereexists' => 'orWhereExists',
        'orwherenotexists' => 'orWhereNotExists',
        'orwherefulltext' => 'orWhereFullText',
        'orwherehas' => 'orWhereHas',
        'orwheredoesnthave' => 'orWhereDoesntHave',
        'orwherelike' => 'orWhereLike',
        'orwherenotlike' => 'orWhereNotLike',
        'orwhereregexp' => 'orWhereRegexp',
        'orwherenotregexp' => 'orWhereNotRegexp',
        // groupby
        'groupby' => 'groupBy',
        'groupbyraw' => 'groupByRaw',
        // having
        'having' => 'having',
        'havingraw' => 'havingRaw',
        'havingbetween' => 'havingBetween',
        'havingin' => 'havingIn',
        'havingnotin' => 'havingNotIn',
        'havingnull' => 'havingNull',
        'havingnotnull' => 'havingNotNull',
        'orhaving' => 'orHaving',
        'orhavingraw' => 'orHavingRaw',
        'orhavingbetween' => 'orHavingBetween',
        'orhavingin' => 'orHavingIn',
        'orhavingnotin' => 'orHavingNotIn',
        'orhavingnull' => 'orHavingNull',
        'orhavingnotnull' => 'orHavingNotNull',
        // orderby
        'orderby' => 'orderBy',
        'orderbyraw' => 'orderByRaw',
        'orderbydesc' => 'orderByDesc',
        'latest' => 'latest',
        'oldest' => 'oldest',
        'inrandomorder' => 'inRandomOrder',
        'reorder' => 'reorder',
        // limit
        'skip' => 'skip',
        'take' => 'take',
        'limit' => 'limit',
        'offset' => 'offset',
        'forpage' => 'forPage',
        
        // distinct
        'distinct' => 'distinct',
        
        // eager loading
        'with' => 'with',
        'without' => 'without',
        'load' => 'load',
        
        // relationship counts
        'withcount' => 'withCount',
        'withavg' => 'withAvg',
        'withsum' => 'withSum',
        'withmin' => 'withMin',
        'withmax' => 'withMax',
        'withexists' => 'withExists',
        
        // conditional clauses
        'when' => 'when',
        'unless' => 'unless',
        'tap' => 'tap',
        
        // union
        'union' => 'union',
        'unionall' => 'unionAll',
        
        // morphTo conditions
        'wheremorph' => 'whereMorph',
        'orwheremorph' => 'orWhereMorph',
        'wheremorphin' => 'whereMorphIn',
        'wheremorphnotin' => 'whereMorphNotIn',
        'orwheremorphin' => 'orWhereMorphIn',
        'orwheremorphnotin' => 'orWhereMorphNotIn',
        
        // locks for SELECT
        'lockforshare' => 'lockForShare',
        'lockforupdate' => 'lockForUpdate',
        
        // Common Table Expression (CTE)
        'withcte' => 'withCTE',
        'withrecursive' => 'withRecursive',
        
        // advanced subqueries
        'whereraw' => 'whereRaw',
        'havingraw' => 'havingRaw',
        'selectraw' => 'selectRaw',
        'fromraw' => 'fromRaw',
        'orwhereraw' => 'orWhereRaw',
        'orhavingraw' => 'orHavingRaw',
        
        // window functions
        'window' => 'window'
    ];



    /**
     * các tham số mặc định
     * @var array
     */
    protected $defaultParams = [];

    /**
     * các tham số mặc định
     * @var array
     */
    protected $defaultConditions = [];

    /**
     * @var array $defaultValues giá trị mặc đĩnh
     */
    protected $defaultValues = [];

    /**
     * tham số có thể xóa
     * @var array
     */
    protected $fixableParams = [];



    /**
     * @var array $params tham so truy van
     */
    protected $params = [];

    /**
     * @var array $actions tham so truy van
     */
    protected $actions = [];

    protected $__queryAfter = [];

    /**
     * @var array $args tham so truy van
     */
    protected $args = [];

    protected $loadAfter = [];
    /**
     * phan trang
     * @var integer
     */
    protected $_paginate = 0;

    protected $__searchMode__ = 'all'; // all | raw

    protected $__searchType__ = 'word'; // start || end

    protected $__searchRules__ = [];

    protected $mlcTable = 'multi_language_contents';

    protected $isJoinedMLC = false;

    protected $mlcSearchKeywords = null;

    protected $mlcSearchActive = false;

    protected $defaultLocale = null;


    protected $isSelectQuery = false;
    /**
     * Set model
     */
    public function setModel()
    {
        $this->_model = app()->make(
            $this->getModel()
        );
    }




    /**
     * đưa tất cả về 0 =))))
     *
     */
    final public function reset($all = false)
    {
        $this->totalCount = 0;
        $this->query = null;
        $this->lastQueryBuilder = null;
        $this->lastParams = [];

        if ($all) {
            $this->removeFixableParam();
        }
        return $this;
    }





    /**
     * thêm tham số
     * @param string|integer|float $paramKey
     */
    final public function addDefaultParam($paramKey = null, ...$params)
    {
        $t = count($params);
        if ($t == 1) {
            if ($paramKey) {
                $this->defaultParams[$paramKey] = [$paramKey, $params[0]];
            }
        } elseif ($t > 1) {
            if ($paramKey) {
                $this->defaultParams[$paramKey] = $params;
            } else {
                $this->defaultParams[] = $params;
            }
        }

        return $this;
    }


    /**
     * xóa tham số mặc định
     * @param string $paramKey
     */
    final public function resetDefaultParams($paramKey = null)
    {
        if (is_null($paramKey)) $this->defaultParams = [];
        else unset($this->defaultParams[$paramKey]);
        return $this;
    }

    /**
     * xóa tham số mặc định
     * @param string $paramKey
     */
    final public function removeDefaultParam($paramKey = null)
    {
        return $this->resetDefaultParams($paramKey);
    }



    /**
     * thêm diều kiện
     * @param string $conditionName
     * @param array ...$params
     * @return static
     */
    final public function addDefaultCondition($conditionName = null, ...$params)
    {

        if (count($params) > 1) {
            if (is_null($conditionName)) {
                $this->defaultConditions[] = $params;
            } else {
                $this->defaultConditions[$conditionName] = $params;
            }
        }
        return $this;
    }


    /**
     * xóa giá trị tham số mặt định
     * @param string $conditionName
     * @return static
     */
    final public function removeDefaultConditions($conditionName = null)
    {
        if (!is_null($conditionName)) unset($this->defaultConditions[$conditionName]);
        else $this->defaultConditions = [];
        return $this;
    }
    /**
     * xóa giá trị tham số mặt định
     * @param string $conditionName
     * @return static
     */
    final public function removeDefaultCondition($conditionName = null)
    {
        return $this->removeDefaultConditions($conditionName);
    }

    /**
     * thêm giá trị mặc dịnh
     *
     * @param array|string $key
     * @param mixed $value
     * @return static
     */
    public function addDefaultValue($key, $value = null)
    {
        if (is_string($key)) {
            $this->defaultValues[$key] = $value;
        } elseif (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->defaultValues[$k] = $v;
            }
        }
        return $this;
    }

    /**
     * thêm tham số có thể override
     * @param string|array $name có thể là tên cột hoặc một mảng
     * @param mixed        $value giá trị
     *
     * @return static
     */
    final public function addFixableParam($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                if (is_numeric($key)) {
                    if (is_string($val)) {
                        $this->fixableParams[$val] = $value;
                    }
                } else {
                    $this->fixableParams[$key] = $val;
                }
            }
        } elseif (is_string($name)) {
            $this->fixableParams[$name] = $value;
        }
        return $this;
    }

    /**
     * xóa giá trị tham số mặt định
     * @param array|string $name
     *
     * @return static
     */
    final public function removeFixableParam($name = null)
    {
        if (is_array($name)) {
            foreach ($name as $val) {
                unset($this->fixableParams[$val]);
            }
        } elseif (is_string($name)) {
            unset($this->fixableParams[$name]);
        } else {
            $this->fixableParams = [];
        }
        return $this;
    }

    /**
     * xóa giá trị tham số mặt định
     * @param array|string $name
     *
     * @return static
     */
    final public function removeDefaultValue($name = null)
    {
        if (is_array($name)) {
            foreach ($name as $val) {
                unset($this->defaultValues[$val]);
            }
        } elseif (is_string($name)) {
            unset($this->defaultValues[$name]);
        } else {
            $this->defaultValues = [];
        }
        return $this;
    }



    /**
     * xóa giá trị tham số mặt định
     * @return static
     */
    final public function clear()
    {
        $this->resetDefaultParams();
        $this->removeDefaultValue();
        $this->removeFixableParam();
        return $this;
    }


    /**
     * kiểm tra field
     * @param string $field tên cột
     * @return boolean
     */
    final public function checkField($field)
    {
        return in_array($field, $this->getFields());
    }



    public function getFields()
    {
        return array_merge([$this->_primaryKeyName], $this->_model->__get_fields());
    }


    final public function queryAfter($action)
    {
        if (is_callable($action)) {
            $this->__queryAfter[] = $action;
        }
        return $this;
    }

    protected function __mlcQuery($data = [])
    {
        if (method_exists($this, 'buildMLC') && is_array($data)) {
            return $this->buildMLC($data);
        }
        return $this;
    }



    /**
     * tạo qury builder
     * @param array $args Mảng các tham số hoặc têm hàm và tham số hàm
     * @return \Illuminate\Database\Eloquent\Builder
     *
     */
    final public function query($args = [])
    {
        $this->fire('beforequery', $args);
        $keywords = null;
        $search_by = null;
        $orderby = null;
        $limit = null;
        $actions = [];
        $parameters = $this->params;
        if (count($parameters)) {
            $args = array_merge($parameters, (array) $args);
        }

        if ($this->fixableParams) {
            $args = array_merge($this->fixableParams, $args);
        }
        // pewfix

        $prefix = '';
        $modelType = $this->_model->__getModelType__();
        if ($modelType == 'default' && ($pre = $this->getTable())) {
            $prefix = $pre . '.';
        }
        $fields = $this->getFields();
        // $required = $prefix . $this->required;
        // tao query builder
        $query = $this->_model->newQuery();

        if (count($this->defaultConditions)) {
            $this->doAction($this->defaultConditions, $query);
        }


        $args = $this->prepareArgs($args);
        // các tham số mặc định
        if (count($this->defaultParams)) {
            foreach ($this->defaultParams as $key => $param) {
                $param[0] = (count(explode('.', $param[0])) > 1) ? $param[0] : $prefix . $param[0];
                if (count($param) == 2 && is_array($param[1])) {
                    call_user_func_array([$query, 'whereIn'], $param);
                } else {
                    call_user_func_array([$query, 'where'], $param);
                }
            }
        }
        // kiểm tra và tạo query các tham số truyền vào
        if (is_array($args) && count($args)) {

            // duyệt mảng tham số truyền vào
            foreach ($args as $field => $vl) {
                if (is_numeric($field)) {
                    // do action

                    $this->doAction([$vl], $query, $prefix);
                    continue;
                }
                $k = strtolower($field);
                // kiểm tra các lệnh đặc biệt bắt đầu với ký tự '@'
                if (substr($k, 0, 1) == '@') {
                    $f = substr($k, 1);
                    switch ($f) {
                        case 'search':
                            // tim kiem
                            if (!is_array($vl)) {
                                $keywords = $vl;
                            } else {
                                if (isset($vl['keywords'])) {
                                    $keywords = $vl['keywords'];
                                } elseif (isset($vl['keyword'])) {
                                    $keywords = $vl['keyword'];
                                }
                                if (isset($vl['by'])) {
                                    $search_by = $vl['by'];
                                }
                            }
                            break;
                        case 'mlcsearch':
                            // tim kiem
                            if (!is_array($vl)) {
                                $keywords = $vl;
                            } else {
                                if (isset($vl['keywords'])) {
                                    $keywords = $vl['keywords'];
                                } elseif (isset($vl['keyword'])) {
                                    $keywords = $vl['keyword'];
                                }
                                if (isset($vl['by'])) {
                                    $search_by = $vl['by'];
                                }
                            }
                            $this->mlcSearchActive = true;
                            break;
                        case 'mlcslug':
                            // dd($vl);
                            $this->buildMLCSlugQuery($vl);
                            break;
                        case 'search_by':
                            // tim kiem
                            $search_by = $vl;
                            break;

                        case 'softdelete':
                        case 'trashed':
                        case 'deleted':

                            if ($this->_model->isSoftDeleteMode() && $softDeleteColumn = $this->_model->getDeletedAtColumn()) {
                                if ($vl) {
                                    if (is_numeric($vl) && $vl > 0) {
                                        $date = date('Y-m-d', time() - 3600 * 24 * $vl);
                                        $query->where(function ($q) use ($date, $prefix, $softDeleteColumn) {
                                            $q->whereNull($prefix . $softDeleteColumn)
                                                ->orWhereDate($prefix . $softDeleteColumn, '>=', $date);
                                        });
                                    } else {
                                        $query->whereNotNull($prefix . $softDeleteColumn);
                                    }
                                } else {
                                    $query->whereNull($prefix . $softDeleteColumn);
                                }
                            }
                            if (in_array('trashed_status', $fields)) {
                                if ($vl) {
                                    $query->where($prefix . 'trashed_status', 1);
                                } else {
                                    $query->where($prefix . 'trashed_status', 0);
                                }
                            }
                            break;

                        case 'order_by':
                        case 'sortby':

                            // order by
                            $orderby = $vl;
                            break;

                        case 'limit':
                            // limit (skip & take)
                            $limit = $vl;
                            break;

                        case 'actions':
                            // thược hiện các hành động với model thông qua các mảng con chứa phương thức và các tham số
                            $actions = $vl;
                            break;

                        default:
                            // nếu không rơi vào các TH trên thì kiểm tra key truyền vào có phải là phương thức của query builder hay không
                            $ff = substr($field, 1);
                            $func = null;
                            $fff = strtolower($ff);
                            if (in_array($ff, $this->sqlclause)) $func = $ff;
                            elseif (array_key_exists($fff, $this->sqlclause)) $func = $this->sqlclause[$fff];
                            elseif (method_exists($query, $ff)) {
                                $func = $ff;
                            }
                            if ($func) {
                                if(in_array($fff, ['select', 'selectraw'])){
                                    $this->isSelectQuery = true;
                                }
                                // la method cua query buildr
                                if (is_array($vl) && isset($vl[0])) {

                                    if (is_array($vl[0]) && isset($vl[0][0])) {
                                        foreach ($vl as $p) {
                                            call_user_func_array([$query, $func], $p);
                                        }
                                    } else {
                                        call_user_func_array([$query, $func], $vl);
                                    }
                                } else {
                                    $param = is_array($vl) ? $vl : [$vl];
                                    call_user_func_array([$query, $func], $param);
                                }
                            } elseif (in_array($eager = substr($f, 0, 9), ['withcount', 'loadcount'])) {
                                $this->eager(substr($ff, 0, 9), substr($ff, 9), $vl);
                            } elseif (in_array($eager2 = substr($f, 0, 4), ['with', 'load'])) {
                                $this->eager($eager2, substr($ff, 4), $vl);
                            }


                            break;
                    }
                }
                // end if start with @
                else {
                    $this->__whereQuery($query, $field, $vl, true, $fields, $prefix);
                }
            }
        }


        $actions = array_merge($this->actions, $actions);
        // tim kiem trong bang dua tren cac cot
        if ($keywords) $this->buildSearchQuery($query, $keywords, $search_by, $prefix);
        // thao tac voi query builder thong qua tham so actions
        if ($actions) $this->doAction($actions, $query);
        // do all Query After
        $this->runQueryAfter($query);
        // build orderby
        if ($orderby) $this->buildOrderByQuery($query, $orderby, $prefix);
        // build limit
        if ($limit) $this->buildLimitQuery($query, $limit);

        $this->resetActionParams();
        $this->resetMLC();
        $this->fire('query', $query);

        return $query;
    }


    /**
     * build where query
     *
     * @param Builder $query
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    final protected function __whereQuery($query, $field, $value = null, $useStrict = false, $fields = [], $prefix = '')
    {
        $vl = $value;
        // không bắt đầu bằng @ thì sẽ gọi hàm where với column là key và so sánh '='
        $operator = '=';
        preg_match_all('/[A-z0-9_]\s*(=|!=|<=|>=|<|>|<>|!|==|\slike|!like|\snotlike|\snot\slike|\sstartwith|\sendwith|\sstartby|\sendby|\scontains|\sfind|\ssearch)$/i', $field, $m);
        if ($m[1]) {
            $operator = strtolower(trim($m[1][0]));
            $field = trim(substr($field, 0, strlen($field) - strlen($operator)));
        }
        $ifield = $field;
        $funcfield = null;
        $ftype = null;
        if (count($spacto = explode(':', $field)) == 2) {
            $ifield = $spacto[0];
            if (in_array($stype = strtolower($spacto[1]), ['date', 'fromdate', 'from_date' . 'todate', 'to_date', 'daterange', 'date_range'])) {
                $ftype = str_replace('_', '', $stype);
                $funcfield = 'addDate';
            }
        }
        $hasPrefix = (count(explode('.', $ifield)) > 1);
        if (!$hasPrefix && $useStrict) {

            // nếu không có prefix và ko có trong fillable thì bỏ qua
            if (isset($this->whereable) && array_key_exists($ifield, $this->whereable)) {
                $f = $this->whereable[$ifield];
            } elseif (!in_array($ifield, $fields) && $ifield != $this->_primaryKeyName) return;
            else $f = $prefix . $ifield;
        } else $f = $ifield;

        if ($funcfield) {
            call_user_func_array([$this, $funcfield], [$query, $ftype, $f, $vl]);
        } elseif (is_array($vl)) {
            // nếu value là mảng sẽ gọi where in
            if (in_array($operator, ['!', '!=', '<>'])) {
                $query->whereNotIn($f, $vl);
            } else {
                $query->whereIn($f, $vl);
            }
        } else {
            switch ($operator) {
                case 'like':
                    $query->where($f, $operator, $vl);
                    break;
                case 'notlike':
                case 'not like':
                    $query->where($f, 'not like', $vl);
                    break;

                case 'contains':
                case 'search':
                case 'find':
                    $query->where($f, 'like', '%' . $vl . '%');
                    break;


                case 'start':
                case 'startwith':
                case 'startby':
                    $query->where($f, 'like', $vl . '%');
                    break;


                case 'end':
                case 'endwith':
                case 'endby':
                    $query->where($f, 'like', '%' . $vl);
                    break;

                case '==':
                    $query->where($f, $vl);
                    break;

                case 'not':
                    $query->where($f, '!=', $vl);
                    break;

                default:
                    $query->where($f, $operator, $vl);
                    break;
            }
        }
    }

    /**
     * run all query action in query after
     *
     * @param Builder $query
     * @return $this
     */
    final protected function runQueryAfter($query)
    {
        if (is_array($this->__queryAfter) && count($this->__queryAfter)) {
            foreach ($this->__queryAfter as $action) {
                $action($query);
            }
        }
        $this->__queryAfter = [];
        return $this;
    }

    /**
     * xử lý data
     *
     * @param array $params
     * @return array
     */
    public function prepareArgs($params = [])
    {
        return $params;
    }


    /**
     * reset param action
     *
     * @return static
     */
    final public function resetActionParams()
    {
        $this->params = [];
        $this->actions = [];
        $this->isBuildJoin = false;
        $this->isBuildSelect = false;
 
    }

    final public function resetMLC() {
        $this->isJoinedMLC = false;
        $this->mlcSearchActive = true;
        $this->mlcSearchKeywords = null;
    }


    /**
     * Eager Loading
     *
     * @param string $type
     * @param string $relation
     * @param mixed $func
     * @return $this
     */
    final public function eager($type = 'with', $relation = null, $func = null, $queryBuilder = null)
    {

        if (!$queryBuilder) $queryBuilder = $this;
        $trla = strtolower(substr($relation, 0, 1)) . substr($relation, 1);
        if (is_numeric($func)) {
            $queryBuilder->{$type}([
                $trla => function ($query) use ($func) {
                    $query->take($func);
                }
            ]);
        } elseif (is_callable($func)) {
            $queryBuilder->{$type}([
                $trla => $func
            ]);
        } elseif (is_array($func)) {
            $queryBuilder->{$type}([
                $trla => function ($query) use ($func) {
                    foreach ($func as $key => $value) {
                        $k = strtolower($key);
                        if (substr($k, 0, 1) == '@') {
                            $kl = substr($k, 1);
                            if ($kl == 'limit') {
                                $this->buildLimitQuery($query, $value);
                            } elseif (in_array($kl, ['sortby', 'orderby', 'sort', 'sortby', 'sorttype'])) {
                                if (is_numeric($value)) {
                                    if (isset($this->sortByRules[$value])) {
                                        $this->buildOrderByQuery($query, $this->sortByRules[$value]);
                                    }
                                } else {
                                    $this->buildOrderByQuery($query, $value);
                                }
                            } else {
                                $func = null;
                                if (in_array($kl, $this->sqlclause))  $func = $kl;
                                elseif (array_key_exists($kl, $this->sqlclause)) $func = $this->sqlclause[$kl];
                                if ($func) {
                                    // la method cua query buildr
                                    if (is_array($value) && isset($value[0])) {

                                        if (is_array($value[0]) && isset($value[0][0])) {
                                            foreach ($value as $p) {
                                                call_user_func_array([$query, $func], $p);
                                            }
                                        } else {
                                            call_user_func_array([$query, $func], $value);
                                        }
                                    } else {
                                        $param = is_array($value) ? $value : [$value];
                                        call_user_func_array([$query, $func], $param);
                                    }
                                } elseif (in_array($eager2 = substr($kl, 0, 9), ['withcount', 'loadcount'])) {
                                    $this->eager(substr($key, 1, 9), substr($key, 10), $value, $query);
                                } elseif (in_array($eager = substr($kl, 0, 4), ['with', 'load'])) {
                                    $this->eager($eager, substr($key, 5), $value, $query);
                                }
                            }
                        } else {
                            $this->__whereQuery($query, $key, $value);
                            // if (is_array($value)) {
                            //     // nếu value là mảng sẽ gọi where in
                            //     $query->whereIn($key, $value);
                            // } else {
                            //     $query->where($key, $value);
                            // }
                        }
                    }
                }
            ]);
        } else {
            $queryBuilder->{$type}($trla);
        }
        return $queryBuilder;
    }

    /**
     * them thời gian
     *
     * @param QueryBuilder $query
     * @param string $type
     * @param string $field
     * @param string|int|float|bool|array $val
     * @return $this
     */
    public function addDate($query, $type, $field, $val = null)
    {
        if (($type == 'date' && is_array($val)) || $type == 'daterange') {
            if (is_array($val)) {
                if (isset($val[0]) && $dateStr = get_date_str($val[0])) {
                    $query->whereDate($field, '>=', $dateStr);
                }
                if (isset($val['from']) && $dateStrl = get_date_str($val['from'])) {
                    $query->whereDate($field, '>=', $dateStrl);
                }
                if (isset($val[1]) && $dateStr2 = get_date_str($val[1])) {
                    $query->whereDate($field, '<=', $dateStr2);
                }
                if (isset($val['to']) && $dateStrp = get_date_str($val['to'])) {
                    $query->whereDate($field, '<=', $dateStrp);
                }
            }
        } elseif ($dateStr = get_date_str($val)) {
            if ($type == 'fromdate') {
                $query->whereDate($field, '>=', $dateStr);
            } elseif ($type == 'todate') {
                $query->whereDate($field, '<=', $dateStr);
            } else {
                $query->whereDate($field, $dateStr);
            }
        }
    }


    /**
     * search mode
     *
     * @param string $mode
     * @return $this
     */
    final public function searchMode($mode = null)
    {
        if (in_array($a = strtolower($mode), ['all', 'raw', 'multiple', 'analytic']))
            $this->__searchMode__ = $a;
        return $this;
    }
    /**
     * search type
     *
     * @param string $type
     * @return $this
     */
    final public function searchType($type = null)
    {
        if (in_array($a = strtolower($type), ['all', 'ward', 'start', 'end', 'match']))
            $this->__searchType__ = $a;
        return $this;
    }

    /**
     * search Rule
     *
     * @param array|string $column
     * @param string $rule
     * @return static
     */
    final public function searchRule($column = null, $rule = null)
    {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                if (preg_match('/^[A-z]+[A-z_\-\.]*$/', $key)) {
                    $this->__searchRules__[$key] = $value;
                }
            }
        } elseif (is_string($column) && preg_match('/^[A-z]+[A-z_\-\.]*$/', $column) && is_string($rule)) {
            $this->__searchRules__[$column] = $rule;
        }
        return $this;
    }

    public function disableSearchColumn($column = null)
    {
        if ($column) {
            if (is_array($column)) {
                foreach ($column as $key => $value) {
                    if (is_numeric($key)) $this->searchDisable[] = $value;
                    else $this->searchDisable[$key] = $value;
                }
            } else {
                $this->searchDisable[] = $column;
            }
        }
        return $this;
    }

    protected function joinMLC()
    {
        if ($this->isJoinedMLC) return $this;
        $this->isJoinedMLC = true;
        if (Locale::default() == ($locale = Locale::current()) || !($mlc = $this->_model->getMLCConfig())) {
            return $this;
        }

        $this->leftJoin($this->mlcTable, function ($join) use ($mlc, $locale) {
            $join->on($this->mlcTable . '.' . $mlc['ref_key'], '=', $this->getTable() . '.' . $mlc['main_key'])
                ->on($this->mlcTable . '.ref', '=', DB::raw("'" . $mlc['ref'] . "'"))
                ->on($this->mlcTable . '.locale', '=', DB::raw("'" . $locale . "'"));
        });
        return $this;
    }


    protected function buildMLCSlugQuery($slug)
    {
        $current = Locale::current();
        if (config('app.multiple_language') != true || Locale::default() == $current || !($mlc = $this->_model->getMLCConfig())) {
            
            return $this->where($this->getTable() . '.slug', $slug);
        }

        $this->where($this->getTable() . '.slug', $slug)->orWhereIn($this->getTable() . '.' . $mlc['main_key'], function($query) use($mlc, $slug, $current){
            $query->select($this->mlcTable . '.' . $mlc['ref_key'])
                ->from($this->mlcTable)
                ->whereColumn($this->getTable() . '.' . $mlc['main_key'],  '=', $this->mlcTable . '.' . $mlc['ref_key'])
                ->where($this->mlcTable . '.locale', $current)
                ->where($this->mlcTable . '.ref', $mlc['ref'])
                ->where($this->mlcTable . '.slug', $slug);
        });
        return $this;
    }



    final protected function lazyLoad($collection)
    {
        if ($this->loadAfter) {
            foreach ($this->loadAfter as $key => $act) {
                call_user_func_array([$collection, $act['method']], $act['params']);
            }
        }
        $this->loadAfter = [];
    }

    /**
     * goi cac phuong thuc cua QueryBuilder
     *
     *
     * @param array $actions
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $prefix
     * @return \Illuminate\Database\Eloquent\Builder
     */
    final protected function doAction($actions, $query = null, $prefix = null)
    {
        if (!$query) {
            $prefix = '';
            $modelType = $this->_model->__getModelType__();
            if ($modelType == 'default' && ($pre = $this->getTable())) {
                $prefix = $pre . '.';
            }
            $query = $this->_model->newQuery();
        }
        $fields = $this->getFields();

        if (is_array($actions)) {


            foreach ($actions as $act) {
                // duyet qua cac action
                if (is_array($act)) {
                    // dump($act['method'], in_array($act['method'], $this->sqlclause));
                    if (isset($act['method']) && in_array($act['method'], $this->sqlclause)) {
                        //
                        if (in_array($act['method'], ['load', 'loadCount'])) $this->loadAfter[] = $act;
                        else call_user_func_array([$query, $act['method']], (isset($act['params']) && is_array($act['params'])) ? $act['params'] : []);
                        continue;
                    }
                    $aract = $act;
                    // array action
                    $f = array_shift($aract);
                    // map:
                    // $actions = [
                    //     // ....
                    //     ['where', 'name', 'doan'], // tham số đầu tiên là tên phương thức
                    //     // ....
                    // ]
                    // neu action la 1 mang

                    if (is_string($f) && in_array($f, $this->sqlclause)) {

                        if (isset($aract[0])) {

                            if (is_array($aract[0])) {
                                call_user_func_array([$query, $f], $aract[0]);
                            } else {
                                call_user_func_array([$query, $f], $aract);
                            }
                        }
                    } elseif (is_array($f)) {
                        // map:
                        // $actions = [
                        //     // ....
                        //     ['where'=>['name','doan']],
                        //     // ....
                        // ]
                        // neu action la 1 mang
                        foreach ($act as $func => $param) {
                            // duyet qua mang day lay ten action
                            if (is_numeric($func) && is_array($param) && count($param) > 1) {
                                $f = array_shift($param);
                                if (in_array($f, $this->sqlclause) && count($param)) {
                                    if (is_array($param[0]) && isset($param[0][0])) {
                                        foreach ($param as $p) {
                                            call_user_func_array([$query, $f], $p);
                                        }
                                    } else {
                                        call_user_func_array([$query, $f], $param);
                                    }
                                }
                            } elseif (in_array($func, $this->sqlclause)) {

                                if (!is_array($param)) {
                                    call_user_func_array([$query, $func], [$param]);
                                }
                                if (is_array($param[0]) && isset($param[0][0])) {
                                    foreach ($param as $p) {
                                        call_user_func_array([$query, $func], $p);
                                    }
                                } else {
                                    call_user_func_array([$query, $func], $param);
                                }
                            }
                        }
                    } elseif (in_array($f, $fields) || ($this->whereable && is_array($this->whereable) && in_array($f, $this->whereable))) {
                        call_user_func_array([$query, 'where'], $act);
                    } elseif (in_array($prefix . $f, $fields)) {
                        $act[0] = $prefix . $f;
                        call_user_func_array([$query, 'where'], $act);
                    } elseif ($this->whereable && is_array($this->whereable)) {
                        if (array_key_exists($f, $this->whereable)) {
                            $act[0] = $this->whereable[$f];
                            call_user_func_array([$query, 'where'], $act);
                        }
                    }
                }
            }
        }
        return $query;
    }

    /**
     * build order by
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array|int $orderby
     * @param string $prefix
     * @return \Illuminate\Database\Eloquent\Builder
     */
    final protected function buildOrderByQuery($query, $orderby = null, $prefix = null)
    {
        if ($orderby) {
            // order by mot hoac nhieu cot
            if (is_string($orderby)) {
                // mot cot
                if (count($odb = explode('-', $orderby)) == 2) {
                    $b = strtoupper($odb[1]);
                    if ($b != 'DESC') $b = 'ASC';
                    $f = (count(explode('.', $odb[0])) > 1) ? $odb[0] : $prefix . $odb[0];
                    $query->orderBy($f, $b);
                } elseif (count($odb = array_filter(explode(' ', $orderby), function ($s) {
                    return strlen(trim($s)) > 0;
                })) == 2) {
                    $b = strtoupper($odb[1]);
                    if ($b != 'DESC') $b = 'ASC';
                    $f = (count(explode('.', $odb[0])) > 1) ? $odb[0] : $prefix . $odb[0];
                    $query->orderBy($f, $b);
                } else {
                    // ngau nhien
                    if (strtolower($orderby) == 'rand()' || strtolower($orderby) == 'random()') {
                        $query->orderByRaw($this->getRandomFunction());
                    } else {
                        // mac dinh
                        $f = (count(explode('.', $orderby)) > 1) ? $orderby : $prefix . $orderby;
                        $query->orderBy($f);
                    }
                }
            } elseif (is_array($orderby)) {
                // nhieu cot
                foreach ($orderby as $col => $type) {
                    if (is_numeric($col) && is_string($type)) {
                        $f = (count(explode('.', $type)) > 1) ? $type : $prefix . $type;
                        $query->orderBy($f);
                    } else {
                        $f = (count(explode('.', $col)) > 1) ? $col : $prefix . $col;
                        $query->orderBy($f, $type);
                    }
                }
            }
        }
        return $query;
    }

    public $paginateMode = 'paginate';

    public $paginateLomit = [];

    /**
     * build limit query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|string|int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    final public function buildLimitQuery($query, $limit = null)
    {
        $this->paginateMode = 'paginate';
        $this->paginateLomit = [];
        // limit
        if ($limit) {
            $this->paginateMode = 'limit';
            if (is_numeric($limit)) {
                $query->skip(0)->take($limit);
                $this->paginateMode = [0, $limit];
            } elseif (is_string($limit)) {
                if (count($l = explode(',', str_replace(' ', '', $limit))) == 2) {
                    $query->skip($l[0])->take($l[1]);
                    $this->paginateMode = $l;
                }
            } elseif (is_array($limit) && isset($limit[0]) && isset($limit[1])) {
                $query->skip($limit[0])->take($limit[1]);
                $this->paginateMode = $limit;
            }
        }
        return $query;
    }

    /**
     * lấy tên bảng
     *
     * @return string
     */
    public function getTable()
    {
        if ($this->table) return $this->table;
        $this->table = $this->_model->getTable();
        return $this->table;
    }

    /**
     * kiểm tra isset vd isset($light->prop)
     * @param string $name
     */

    public function __isset($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * loại bỏ thuộc tính qua tên thuộc tính
     */
    public function __unset($name)
    {
        unset($this->params[$name]);
    }


    /**
     * gắn giá trị cho thuộc tính với name là tên thuộc tính
     * value là giá trị của thuộc tính
     * @param string $name
     *
     *
     */
    public function __set($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function __get($name)
    {
        if (in_array(strtolower($name), ['primarykey', 'primarykeyname'])) return $this->_primaryKeyName;
        if (isset($this->params[$name])) return $this->params[$name];
        return null;
    }


    /**
     * set thuoc tinh
     *
     * @param string|int|float|array $key
     * @param mixed $value
     * @return static
     */
    final public function param($key = null, $value = null)
    {
        if (is_array($key)) {
            $this->params = array_merge($this->params, $key);
        } elseif (!is_null($key) && (is_string($key) || is_null($key))) {
            $this->params[$key] = $value;
        }
        return $this;
    }

    /**
     * them chuoi tim kiem
     * @param string $keywords
     * @param string/array $search_by
     *
     * @return static
     */
    public function addsearch($keywords = null, $search_by = null, $rules = null)
    {
        $this->params['@search'] = [
            'keyword' => $keywords,
            'by' => $search_by
        ];
        if ($rules) {
            $this->params['@search']['rules'] = $rules;
        }
        return $this;
    }

    /**
     * tim kiem bang like
     * @param string $column tên cột
     * @param string $value giá trị tìm kiếm
     * @return static
     */
    public function like($column, $value = null)
    {
        return $this->where($column, 'like', '%' . $value . '%');
    }
    /**
     * tim kiem bang like
     * @param string $column tên cột
     * @param string $value giá trị tìm kiếm
     * @return static
     */
    public function orLike($column, $value = null)
    {
        return $this->orWhere($column, 'like', '%' . $value . '%');
    }

    /**
     * Tìm kiếm bằng ILIKE (case-insensitive LIKE)
     * 
     * PostgreSQL: ILIKE (native)
     * MySQL: Sử dụng LOWER() để mô phỏng ILIKE
     * 
     * @param string $column tên cột
     * @param string $value giá trị tìm kiếm
     * @return static
     */
    public function ilike($column, $value = null)
    {
        if ($this->isPostgreSQL()) {
            // PostgreSQL hỗ trợ ILIKE native
            return $this->where($column, 'ilike', '%' . $value . '%');
        } else {
            // MySQL: mô phỏng ILIKE bằng LOWER()
            return $this->whereRaw('LOWER(' . $column . ') LIKE LOWER(?)', ['%' . $value . '%']);
        }
    }
    
    /**
     * Tìm kiếm bằng ILIKE với OR
     * 
     * @param string $column tên cột
     * @param string $value giá trị tìm kiếm
     * @return static
     */
    public function orILike($column, $value = null)
    {
        if ($this->isPostgreSQL()) {
            // PostgreSQL hỗ trợ ILIKE native
            return $this->orWhere($column, 'ilike', '%' . $value . '%');
        } else {
            // MySQL: mô phỏng ILIKE bằng LOWER()
            return $this->orWhereRaw('LOWER(' . $column . ') LIKE LOWER(?)', ['%' . $value . '%']);
        }
    }
    /**
     * Full-text search
     * 
     * MySQL: MATCH(columns) AGAINST(search)
     * PostgreSQL: to_tsvector(columns) @@ to_tsquery(search)
     * 
     * @param string|array $columns Cột hoặc mảng cột để search
     * @param string $value Giá trị tìm kiếm
     * @param array $options Options (mode, language, etc.)
     * @return static
     */
    public function whereFullText($columns, string $value, array $options = [])
    {
        $sql = $this->getFullTextSearch($columns, $value);
        return $this->whereRaw($sql, [$value]);
    }
    
    /**
     * Full-text search với OR
     * 
     * @param string|array $columns
     * @param string $value
     * @param array $options
     * @return static
     */
    public function orWhereFullText($columns, string $value, array $options = [])
    {
        $sql = $this->getFullTextSearch($columns, $value);
        return $this->orWhereRaw($sql, [$value]);
    }
    
    /**
     * Sắp xếp ngẫu nhiên (tự động chuyển đổi cho database)
     * 
     * @return static
     */
    public function randomOrder()
    {
        return $this->orderByRaw($this->getRandomFunction());
    }
    
    /**
     * Sắp xếp ngẫu nhiên với OR
     * 
     * @return static
     */
    public function orRandomOrder()
    {
        return $this->orderByRaw($this->getRandomFunction());
    }
    
    /**
     * Where row number (Window function)
     * 
     * MySQL: Sử dụng ROW_NUMBER() OVER() (MySQL 8.0+)
     * PostgreSQL: Sử dụng ROW_NUMBER() OVER()
     * 
     * @param int $rowNumber Số thứ tự row
     * @param array $orderBy Order by columns cho window function
     * @return static
     */
    public function whereRowNum(int $rowNumber, array $orderBy = [])
    {
        // ROW_NUMBER() OVER() được hỗ trợ bởi cả MySQL 8.0+ và PostgreSQL
        $orderByClause = '';
        if (!empty($orderBy)) {
            $orderParts = [];
            foreach ($orderBy as $col => $dir) {
                if (is_numeric($col)) {
                    // Nếu key là số, value là column name
                    $orderParts[] = $dir . ' ASC';
                } else {
                    // Nếu key là column, value là direction
                    $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                    $orderParts[] = $col . ' ' . $dir;
                }
            }
            $orderByClause = ' ORDER BY ' . implode(', ', $orderParts);
        } else {
            // Mặc định order by primary key
            $pk = $this->_primaryKeyName ?? 'id';
            $orderByClause = ' ORDER BY ' . $pk . ' ASC';
        }
        
        return $this->whereRaw(
            "ROW_NUMBER() OVER({$orderByClause}) = ?",
            [$rowNumber]
        );
    }
    

    /**
     * Where in - thêm query so sánh trong (IN)
     * @param string $column
     * @param array $array
     * @return $this
     */
    public function in($column, $array = [])
    {
        return $this->whereIn($column, $array);
    }
    /**
     * Where not in - thêm query so sánh không trong (NOT IN)
     * @param string $column
     * @param array $array
     * @return $this
     */
    public function notIn($column, $array = [])
    {
        return $this->whereNotIn($column, $array);
    }

    /**
     * Where less than - thêm query so sánh nhỏ hơn (<)
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function lt($column, $value = null)
    {
        return $this->where($column, '<', $value);
    }
    /**
     * Where greater than - thêm query so sánh lớn hơn (>)
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function gt($column, $value = null)
    {
        return $this->where($column, '>', $value);
    }
    /**
     * Where less than or equal to - thêm query so sánh nhỏ hơn hoặc bằng (<=)
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function lte($column, $value = null)
    {
        return $this->where($column, '<=', $value);
    }
    /**
     * Where greater than or equal to - thêm query so sánh lớn hơn hoặc bằng (>=)
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function gte($column, $value = null)
    {
        return $this->where($column, '>=', $value);
    }
    /**
     * Where equal to - thêm query so sánh bằng (=)
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function eq($column, $value = null)
    {
        return $this->where($column, '=', $value);
    }
    /**
     * Where not equal to - thêm query so sánh không bằng (!=)
     * @param string $column
     * @param mixed $value
     * @return $this
     */
    public function ne($column, $value = null)
    {
        return $this->where($column, '!=', $value);
    }


    /**
     * order by
     * @param mixed
     * @param string
     */
    public function orderBy($column = null, $type = 'asc')
    {
        $orderby = is_array($column) ? $column : [$column => $type];
        if (array_key_exists('@order_by', $this->params)) {
            $this->params['@order_by'] = array_merge($this->params['@order_by'], $orderby);
        } else {
            $this->params['@order_by'] = $orderby;
        }
        return $this;
    }

    /**
     * order by
     * @param mixed
     * @param string
     */
    final public function sortBy($column = null, $type = 'asc')
    {
        $orderby = is_array($column) ? $column : [$column => $type];
        if (array_key_exists('@sortby', $this->params)) {
            $this->params['@sortby'] = array_merge($this->params['@sortby'], $orderby);
        } else {
            $this->params['@sortby'] = $orderby;
        }
        return $this;
    }

    final public function groupByRaw(...$columns)
    {
        if (is_array($columns) && count($columns)) {
            foreach ($columns as $col) {
                $this->groupBy(DB::raw($col));
            }
        }
        return $this;
    }

    /**
     * limit
     *
     * @param int|string|array $start
     * @param integer $length
     * @return static
     */
    final public function limit($start = null, $length = 0)
    {
        if (is_array($start)) {
            $this->params['@limit'] = $start;
        } elseif ($length) {
            $this->params['@limit'] = [$start, $length];
        } else {
            $this->params['@limit'] = $start;
        }
        return $this;
    }

    /**
     * phân trang
     * @param integer|bool|null
     * @return static
     */
    final public function paginate($paginate = null)
    {
        if ($paginate === false) {
            $this->paginate = false;
            $this->_paginate = 0;
        } elseif (is_numeric($paginate) && $paginate > 0) $this->_paginate = $paginate;
        return $this;
    }
    /**
     * kiểm tra các phương thức có chứa tham số là chuỗi
     *
     * @param string $method
     * @param string[] ...$params
     * @return boolean
     */
    final public function hasActionParam($method, ...$params)
    {
        if ($this->actions) {
            foreach ($this->actions as $key => $actionParans) {
                if (isset($actionParans['method'])) {
                    if ($method == $actionParans['method']) {
                        $a = true;
                        if (isset($actionParans['params']) && is_array($actionParans['params'])) {
                            $p = $actionParans['params'];
                            foreach ($params as $key => $v) {
                                if (!isset($p[$key]) || $p[$key] != $v) $a = false;
                            }
                        } elseif (count($params)) {
                            $a = false;
                        }
                        if ($a) return true;
                    }
                } elseif (isset($actionParans[0])) {
                    if ($method == $actionParans[0]) {
                        $a = true;
                        if (isset($actionParans[1]) && is_array($actionParans[1])) {
                            $p = $actionParans[1];
                            foreach ($params as $key => $v) {
                                if (!isset($p[$key]) || $p[$key] != $v) $a = false;
                            }
                        } elseif (count($params)) {
                            $a = false;
                        }
                        if ($a) return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->params);
    }
}
