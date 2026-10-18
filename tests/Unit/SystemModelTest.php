<?php

namespace Tests\Unit;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Saola\Core\Database\Repositories\BaseRepository;
use Saola\Core\Models\Authenticatable;
use Saola\Core\Models\Model;
use Tests\TestCase;

class SystemModelTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('system_records', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('name');
            $table->string('password')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('soft_records', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_authentication_and_regular_models_share_system_metadata_and_hooks(): void
    {
        foreach ([SystemRecord::class, SystemIdentity::class] as $class) {
            $model = $class::create(['name' => 'Example', 'password' => 'private']);
            $this->assertSame('default', $model->__getModelType__());
            $this->assertSame('system_records', $model->__get_table());
            $this->assertContains('name', $model->fields);
            $this->assertSame(['creating', 'saving', 'created', 'saved'], $model->hooks);
            $this->assertTrue(Str::isUuid($model->uuid));
            $this->assertSame('uuid', $model->useUuid);
            $this->assertSame('default', $model->model_type);
            $this->assertArrayNotHasKey('password', $model->getAttrData());
            $this->assertTrue($model->canMoveToTrash());
            $this->assertTrue($model->canForceDelete());
            $this->assertFalse($model->isSoftDeleteMode());
        }
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Authenticatable::class, new SystemIdentity);
        $this->assertInstanceOf(CanResetPassword::class, new SystemIdentity);
    }

    public function test_authentication_models_work_with_the_system_repository_and_array_casts(): void
    {
        $repository = new SystemIdentityRepository;
        $model = $repository->create([
            'name' => 'Repository', 'password' => 'private', 'metadata' => ['source' => 'system'],
        ]);

        $this->assertInstanceOf(SystemIdentity::class, $model);
        $this->assertSame(['source' => 'system'], $model->fresh()->metadata);
        $this->assertSame($model->id, $repository->first(['name' => 'Repository'])->id);
        $this->assertSame(1, $repository->count());
        $updated = $repository->update($model->id, ['name' => 'Updated']);
        $this->assertSame('Updated', $updated->name);
        $this->assertTrue($repository->delete($model->id));
        $this->assertNull($repository->find($model->id));
    }

    public function test_soft_delete_is_opt_in_and_supports_trash_restore_and_force_delete(): void
    {
        $hard = SystemRecord::create(['name' => 'Hard']);
        $this->assertTrue($hard->delete());
        $this->assertSame(0, SystemRecord::count());

        $soft = SoftSystemRecord::create(['name' => 'Soft']);
        $this->assertTrue($soft->moveToTrash());
        $this->assertSame(0, SoftSystemRecord::count());
        $trashed = SoftSystemRecord::withTrashed()->findOrFail($soft->id);
        $this->assertTrue($trashed->trashed());
        $this->assertTrue($trashed->restore());
        $this->assertSame(1, SoftSystemRecord::count());
        $this->assertTrue($trashed->forceDelete());
        $this->assertSame(0, SoftSystemRecord::withTrashed()->count());
    }

    public function test_a_model_guard_blocks_updates_before_the_database_is_changed(): void
    {
        $model = GuardedSystemIdentity::create(['name' => 'Original']);
        try {
            $model->update(['name' => 'Rejected']);
            $this->fail('The system update guard was not called.');
        } catch (\LogicException $exception) {
            $this->assertSame('Không thể cập nhật model này.', $exception->getMessage());
            $this->assertSame('Original', $model->fresh()->name);
        }
    }
}

trait RecordsSystemHooks
{
    public array $hooks = [];

    public function __construct(array $attributes = [])
    {
        $this->useUuid = 'uuid';
        $this->table = 'system_records';
        $this->fillable = ['name', 'password', 'metadata'];
        $this->hidden = ['password'];
        $this->casts = ['metadata' => 'array'];
        parent::__construct($attributes);
    }

    protected function beforeCreate()
    {
        $this->hooks[] = 'creating';
    }

    protected function beforeSave()
    {
        $this->hooks[] = 'saving';
    }

    protected function afterCreate()
    {
        $this->hooks[] = 'created';
    }

    protected function afterSave()
    {
        $this->hooks[] = 'saved';
    }
}

class SystemRecord extends Model
{
    use RecordsSystemHooks;
}

class SystemIdentity extends Authenticatable
{
    use RecordsSystemHooks;
}

class GuardedSystemIdentity extends SystemIdentity
{
    public function canUpdate(): bool
    {
        return false;
    }
}

class SoftSystemRecord extends Model
{
    protected $table = 'soft_records';

    protected $fillable = ['name'];

    protected $deleteMode = 'soft';
}

class SystemIdentityRepository extends BaseRepository
{
    protected $model = SystemIdentity::class;
}
