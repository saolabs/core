<?php

namespace Saola\Core\Repositories;

/**
 * các phương thúc với owner
 */
trait GettingAction
{

    /**
     * has pagination
     *
     * @var boolean
     */
    public $hasPaginateParam = false;
    /**
     * get model
     *
     * @return \Saola\Core\Models\Model
     */
    public function model()
    {
        return new $this->_model;
    }

    /**
     * Get All
     * @return \Saola\Core\Models\Model[]
     */
    public function getAll()
    {
        $this->fire('beforegetAll', $this);
        $this->checkIfMultiLangContents();
        $rs = $this->_model->all();
        $this->fire('aftergetAll', $this, $rs);
        return $rs;
    }


    /**
     * Get one
     * @param int $id
     * @return \Saola\Core\Models\Model
     */
    final public function find($id)
    {
        $result = $this->_model->find($id);
        return $result;
    }


    /**
     * lay ra 1 ban ghi theo tieu chi
     * @param string $prop
     * @param string $value
     * @return \Saola\Core\odels\Model
     */
    final public function findBy($prop = 'name', $value = null)
    {
        if ($prop && $value !== null) {

            $this->fire('beforfindBy', $this, $prop, $value);
            $rs = $this->first([$prop => $value]);
            $this->fire('afterfindBy', $this, $prop, $value, $rs);
            return $rs;
        }
        return null;
    }

    public function checkIfMultiLangContents()
    {
        if($this->_model->multilang){
            $this->with('localeContent');
        }
    }

    public function beforeGetData($data = [])
    {
        # code...
    }

    /**
     * lấy nhanh thông tin theo một trường nào đó
     * @param string $column
     * @param mixed $value
     * @return \Saola\Core\odels\Model[]
     */

    final public function getBy($prop = 'name', $value = null)
    {
        if ($prop && $value !== null) {
            $this->fire('beforgetBy', $this, $prop, $value);
            $rs = $this->get([$prop => $value]);
            $this->fire('aftergetBy', $this, $prop, $value, $rs);
            return $rs;
 
        }
        return [];
    }



    /**
     * lấy về các bản ghi phù hợp với tham số đã cung cấp
     * @param array $args
     * @return \Saola\Core\odels\Model[]|\Saola\Core\asks\MaskCollection
     */
    final public function get($args = [])
    {
        $this->fire('prepareget', $this, $args);
            
        $this->hasPaginateParam = false;
        if (is_array($a = $this->beforeGetData($args))) $args = $a;
        $this->checkIfMultiLangContents();
        $paginate = null;
        $limit = null;
        if (is_array($args)) {
            if (isset($args['@paginate'])) {
                $paginate = $args['@paginate'];
                unset($args['@paginate']);
            }
            if (isset($args['@limit'])) {
                $limit = $args['@limit'];
                unset($args['@limit']);
            }
        }
        if ($paginate === null) $paginate = $this->_paginate;
        $this->fire('beforget', $this, $args);
        $query = $this->query($args);
        // $this->lastQueryBuilder = $query;

        if ($limit) {
            $this->totalCount = $query->count();
            $this->buildLimitQuery($query, $limit);
        }
        if ($paginate) {
            $this->hasPaginateParam = true;
            $collection = $query->paginate($paginate);
            $this->totalCount = $collection->total();
        } else {
            $collection = $query->get();
            $this->totalCount = count($collection);
        }

        // if($this->mode == 'mask') return $this->parseCollection($collection);
        if ($this->loadAfter) {
            $this->lazyLoad($collection);
        }
        $this->fire('afterget', $this, $args, $collection);
        return $collection;
    }

    /**
     * chi lay ma ko dem
     * @param array $args
     * @return \Saola\Core\odels\Model[]
     */

    final public function getOnly($args = [])
    {
        $this->hasPaginateParam = false;
        if (is_array($a = $this->beforeGetData($args))) $args = $a;
        $this->checkIfMultiLangContents();
        $paginate = null;
        $limit = null;
        if (is_array($args)) {
            if (isset($args['@paginate'])) {
                $paginate = $args['@paginate'];
                unset($args['@paginate']);
            }
        }
        if (is_array($args)) {
            if (isset($args['@limit'])) {
                $limit = $args['@limit'];
                unset($args['@limit']);
            }
        }
        if (!$paginate) $paginate = $this->_paginate;
        $query = $this->query($args);
        if ($limit) $this->buildLimitQuery($query, $limit);
        if ($paginate) {
            $this->hasPaginateParam = true;
            $collection = $query->paginate($paginate);
        } else {
            $collection = $query->get();
        }
        if ($this->loadAfter) {
            $this->lazyLoad($collection);
        }
        return $collection;
    }

