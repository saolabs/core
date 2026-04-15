<?php

namespace Saola\Core\Models;

class SQLModel extends Model
{
    protected $connection = 'mysql'; // Hoặc giá trị từ config
    const MODEL_TYPE = 'sql';

    public function getModelType(): string
    {
        return self::MODEL_TYPE;
    }
}