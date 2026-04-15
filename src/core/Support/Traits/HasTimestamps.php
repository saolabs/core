<?php

namespace Saola\Core\Support\Traits;

use Illuminate\Support\Facades\Auth;


trait HasTimestamps
{
    /**
     * Boot the trait
     */
    protected static function bootHasTimestamps()
    {
        static::creating(function ($model) {
            if (Auth::check() && method_exists($model, 'creator')) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && method_exists($model, 'updater')) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check() && method_exists($model, 'deleter')) {
                $model->deleted_by = Auth::id();
            }
        });
    }

    /**
     * Get the user who created the record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the record
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
} 