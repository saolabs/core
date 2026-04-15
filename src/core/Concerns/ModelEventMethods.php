<?php

namespace Saola\Core\Concerns;

use Illuminate\Database\Eloquent\SoftDeletingScope;

trait ModelEventMethods
{
       
   /**
    * chế độ xóa
    *
    * @var integer
    */
   protected $deleteMode = 0;

   
   /**
    * Indicates if the model is currently force deleting.
    *
    * @var bool
    */
   protected $forceDeleting = false;
   

   public function isSoftDeleteMode()
   {
       return $this->deleteMode == 1 || strtolower($this->deleteMode) == 'soft';
   }

    /**
     * Khởi động trait và đăng ký các sự kiện của model.
     */
    protected static function bootModelEventMethods()
    {
        static::creating(function ($model) {
            $model->beforeCreate();
            $model->beforeSave();
        });

        static::created(function ($model) {
            $model->afterCreate();
            $model->afterSave();

        });

        static::updating(function ($model) {
            if (!$model->canUpdate()) {
                throw new \Exception("Không thể cập nhật model này.");
            }
            $model->beforeUpdate();
            $model->beforeSave();

        });

        static::updated(function ($model) {
            $model->afterUpdate();
            $model->afterSave();

        });

        static::deleting(function ($model) {
            if (!$model->canDelete()) {
                throw new \Exception("Không thể xóa model này.");
            }
            $model->beforeDelete();
        });

        static::deleted(function ($model) {
            $model->afterDelete();
        });

        static::restoring(function ($model) {
            if (!$model->canRestore()) {
                throw new \Exception("Không thể khôi phục model này.");
            }
            $model->beforeRestore();
        });

        static::restored(function ($model) {
            $model->afterRestore();
        });
    }


    /**
     * chuyển trạng thái về đã xoa
     * @return boolean
     */
    public function moveToTrash()
    {
        if(!$this->canMoveToTrash()) return false;
        if(in_array('trashed_status', $this->fillable)){
            $this->beforeMoveToTrash();
            $this->trashed_status = 1;
            $sd = $this->save();
            if ($this->isSoftDeleteMode()) {
                // $this->beforeMoveToTrash();
                $delete = parent::delete();
                if ($delete) {
                    $sd = $delete;
                }
            }
            if($sd){
                $this->afterMoveToTrash();
                return true;
            }
            
            
            return false;
        }
        else if($this->isSoftDeleteMode())
        {
            $this->beforeMoveToTrash();
            $delete = parent::delete();
            if($delete){
                $this->afterMoveToTrash();
            }
           
        }
        else{
            return $this->delete();
        }
    }

    
    /**
     * xóa vĩnh viễn bản ghi
     * @return boolean
     */
    public function forceDelete()
    {
        
        if(!$this->canForceDelete()) return false;
        $this->beforeForceDelete();
        $delete = $this->sysForceDelete();
        if($delete){
            $this->afterForceDelete();
        }
        
        
        return $delete;
    }


    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);
    }

    /**
     * Initialize the soft deleting trait for an instance.
     *
     * @return void
     */
    public function initializeSoftDeletes()
    {
        $this->dates[] = $this->getDeletedAtColumn();
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    protected function sysForceDelete()
    {
        $this->forceDeleting = true;

        return tap(parent::delete(), function ($deleted) {
            $this->forceDeleting = false;

            if ($deleted) {
                $this->fireModelEvent('forceDeleted', false);
            }
        });
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting || !$this->isSoftDeleteMode()) {
            $this->exists = false;

            return $this->setKeysForSaveQuery($this->newModelQuery())->forceDelete();
        }

        return $this->runSoftDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->timestamps && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    protected function sysRestore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return ! is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting()
    {
        return $this->forceDeleting;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        if(defined('static::MODEL_TYPE') && static::MODEL_TYPE == 'mongo') return $this->getDeletedAtColumn();
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }

    // ================== QUYỀN HẠN ==================
    public function canDelete(): bool { return true; }
    public function canUpdate(): bool { return true; }
    public function canRestore(): bool { return true; }

    // ================== HOOK TRƯỚC SỰ KIỆN ==================
    protected function beforeCreate() {}
    protected function beforeUpdate() {}
    protected function beforeDelete() {}
    protected function beforeRestore() {}

    // ================== HOOK SAU SỰ KIỆN ==================
    protected function afterCreate() {}
    protected function afterUpdate() {}
    protected function afterDelete() {}
    protected function afterRestore() {}
}