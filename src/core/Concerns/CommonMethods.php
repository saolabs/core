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

    /** @var array<string, list<string>> connection.table => columns, read once per process */
    protected static array $schemaColumns = [];

    /**
     * Columns code may filter on: the declared fields plus the table's real columns, read lazily when a query is
     * built (never while a repository is constructed) and cached per connection.table for the process.
     * Requests stay limited to the declared fields (FilterAction::getDeclaredFields()).
     */
    public function __get_columns(): array
    {
        $columns = $this->__schema_columns();

        return $columns === null ? $this->__get_fields() : array_values(array_unique([...$this->__get_fields(), ...$columns]));
    }

    /** The table's columns, or null when the schema cannot be read here (no database): then nothing is cached. */
    public function __schema_columns(): ?array
    {
        $key = $this->getConnectionName().'.'.$this->getTable();
        if (!array_key_exists($key, static::$schemaColumns)) {
            try {
                $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
            } catch (\Throwable) {
                return null;
            }
            if ($columns === []) {
                return null; // table not migrated yet: do not freeze an empty list
            }
            static::$schemaColumns[$key] = $columns;
        }

        return static::$schemaColumns[$key];
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
