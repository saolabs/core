<?php

namespace Saola\Core\Models;

class MongoModel extends Model
{
    protected $connection = 'mongodb'; // Hoặc giá trị từ config
    const MODEL_TYPE = 'mongo';

    public function getModelType(): string
    {
        return self::MODEL_TYPE;
    }
}