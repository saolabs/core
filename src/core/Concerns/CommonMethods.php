<?php

namespace Saola\Core\Concerns;

/** Metadata shared by system models and authentication models. */
trait CommonMethods
{
    protected $ignoreParse = [];

    public function getModelType(): string
    {
        return defined('static::MODEL_TYPE') ? static::MODEL_TYPE : 'default';
    }

    public function __getModelType__(): string
    {
        return $this->getModelType();
    }

    public function __get_table(): string
    {
        return $this->getTable();
    }

    /** Declared fields; resolving a model must not require a schema query. */
    public function __get_fields(): array
    {
        $fields = array_merge([$this->getKeyName()], $this->getFillable(), array_keys($this->getAttributes()));
        if ($this->useUuid && $this->useUuid !== 'no') {
            $fields[] = $this->useUuid === true ? 'uuid' : ($this->useUuid === 'primary' ? $this->getKeyName() : $this->useUuid);
        }
        if ($this->usesTimestamps()) {
            $fields[] = $this->getCreatedAtColumn();
            $fields[] = $this->getUpdatedAtColumn();
        }
        if ($this->isSoftDeleteMode()) {
            $fields[] = $this->getDeletedAtColumn();
        }

        return array_values(array_unique(array_filter($fields)));
    }

    public function getDefaultValues(): array
    {
        return $this->getAttributes();
    }

    public function getIgnoreParse(): array
    {
        return $this->ignoreParse;
    }

    public function getAttrData(): array
    {
        return $this->toArray();
    }

    public function getUseUuidAttribute(): bool|string
    {
        return $this->useUuid;
    }

    public function getModelTypeAttribute(): string
    {
        return $this->getModelType();
    }

    public function getFieldsAttribute(): array
    {
        return $this->__get_fields();
    }
}
