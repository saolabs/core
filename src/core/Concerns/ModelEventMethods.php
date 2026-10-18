<?php

namespace Saola\Core\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

trait ModelEventMethods
{
    use SoftDeletes {
        bootSoftDeletes as protected bootEloquentSoftDeletes;
        initializeSoftDeletes as protected initializeEloquentSoftDeletes;
        performDeleteOnModel as protected performSoftDeleteOnModel;
        forceDelete as protected sysForceDelete;
        restore as protected sysRestore;
    }

    protected $deleteMode = 0;

    public function isSoftDeleteMode(): bool
    {
        return $this->deleteMode == 1 || strtolower((string) $this->deleteMode) === 'soft';
    }

    public static function bootSoftDeletes()
    {
        $mode = (new \ReflectionClass(static::class))->getDefaultProperties()['deleteMode'] ?? 0;
        if ($mode == 1 || strtolower((string) $mode) === 'soft') {
            static::bootEloquentSoftDeletes();
        }
    }

    public function initializeSoftDeletes()
    {
        if ($this->isSoftDeleteMode()) {
            $this->initializeEloquentSoftDeletes();
        }
    }

    protected function performDeleteOnModel()
    {
        if ($this->isSoftDeleteMode()) {
            return $this->performSoftDeleteOnModel();
        }

        return parent::performDeleteOnModel();
    }

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
            if (! $model->canUpdate()) {
                throw new \LogicException('Không thể cập nhật model này.');
            }
            $model->beforeUpdate();
            $model->beforeSave();
        });
        static::updated(function ($model) {
            $model->afterUpdate();
            $model->afterSave();
        });
        static::deleting(function ($model) {
            if (! $model->canDelete()) {
                throw new \LogicException('Không thể xóa model này.');
            }
            $model->beforeDelete();
        });
        static::deleted(fn ($model) => $model->afterDelete());
        static::restoring(function ($model) {
            if (! $model->canRestore()) {
                throw new \LogicException('Không thể khôi phục model này.');
            }
            $model->beforeRestore();
        });
        static::restored(fn ($model) => $model->afterRestore());
    }

    public function moveToTrash()
    {
        if (! $this->canMoveToTrash()) {
            return false;
        }

        $this->beforeMoveToTrash();
        if (in_array('trashed_status', $this->getFillable(), true)) {
            $this->trashed_status = 1;
            if (! $this->save()) {
                return false;
            }
            $result = $this->isSoftDeleteMode() ? $this->delete() : true;
        } else {
            $result = $this->delete();
        }
        if ($result) {
            $this->afterMoveToTrash();
        }

        return $result;
    }

    public function forceDelete()
    {
        if (! $this->canForceDelete()) {
            return false;
        }
        $this->beforeForceDelete();
        $result = $this->sysForceDelete();
        if ($result) {
            $this->afterForceDelete();
        }

        return $result;
    }

    public function restore()
    {
        $hasTrashStatus = in_array('trashed_status', $this->getFillable(), true);
        if (! $this->isSoftDeleteMode() && ! $hasTrashStatus) {
            return false;
        }
        if ($hasTrashStatus && $this->canRestore()) {
            $this->trashed_status = 0;
        }
        if ($this->isSoftDeleteMode()) {
            return $this->sysRestore();
        }
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }
        $result = $this->save();
        $this->fireModelEvent('restored', false);

        return $result;
    }

    public function canDelete(): bool
    {
        return true;
    }

    public function canUpdate(): bool
    {
        return true;
    }

    public function canRestore(): bool
    {
        return true;
    }

    public function canMoveToTrash(): bool
    {
        return true;
    }

    public function canForceDelete(): bool
    {
        return true;
    }

    protected function beforeCreate() {}

    protected function beforeUpdate() {}

    protected function beforeSave() {}

    protected function beforeDelete() {}

    protected function beforeRestore() {}

    protected function beforeMoveToTrash() {}

    protected function beforeForceDelete() {}

    protected function afterCreate() {}

    protected function afterUpdate() {}

    protected function afterSave() {}

    protected function afterDelete() {}

    protected function afterRestore() {}

    protected function afterMoveToTrash() {}

    protected function afterForceDelete() {}
}
