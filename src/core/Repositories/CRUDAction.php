<?php

namespace Saola\Core\Repositories;

use Saola\Core\Magic\Arr;
use Saola\Core\Models\Model;
use Saola\Core\Models\MongoModel;
use Saola\Core\Models\SQLModel;
use Saola\Core\Validators\ExampleValidator;
use Saola\Core\Validators\Validator;
use ReflectionClass;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Các phuong thức để crud
 * @method array beforeCreate(array $data) can thiep va tra ve mang du lieu truoc khi tao moi
 * @method array beforeUpdate(array $data, int $id) can thiep va tra ve mang du lieu truoc khi cap nhat
 * @method array beforeSave(array $data, int $id = null) can thiep va tra ve mang du lieu truoc khi cap nhat hoac tao moi
 * @method void afterCreate(Model $result) thuc hien hanh dong sau khi khoi tao
 * @method void afterUpdate(Model $result) thuc hien hanh dong sau khi khi cap nhat
 * @method void afterSave(Model $result) thuc hien hanh dong sau khi cap nhat hoac tao moi
 */
trait CRUDAction
{

    /**
     * app validator namespace
     *
     * @var string
     */
    protected $appNamespace = 'App\Validators';


    protected $actor = null;


    protected $crudAction = null;

    protected $currentID = 0;

    protected $crudErrorMessage = null;

    protected $crudException = null;

    protected $throwExceptionEnabled = true;

    public function disableThrowException()
    {
        $this->throwExceptionEnabled = false;
    }
    public function enableThrowException()
    {
        $this->throwExceptionEnabled = false;
    }

    public function getCrudErrorMessage()
    {
        return $this->crudErrorMessage;
    }

    /**
     * get exception
     *
     * @return Throwable
     */
    public function getCrudException()
    {
        return $this->crudException;
    }

    public function setActor($actor = null)
    {
        if (is_string($actor) && in_array($a = strtolower($actor), ['admin', 'manager', 'client', 'private', 'public'])) {
            $this->actor = $a;
        }
        return $this;
    }

    public function getActor()
    {
        return $this->actor;
    }

    /**
     * Chuẩn hóa dữ liệu trước khi tạo mới
     * @param  array  $data mang du lieu
     * @return array
     */
    public function beforeCreate(array $data)
    {
        return $data;
    }
    /**
     * Chuẩn hóa dữ liệu trước khi Cập nhật
     * @param  array  $data mang du lieu
     * @return array
     */
    public function beforeUpdate(array $data, $id = null)
    {
        return $data;
    }


    /**
     * luu du lieu
     * @param  array  $data mang du lieu
     * @param  integer $id        id cua ban ghi
     * @return Model
     */
    final public function save(array $data, $id = null)
    {
        $this->crudErrorMessage = null;
        if ($id && $m = $this->_model->find($id)) {
            $model = $m;
            $this->crudAction = 'update';
            $this->currentID = $id;
            $data = $this->beforeUpdate($data, $id);
            $this->fire('beforeupdate', $this, $data, $id, $m);
            $this->fire('updating', $this, $data, $id, $m);
        } else {
            $this->fire('beforecreate', $this, $data);
            $this->fire('creating', $this, $data);
            $model = $this->model();
            if ($this->defaultValues) {
                $data = array_merge($this->defaultValues, $data);
            }
            $data = $this->beforeCreate($data);
        }
        $this->fire('beforesave', $this, $data, $id);
        $this->fire('saving', $this, $data, $id);

        if (method_exists($this, 'beforeSave') && is_array($d = $this->beforeSave($data, $id))) {
            $data = $d;
        }


        if (!$data && !$id) {
            $this->crudErrorMessage = 'Không có dữ liệu';
            return false;
        }
        $data = $this->parseData($data);
        $model->fill($data);
        $this->checkModelUuid($model);
        // dd($model);
        DB::beginTransaction();
        try {
            $model->save();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($this->throwExceptionEnabled) {
                throw $th;
            }
            $this->crudErrorMessage = $th->getMessage();
            $this->crudException = $th;
            return false;
        }
        if ($id && $id == $model->{$this->_primaryKeyName}) {
            $this->afterUpdate($model);
            $this->fire('afterupdate', $this, $model);
            $this->fire('updated', $this, $model);
        } else {
            $this->afterCreate($model);
            $this->fire('aftercreate', $this, $model);
            $this->fire('created', $this, $model);
        }
        $this->afterSave($model);
        $this->fire('aftersave', $this, $model);
        $this->fire('saved', $this, $model);
        $this->crudAction = null;
        $this->currentID = 0;

        return $model;
    }


