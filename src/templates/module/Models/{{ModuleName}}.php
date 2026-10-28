<?php

namespace {{Namespace}}\Models;

use Saola\Core\Models\Model;

/**
 * Saola model: the core Model brings the system traits the core BaseRepository relies on.
 * Add SoftDeletes once the table has deleted_at, HasUuids for UUID keys.
 */
class {{ModelName}} extends Model
{
    protected $table = '{{model_table}}';

    protected $fillable = ['name'];
}
