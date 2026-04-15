<?php

namespace Saola\Core\Repositories;

use Illuminate\Support\Facades\DB;

trait EloquentQuery{
    use DatabaseHelper;
    
    protected $table = '';
    /**
     * @var string
     */
    protected $required = MODEL_PRIMARY_KEY;

    /**
     * @var integer
     */

    public $total_count = 0;

     /**
      * du lieu lan gan day
      * @var array
      */
    protected $lastParams = [];


    /**
     * @var queryBuilder
     */

    protected $last_query_builder = null;



    /**
     * sql functions
     * @var array $sqlclause
     */
    protected $sqlclause = [
        // select
        'select','selectRaw',
        // from
        'from','fromRaw',
        // join
        'join','leftJoin','crossJoin',
        // where
        'where','whereRaw','whereIn','whereNotIn','whereBetween','whereNotBetween', 
        'whereDay', 'whereMonth', 'whereYear', 'whereDate','whereTime',
        'whereColumn',
        // orWhere
        'orWhere','orWhereRaw','orWhereIn','orWhereNotIn','orWhereBetween','orWhereNotBetween', 
        'orWhereDay', 'orWhereMonth', 'orWhereYear', 'orWhereDate', 'orWhereTime',
        'orWhereColumn',
        // group by
        'groupBy',
        // having
        'having','havingRaw',
        // order by
        'orderBy','orderByRaw',
        // limit
        'skip','take',
    ];

    /**
     * các tham số mặc định
     * @var array
     */
    public $defaultParams = [];

    /**
     * các tham số mặc định
     * @var array
     */
    public $defaultConditions = [];

    /**
     * @var array $fillValues giá trị mặc đĩnh
     */
    public $fillValues = [];

    /**
     * tham số có thể xóa
     * @var array
     */
    public $fixableParams = [];



    /**
     * @var array tham so truy van
     */
    protected $_params = [];

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
    public function reset($all = false)
    {
        $this->total_count = 0;
        $this->query = null;
        $this->last_query_builder = null;
        $this->lastParams = [];

        if($all){
            $this->removeFixableParam();
        }
    }
    
    

    /**
     * Get All
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getAll()
    {
        return $this->_model->all();
    }

    
    
    /**
     * lay ra 1 ban ghi theo tieu chi
     * @param string $name
     * @param string $value
     */
    public function findBy($prop='name',$value=null)
    {
        if($prop && $value!==null){
            return $this->first([$prop=>$value]);
        }
        return null;
    }

    public function getBy($prop='name',$value=null)
    {
        if($prop && $value!==null){
            return $this->get([$prop=>$value]);
        }
        return [];
    }

    public function model()
    {
        return new $this->_model;
    }



    /**
     * lấy về các bản ghi phù hợp với tham số đã cung cấp
     * @param array $data 
     */
    public function get($data = [])
    {

        $paginate = null;
        $limit = null;
        if(is_array($data)){
            if(isset($data['@paginate'])){
                $paginate = $data['@paginate'];
                unset($data['@paginate']);
            }
        }
        if(is_array($data)){
            if(isset($data['@limit'])){
                $limit = $data['@limit'];
                unset($data['@limit']);
            }
        }
        
        $query = $this->query($data);
        $this->last_query_builder = $query;
        
        if($limit){
            $this->total_count = $query->count();
            $this->buildLimitQuery($query, $limit);
        }
        if($paginate){
            $collection = $query->paginate($paginate);
            $this->total_count = $collection->total();
        }else{
            $collection = $query->get();
        }
        
        return $collection;
    }

    /**
     * 
     */

    public function getOnly($data = [])
    {

        $paginate = null;
        $limit = null;
        if(is_array($data)){
            if(isset($data['@paginate'])){
                $paginate = $data['@paginate'];
                unset($data['@paginate']);
            }
        }
        if(is_array($data)){
            if(isset($data['@limit'])){
                $limit = $data['@limit'];
                unset($data['@limit']);
            }
        }
        
        $query = $this->query($data);
        if($limit) $this->buildLimitQuery($query, $limit);
        if($paginate){
            $collection = $query->paginate($paginate);
        }else{
            $collection = $query->get();
        }
        
        return $collection;
    }

