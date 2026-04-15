<?php

namespace Saola\Core\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Saola\Core\Concerns\Uuid;
use Saola\Core\Concerns\ModelEventMethods;
use Saola\Core\Concerns\ModelFileMethods;

/**
 * Class BaseModel
 *
 * Cung cấp các tính năng chung cho tất cả model trong hệ thống.
 */
abstract class Model extends EloquentModel
{
    use SoftDeletes, Uuid, ModelEventMethods, ModelFileMethods;

    /**
     * Khởi động model và gọi các phương thức boot từ các trait.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::bootUuid();
        static::bootModelEventMethods();
        static::bootModelFileMethods();
    }


}