    /**
     * lấy ra kết quả đầu tiên
     *
     * @param array $args
     * @return \Saola\Core\odels\Model
     */
    final public function first($args = [])
    {
        $this->fire('preparefirst', $this, $args);
        if (is_array($a = $this->beforeGetData($args))) $args = $a;
        $this->checkIfMultiLangContents();
        $this->fire('beforefirst', $this, $args);
        $query = $this->query($args);
        // $this->last_query_builder = $query;
        if (is_array($args)) {
            //
        }
        $rs = $query->first();
        if ($this->loadAfter) {
            $this->lazyLoad($rs);
        }
        $this->fire('afterfirst', $this, $args, $rs);
        
        return $rs;
    }


    /**
     * dem so ban ghi
     *
     * @param array $args
     * @return int
     */
    final public function count($args = [])
    {
        $this->fire('prepareCount', $this, $args);
        if (is_array($a = $this->beforeGetData($args))) $args = $a;
        if (is_array($args)) {
            if (isset($args['@paginate'])) {
                unset($args['@paginate']);
            }
            if (isset($args['@limit'])) {
                unset($args['@limit']);
            }
        }
        $this->fire('beforeCount', $this, $args);
        $query = $this->query($args);
        // $this->lastQueryBuilder = $query;

        return $query->count();
    }


    /**
     * dem so ban ghi theo tieu chi
     * @param string $name
     * @param string $value
     * @return int
     */
    final public function countBy($prop = 'name', $value = null)
    {
        if ($prop && $value !== null) {
            return $this->count([$prop => $value]);
        }
        return 0;
    }

    /**
     * tính tổng
     *
     * @param string $column
     * @param array $args
     * @return int
     */
    final public function sum($column, $args = [])
    {
        if (is_array($a = $this->beforeGetData($args))) $args = $a;
        return $this->query($args)->sum($column);
    }

    /**
     * tính trung bình
     *
     * @param string $column
     * @param array $args
     * @return int|float
     */
    final public function avg($column, $args = [])
    {
        if (is_array($a = $this->beforeGetData($args))) $args = $a;
        return $this->query($args)->avg($column);
    }



    final public function countLast()
    {
        $data = $this->lastParams;
        $query = $this->query($data);
        return $query->count();
    }

    /**
     * lấy dữ liệu option
     *
     * @param array $args
     * @param string $defaultFirst
     * @param string $valueKey
     * @param string $textKey
     * @return array
     */
    public function getDataOptions(array $args = [], $defaultFirst = null, $valueKey = MODEL_PRIMARY_KEY, $textKey = 'name'): array
    {
        $a = array_filter($args, function ($value) {
            if (is_string($value) || is_numeric($value)) {
                return strlen($value) > 0;
            } elseif (is_array($value)) {
                if (count($value)) return true;
                else return false;
            }
            return true;
        });
        $this->fire('beforegetDataOptions', $this, $a);
        
        $data = [];
        if ($defaultFirst) $data = ["" => $defaultFirst];
        if (!$textKey) $textKey = 'name';
        $textTemp = preg_match('/\{\$[^\}]+\}/i', $textKey) ? $textKey : null;
        if ($list = $this->get($a)) {
            foreach ($list as $item) {
                $val = $item->{$valueKey};
                if (is_null($val)) $val = '';
                $data[$val] = $textTemp ? str_eval($textKey, $item->getAttrData()) : (isset($item->{$textKey}) ? $item->{$textKey} : ''
                );
            }
        }

        return $data;
    }


    /**
     * lấy danh sách option được yêu cầu trực tiếp bở request
     *
     * @param \Illuminate\Http\Request $request thông tin request
     * @param array $args $mảng tham số
     * @param string $defaultFirst
     * @param string $valueKey tên cột sẽ làm giá trị
     * @param string $textKey tên cộ sẽ hiển thị
     * @return array
     */
    public function getRequestDataOptions($request, array $args = [], $defaultFirst = null, $valueKey = MODEL_PRIMARY_KEY, $textKey = 'name'): array
    {
        if ($request->ignore && is_array($request->ignore)) {
            $this->whereNotIn($valueKey, $request->ignore);
        }
        if($valueKey == MODEL_PRIMARY_KEY) $valueKey = $this->_primaryKeyName;
        $this->buildFilter($request);
        $args = array_merge($this->getPaginateArgs($request), $args);
        $this->fire('beforegetRequestDataOptions', $this, $request, $args);
        
        $data = $this->getDataOptions($args, $defaultFirst, $valueKey, $textKey);
        $this->fire('aftergetRequestDataOptions', $this, $request, $args, $data);
        return $data;
    }


    public function chunkById(...$args)
    {
        $this->checkIfMultiLangContents();
        return $this->query()->chunkById(...$args);
    }

    public function chunk(...$args)
    {
        $this->checkIfMultiLangContents();
        return $this->query()->chunk(...$args);
    }

    /**
     * lấy ra tổng số bản ghi
     *
     * @return int
     */
    public function total()
    {
        return $this->totalCount;
    }
}