    public function first($data = [])
    {
        $query = $this->query($data);
        $this->last_query_builder = $query;
        if(is_array($data)){
            //
        }
        return $query->first();
    }


    public function count($data = [])
    {
        if(is_array($data)){
            if(isset($data['@paginate'])){
                unset($data['@paginate']);
            }
        }
        if(is_array($data)){
            if(isset($data['@limit'])){
                unset($data['@limit']);
            }
        }
        $query = $this->query($data);
        $this->last_query_builder = $query;

        return $query->count();
    }
    
    
    public function countLast()
    {
        $data = $this->lastParams;
        $query = $this->query($data);
        return $query->count();
    }


    


    /**
     * thêm tham số
     */
    public function addDefaultParam(...$params)
    {

        if(count($params)>1){
            $this->defaultParams[] = $params;
        }
        return $this;
    }

    
    public function resetDefaultParams()
    {
        $this->defaultParams=[];
        return $this;
    }

    /**
     * thêm diều kiện
     * @param string
     * @param array
     */
    public function addDefaultCondition(...$params)
    {

        if(count($params)>1){
            $this->defaultConditions[] = $params;
        }
        return $this;
    }


    /**
     * xóa giá trị tham số mặt định
     * 
     * @return object EloquanRepository
     */
    public function removeDefaultConditions()
    {
        $this->defaultConditions = [];
        
        return $this;
    }

    /**
     * thêm giá trị mặc định khi tạo mới
     */
    public function addDefaultValue($key, $value = null)
    {
        if(is_string($key)){
            $this->fillValues[$key] = $value;
        }elseif(is_array($key)){
            foreach ($key as $k => $v) {
                $this->fillValues[$k] = $v;
            }
        }
        return $this;
    }

    /**
     * thêm tham số có thể override
     * @param string|array $name có thể là tên cột hoac458 một mảng
     * @param mixed        $value giá trị 
     * 
     * @return object EloquanRepository
     */
    public function addFixableParam($name, $value=null)
    {
        if(is_array($name)){
            foreach($name as $key => $val){
                if(is_numeric($key)){
                    if(is_string($val)){
                        $this->fixableParams[$val] = $value;
                    }
                }else{
                    $this->fixableParams[$key] = $val;
                }
            }
        }elseif (is_string($name)) {
            $this->fixableParams[$name] = $value;
        }
        return $this;
    }

    /**
     * xóa giá trị tham số mặt định
     * @param array|string $name
     * 
     * @return object EloquanRepository
     */
    public function removeFixableParam($name=null)
    {
        if(is_array($name)){
            foreach($name as $val){
                unset($this->fixableParams[$val]);
            }
        }elseif (is_string($name)) {
            unset($this->fixableParams[$name]);
        }
        else{
            $this->fixableParams = [];
        }
        return $this;
    }

    /**
     * xóa giá trị tham số mặt định
     * @param array|string $name
     * 
     * @return object EloquanRepository
     */
    public function removeDefaultValue($name=null)
    {
        if(is_array($name)){
            foreach($name as $val){
                unset($this->fillValues[$val]);
            }
        }elseif (is_string($name)) {
            unset($this->fillValues[$name]);
        }
        else{
            $this->fillValues = [];
        }
        return $this;
    }


    /**
     * xóa giá trị tham số mặt định
     * @return object EloquanRepository
     */
    public function clear()
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
    public function checkField($field)
    {
        return in_array($field, $this->getFields());
    }

    

