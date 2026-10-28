<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Saola\Core\Models\Model;
use Tests\TestCase;

/** A core model may use Laravel's HasUuids (UUID v7 keys) instead of the core Uuid trait. */
class CoreModelUuidsTest extends TestCase
{
    public function test_core_model_accepts_laravel_has_uuids(): void
    {
        $model = new class extends Model { use HasUuids; protected $table = 'things'; };
        $this->assertFalse($model->getIncrementing());
        $this->assertSame('string', $model->getKeyType());
        $this->assertTrue(\Illuminate\Support\Str::isUuid($model->newUniqueId()));
    }
}