    final protected function checkModelUuid($model)
    {
        if (!$model->useUuid || $model->useUuid === 'no') return;
        $uuidName = $model->useUuid === true ? 'uuid' : ($model->useUuid === 'primary' ? $model->getKeyName() : $model->useUuid);
        $uuidValue = $model->{$uuidName};
        // Check if the primary key doesn't have a value
        if (!$uuidValue) {
            // Dynamically set the primary key
            $model->setAttribute($uuidName, Str::uuid()->toString());
        }
    }

    /**
     * chuẩn hóa data trước khi lưu
     */
    public function parseData($data = [])
    {
        $escape = [];
        if (count($data)) {
            foreach ($data as $key => $value) {
                if ((is_array($value) || is_object($value)) && (!$this->_model->casts || !array_key_exists($key, $this->_model->casts)) && (!($ignore = $this->_model->getIgnoreParse()) || !is_array($ignore) || !in_array($key, $ignore))) {
                    $escape[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    $escape[$key] = $value;
                }
            }
        }
        return $escape;
    }


    /**
     * tao bản ghi mới
     * @param array
     * 
     * @return false|Model|MongoModel|SQLModel
     */
    public function create(array $data = [])
    {
        if ($model = $this->save($data)) {
            // do something

            // $this->afterCreate($model);
            return $model;
        }
        $this->crudErrorMessage = 'Không thể khởi tạo bản ghi (' . $this->crudErrorMessage . ')';
        return false;
    }

    /**
     * Tạo một lúc nhiều bản ghi (bulk insert)
     *
     * @param array $dataList Mảng các mảng dữ liệu
     * @return int|bool Số bản ghi tạo thành công hoặc false nếu thất bại
     */
    public function createMany(array $dataList = [])
    {
        if (empty($dataList)) return 0;
        // Chuẩn hóa data từng bản ghi, nếu cần
        $escapedDataList = array_map([$this, 'parseData'], $dataList);

        DB::beginTransaction();
        try {
            $res = $this->_model->insert($escapedDataList);
            DB::commit();
            return $res ? count($escapedDataList) : false;
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($this->throwExceptionEnabled) {
                throw $th;
            }
            $this->crudErrorMessage = $th->getMessage();
            $this->crudException = $th;
            return false;
        }
    }
    /**
     * cập nhật dử liệu bản ghi
     * @param int|string $id
     * @param array $data
     * @return Model|MongoModel|SQLModel|false
     */
    public function update($id, array $data = [])
    {
        if ($this->count(['id' => $id]) == 0) {
            $this->crudErrorMessage = 'Không thể tìm thấy bản ghi có id là ' . $id . ' để cập nhật';
            return false;
        }
        if ($model = $this->save($data, $id)) {
            // do something

            // $this->afterUpdate($model);
            return $model;
        }
        $this->crudErrorMessage = 'Không thể cập nhật bản ghi có id là ' . $id . ' (' . $this->crudErrorMessage . ')';
        return false;
    }

    /**
     * tạo bản ghi nếu chưa tồn tại
     *
     * @param array $data
     * @return Model
     */
    public function createIfNotExists(array $data = [], array $columns = [])
    {
        $params = $data;
        if ($columns) {
            $params = array_copy($data, $columns);
        }
        if (!$params) {
            if ($data) return $this->create($data);
            return null;
        }
        if (!($d = $this->first($params))) {
            $d = $this->create($data);
        }
        return $d;
    }
    /**
     * tạo bản ghi nếu tồn tại thì update
     *
     * @param array $data
     * @param array $columns
     * @return \Saola\Core\odels\Model
     */
    public function createOrUpdate(array $data = [], array $columns = [])
    {
        $params = $data;
        if ($columns) {
            $params = array_copy($data, $columns);
        }
        if ($params && $d = $this->first($params)) {
            return $this->update($d->{$this->_primaryKeyName}, $data);
        }
        return $this->create($data);
    }

    /**
     * xóa bằng model
     *
     * @param Model $model
     * @return bool
     */
    protected function deleteByModel($model)
    {
        if (!$model->canDelete()) return false;
        $this->fire('beforedelete', $this, $model->id, $model);
        $this->fire('deleting', $this, $model->id, $model);
        $stt = false;
        DB::beginTransaction();
        try {
            $stt = $model->delete();
            DB::commit();

            $this->fire('afterdelete', $this, $model->id, $model);
            $this->fire('deleted', $this, $model->id, $model);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($this->throwExceptionEnabled) {
                throw $th;
            }
            $this->crudErrorMessage = $th->getMessage();
            $this->crudException = $th;
            return false;
        }
        return $stt;
    }


    /**
     * Delete
     *
     * @param int|int[] $id
     * @return bool
     */
    final public function delete($id = null)
    {
        if (!$id) {
            // 
            if (count($this->params) || count($this->actions)) {
                $stt = false;
                if ($rs = $this->get()) {
                    foreach ($rs as $item) {
                        $stt = $this->deleteByModel($item);
                    }
                }
                return $stt;
            }
            return false;
        }
        // nếu xóa nhiều
        if (is_array($id)) {
            $ids = [];
            $args = Arr::isNumericKeys($id) ? $id : [$this->_primaryKeyName => $id];
            $list = $this->get($args);
            if (count($list)) {
                foreach ($list as $item) {
                    $id0 = $item->{$this->_primaryKeyName};
                    if ($this->deleteByModel($item)) {
                        $ids[] = $id0;
                    }
                }
            }
            return $ids;
        }
        $result = $this->find($id);
        if ($result) {
            return $this->deleteByModel($result);
        }

        return false;
    }


    /**
     * Delete
     *
     * @param int|int[] $id
     * @return bool
     */
    final public function forceDelete($id = null)
    {
        if (!$id) {
            if (count($this->params) || count($this->actions)) {
                $ids = [];
                $list = $this->get();
                if (count($list)) {
                    DB::beginTransaction();
                    try {
                        $this->fire('beforeForceDelete', $this, $id, $list);
                        foreach ($list as $item) {
                            if (!$item->canForceDelete()) continue;
                            $ids[] = $item->{$this->_primaryKeyName};
                            $item->forceDelete();
                        }
                        $this->fire('afterForceDelete', $this, $ids, $list);
                        DB::commit();
                    } catch (\Throwable $th) {
                        DB::rollBack();
                        if ($this->throwExceptionEnabled) {
                            throw $th;
                        }
                        $this->crudErrorMessage = $th->getMessage();
                        $this->crudException = $th;
                        return $ids;
                    }
                }
                return $ids;
            }
            return false;
        }
        // nếu xóa nhiều
        if (is_array($id)) {
            $ids = [];
            $args = Arr::isNumericKeys($id) ? $id : [$this->_primaryKeyName => $id];
            $list = $this->get($args);
            if (count($list)) {
                DB::beginTransaction();
                try {
                    $this->fire('beforeForceDelete', $this, $id, $list);
                    foreach ($list as $item) {
                        if (!$item->canForceDelete()) continue;
                        $ids[] = $item->{$this->_primaryKeyName};
                        $item->forceDelete();
                    }
                    $this->fire('afterForceDelete', $this, $ids, $list);

                    DB::commit();
                    return $ids;
                } catch (\Throwable $th) {
                    DB::rollBack();
                    if ($this->throwExceptionEnabled) {
                        throw $th;
                    }
                    $this->crudErrorMessage = $th->getMessage();
                    $this->crudException = $th;
                    return false;
                }
            }
            return $ids;
        }
        $result = $this->find($id);
        if ($result) {

            if ($result->canForceDelete()) {
                DB::beginTransaction();
                try {
                    $this->fire('beforeForceDelete', $this, $id, $result);
                    $result->forceDelete();
                    $this->fire('afterForceDelete', $this, $id, $result);
                    DB::commit();
                    return true;
                } catch (\Throwable $th) {
                    DB::rollBack();
                    if ($this->throwExceptionEnabled) {
                        throw $th;
                    }
                    $this->crudErrorMessage = $th->getMessage();
                    $this->crudException = $th;
                    return false;
                }
            }
        }

        return false;
    }



    /**
     * trash
     *
     * @param $id
     * @return bool
     */
    final public function moveToTrash($id)
    {
        $result = $this->find($id);
        if ($result && $result->canMoveToTrash()) {
            DB::beginTransaction();
            try {
                $this->fire('beforeMoveToTrash', $this, $id, $result);
                $rs = $result->moveToTrash();
                if(!$rs){
                    DB::rollBack();
                    return false;
                }
                DB::commit();
                $this->fire('afterMoveToTrash', $this, $id, $result);
                return $rs;
            } catch (\Throwable $th) {
                DB::rollBack();
            }
        }

        return false;
    }


    /**
     * trash
     *
     * @param $id
     * @return bool
     */
    final public function softDelete($id)
    {
        $result = $this->find($id);
        if ($result && $result->canMoveToTrash()) {
            DB::beginTransaction();
            try {
                $this->fire('beforeMoveToTrash', $this, $id, $result);
                $rs = $result->moveToTrash();
                if(!$rs){
                    DB::rollBack();
                    return false;
                }
                DB::commit();
                $this->fire('afterMoveToTrash', $this, $id, $result);
                return $rs;
            } catch (\Throwable $th) {
                DB::rollBack();
            }
        }

        return false;
    }

    /**
     * khôi phục bản ghi
     * @param int $id
     */
    final public function restore($id)
    {
        $result = $this->find($id);
        if ($result) {
            DB::beginTransaction();
            try {
                $this->fire('beforeRestore', $this, $id, $result);
                $rs = $result->restore();
                if(!$rs){
                    DB::rollBack();
                    return false;
                }
                $this->fire('afterRestore', $this, $id, $result);
                DB::commit();
                return $rs;
            } catch (\Throwable $th) {
                DB::rollBack();    
                if($this->throwExceptionEnabled){
                    throw $th;
                }
                $this->crudErrorMessage = $th->getMessage();
                $this->crudException = $th;
                return false;
            }
        }

        return false;
    }

    /**
     * xóa vĩnh viễn bản ghi
     * @param int $id
     */
    final public function erase($id)
    {
        $result = $this->find($id);
        if ($result && $result->canErase()) {
            DB::beginTransaction();
            try {
                $this->fire('beforeErase', $this, $id, $result);
                $rs = $result->erase();
                DB::commit();
                return $rs;
            } catch (\Throwable $th) {
                DB::rollBack();
                if($this->throwExceptionEnabled){
                    throw $th;
                }
                $this->crudErrorMessage = $th->getMessage();
                $this->crudException = $th;
                return false;
            }
        }

        return false;
    }

    /**
     * kiểm tra cho cho phep chuyen vao thung ra hay ko
     * @param int $id
     */
    final public function canMoveToTrash($id = null)
    {
        if ($id && $model = $this->find($id)) return $model->canMoveToTrash();
        return false;
    }

    /**
     * kiểm tra cho cho phep chuyen vao thung ra hay ko
     * @param int $id
     */
    final public function canDelete($id = null)
    {
        if ($id && $model = $this->find($id)) return $model->canDelete();
        return false;
    }
}