    public function getFields()
    {
        return array_merge([$this->_primaryKeyName], $this->_model->__get_fields());
    }


    
    /**
     * tạo qury builder 
     * @param array $args            Mảng các tham số hoặc têm hàm và tham số hàm
     * @return quryBuilder
     * @author DoanLN
     */
    public function query($args=[])
    {
        $keywords = null; 
        $search_by= null; 
        $orderby=null;
        $limit = null;
        $actions = [];
        $parameters = $this->_params;
        $param_actions = isset($parameters['@actions'])?$parameters['@actions']:[];
        unset($parameters['@actions']);
        if(count($parameters)){
            $args = array_merge($parameters, (array)$args);
        }
        
        if($this->fixableParams){
            $args = array_merge($this->fixableParams, $args);
        }
        // pewfix 

        $prefix = '';
        $model = $this->model();
        if($pre = $this->getTable()){
            $prefix = $pre.'.';
        }
        $required = $prefix.$this->required;
        // tao query builder
        $query = $model->whereNotNull($required);

        if(count($this->defaultConditions)){
            $this->doAction($this->defaultConditions, $query);
        }


        $args = $this->prepareArgs($args);
        // các tham số mặc định
        if(count($this->defaultParams)){
            foreach ($this->defaultParams as $key => $param) {
                $param[0] = (count(explode('.',$param[0])) > 1)? $param[0] : $prefix . $param[0];
                call_user_func_array([$query,'where'],$param);
            }
        }
        // kiểm tra và tạo query các tham số truyền vào
        if(is_array($args) && count($args)){

            // duyệt mảng tham số truyền vào
            foreach($args as $field => $vl){
                if(is_numeric($field)){
                    // do action
                    $this->doAction([$vl], $query, $prefix);
                    continue;
                }
                $k = strtolower($field);
                // kiểm tra các lệnh đặc biệt bắt đầu với ký tự '@'
                if(substr($k,0,1) == '@'){
                    $f = substr($k,1);
                    switch ($f) {
                        case 'search':
                            // tim kiem
                            if(!is_array($vl)){
                                $keywords = $vl;
                            }else{
                                if(isset($vl['keywords'])){
                                    $keywords = $vl['keywords'];
                                }
                                elseif(isset($vl['keyword'])){
                                    $keywords = $vl['keyword'];
                                }
                                if(isset($vl['by'])){
                                    $search_by = $vl['by'];
                                }
                            }
                            break;
                        
                        case 'search_by':
                            // tim kiem
                            $search_by = $vl;
                            break;
                        
                        case 'order_by':
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

                            if(in_array($func = substr($field,1),$this->sqlclause)){ 
                                // la method cua query buildr
                                if(is_array($vl) && isset($vl[0])){
                                    
                                    if(is_array($vl[0]) && isset($vl[0][0])){
                                        foreach($vl as $p){
                                            call_user_func_array([$query,$func],$p);
                                        }
                                    }else{
                                        call_user_func_array([$query,$func],$vl);
                                    }
                                }else{
                                    $param = is_array($vl)?$vl:[$vl];
                                    call_user_func_array([$query,$func],$param);
                                }
                            }
                            break;
                    }
                }else{
                    // không bắt đầu bằng @ thì sẽ gọi hàm where với column là key và so sánh '=' 
                    $hasPrefix = (count(explode('.',$field)) > 1);
                    if(!$hasPrefix){
                        // nếu không có prefix và ko có trong fillable thì bỏ qua
                        if(!in_array($field, $this->getFields()) && $field!=$this->_primaryKeyName) continue;
                        $f = $prefix . $field;
                    }
                    else $f = $field;
                    
                    if(is_array($vl)){
                        // nếu value là mảng sẽ gọi where in
                        $query->whereIn($f,$vl);
                    }else{
                        $query->where($f,$vl);
                    }
                }
            }
        }

        
        $actions = array_merge($param_actions, $actions);
        
        // tim kiem trong bang dua tren cac cot
        if($keywords) $this->buildSearchQuery($query, $keywords, $search_by, $prefix);
        // thao tac voi query builder thong qua tham so actions
        if($actions) $this->doAction($actions, $query);
        // build orderby
        if($orderby) $this->buildOrderByQuery($query,$orderby, $prefix);
        // build limit
        if($limit) $this->buildLimitQuery($query, $limit);

        $this->_params = [];
        
        return $query;
    }


