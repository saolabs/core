<?php

namespace Saola\Core\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Saola\Core\Concerns\HasSystemModel;

/**
 * Class BaseModel
 *
 * Cung cấp các tính năng chung cho tất cả model trong hệ thống.
 */
abstract class Model extends EloquentModel
{
    use HasSystemModel;
}
