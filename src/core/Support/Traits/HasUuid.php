<?php

namespace Saola\Core\Support\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType()
    {
        return 'string';
    }
} 