    public function prepareArgs($params = [])
    {
        return $params;
    }

    protected function buildSearchQuery($query, $keywords, $search_by=null, $prefix = null)
    {
        if(is_string($keywords) && strlen($keywords) > 0){
            if($search_by){
                if(is_string($search_by)){
                    // tim mot cot
                    $f = (count(explode('.',$search_by)) > 1)? $search_by : $prefix . $search_by;
                    $query->where($f,'like',"%$keywords%");
                }elseif(is_array($search_by)){
                    // tim theo nhieu cot
                    $query->where(function($q) use($keywords,$search_by, $prefix){
                        $b = $search_by;
                        $c = array_shift($b);
                        $f2 = (count(explode('.',$c)) > 1)? $c : $prefix . $c;
                        $k2 = str_slug($keywords);
                        $q->where($f2,'like', "%$keywords%");
                        foreach($b as $col){
                            $f3 = (count(explode('.',$col)) > 1)? $col : $prefix . $col;
                            $q->orWhere($f3,'like', "%$keywords%")->orWhere($f3,'like', "%$k2%");
                        }
                    });
                }
            }else{
                // mac dinh
                $query->where($prefix.'name','like',"%$keywords%");
            }
        }
        return $query;
    }


    protected function doAction($actions, $query=null, $prefix=null){
        if(!$query) $query = $this->_model->where($this->_primaryKeyName,'!=',0);
        if(is_array($actions)){
            
            foreach ($actions as $act) {
                // duyet qua cac action
                if(is_array($act)){
                    $aract = $act;
                    // array action
                    $f = array_shift($aract);

                    if(is_string($f) && in_array($f,$this->sqlclause)){
                        // map:
                        // $actions = [
                        //     // ....
                        //     ['where', 'name', 'doan'], // tham số đầu tiên là tên phương thức
                        //     // ....
                        // ]
                        // neu action la 1 mang 
                        
                        if(is_array($aract)){
                            if(is_array($aract[0]) && isset($aract[0][0])){
                                // map:
                                // $actions = [
                                //     // ....
                                //     ['where', ['column1', 'value1']], 
                                //     // ....
                                // ]
                                // neu action la 1 mang 
                                

                                foreach($aract[0] as $p){
                                    // goi ham
                                    call_user_func_array([$query,$f],$p);
                                }
                            }else{
                                call_user_func_array([$query,$f],$aract);
                            }
                        }
                    }
                    elseif(in_array($f,$this->sqlclause)){
                        // map:
                        // $actions = [
                        //     // ....
                        //     ['where'=>['name','doan']],
                        //     // ....
                        // ]
                        // neu action la 1 mang 
                        foreach ($act as $func => $param) {
                            // duyet qua mang day lay ten action
                            if(is_numeric($func) && is_array($param) && count($param)>1){
                                $f = array_shift($param);
                                if(in_array($f,$this->sqlclause) && count($param)){
                                    if(is_array($param[0]) && isset($param[0][0])){
                                        foreach($param as $p){
                                            call_user_func_array([$query,$f],$p);
                                        }
                                    }else{
                                        call_user_func_array([$query,$f],$param);
                                    }
                                }
                            }elseif(in_array($func,$this->sqlclause)){
                                if(!is_array($param)){
                                    call_user_func_array([$query,$func],[$param]);
                                }
                                if(is_array($param[0]) && isset($param[0][0])){
                                    foreach($param as $p){
                                        call_user_func_array([$query,$func],$p);
                                    }
                                }else{
                                    call_user_func_array([$query,$func],$param);
                                }
                            }
                        }
                    }
                    
                }
            }
        }
        return $query;
    }

    protected function buildOrderByQuery($query, $orderby = null, $prefix = null)
    {
        if($orderby){
            // order by mot hoac nhieu cot
            if(is_string($orderby)){
                // mot cot
                if(count($odb = explode('-',$orderby))==2){
                    $b = strtoupper($odb[1]);
                    if($b!='DESC') $b = 'ASC';
                    $f = (count(explode('.',$odb[0])) > 1)? $odb[0] : $prefix . $odb[0];
                    $query->orderBy($f, $b);
                }else{
                    // ngau nhien
                    if(strtolower($orderby) == 'rand()' || strtolower($orderby) == 'random()'){
                        $query->orderByRaw($this->getRandomFunction());
                    }
                    else{
                        // mac dinh
                        $f = (count(explode('.',$orderby)) > 1)? $orderby : $prefix . $orderby;
                        $query->orderBy($f);
                    }
                }
            }elseif(is_array($orderby)){
                // nhieu cot
                foreach($orderby as $col => $type){
                    if(is_numeric($col) && is_string($type)){
                        $f = (count(explode('.',$type)) > 1)? $type : $prefix . $type;
                        $query->orderBy($f);
                    }else {
                        $f = (count(explode('.',$col)) > 1)? $col : $prefix . $col;
                        $query->orderBy($f,$type);
                    }
                }
            }
        }
        return $query;
    }
    public function buildLimitQuery($query, $limit=null)
    {
        // limit
        if($limit){
            if(is_numeric($limit)){
                $query->skip(0)->take($limit);
            }elseif (is_string($limit)) {
                if(count($l = explode(',', str_replace(' ','',$limit)))==2){
                    $query->skip($l[0])->take($l[1]);
                }
            }
            elseif (is_array($limit) && isset($limit[0]) && isset($limit[1])) {
                $query->skip($limit[0])->take($limit[1]);
            }
        }
        return $query;
    }

    protected function getTable()
    {
        $model = $this->model();
        if($this->table) return $this->table;
        elseif(method_exists($model, '__get_table')) return call_user_func_array([$model, '__get_table'], ['1']);
        elseif(isset($model->table)) return $model->table;
        return null;
        
    }

    /**
     * kiểm tra isset vd isset($light->prop)
     * @param string $name
     */
	
    public function __isset($name)
    {
        return isset($this->_params[$$name]);
    }

    /**
     * loại bỏ thuộc tính qua tên thuộc tính
     */
    public function __unset($name)
    {
        unset($this->_params[$name]);
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
        $this->_params[$name] = $value;
        
    }

    /**
     * them chuoi tim kiem
     * @param string $keywords
     * @param string/array $search_by
     * 
     * @return object
     */
    public function addsearch(string $keywords = '', $search_by = null)
    {
        $this->_params['@search'] = [
            'keyword' => $keywords,
            'by' => $search_by
        ];
        return $this;
    }

    /**
     * order by
     * @param mixed
     * @param string
     */
    public function order_by($column = null, $type='asc')
    {
        $orderby = is_array($column)?$column:[$column=>$type];
        if(array_key_exists('@order_by', $this->_params)){
            $this->_params['@order_by'] = array_merge($this->_params['@order_by'], $orderby);
        }else{
            $this->_params['@order_by'] = $orderby;
        }
        return $this;
    }

    public function groupByRaw(...$columns)
    {
        if(is_array($columns) && count($columns)){
            foreach ($columns as $col) {
                $this->groupBy(DB::raw($col));
            }
        }
        return $this;
    }

    public function limit($start=null,$length=0)
    {
        if(is_array($start)){
            $this->_params['@limit'] = $start;
        }elseif($length){
            $this->_params['@limit'] = [$start, $length];
        }else{
            $this->_params['@limit'] = $start;
        }
        return $this;
    }


	public function __call($method, $params)
	{
		if (in_array($method, $this->sqlclause)) {
            if(!isset($this->_params['@actions'])){
                $this->_params['@actions'] = [];
            }
			$this->_params['@actions'][] = array_merge([$method], $params);
        }
        return $this;
    }
    
    public function __toString()
    {
        return json_encode($this->_params);
    }
}