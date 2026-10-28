<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Saola\Core\Database\Repositories\BaseRepository;
use Saola\Core\Models\Model;
use Saola\Core\Services\ModuleService;
use Tests\TestCase;

class CrudNote extends Model
{
    use HasUuids;

    protected $table = 'crud_notes';
    protected $guarded = [];
    protected $deleteMode = 'soft';
}

class CrudNoteRepository extends BaseRepository
{
    protected $model = CrudNote::class;
}

class CrudNoteService extends ModuleService
{
    protected $repositoryClass = CrudNoteRepository::class;
}

/** ModuleService gives CRUD through its repository: UUID keys, one repository instance, writes that fail loudly. */
class ModuleServiceCrudTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('crud_notes', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->string('title')->unique(); $table->string('folder')->nullable(); $table->timestamps(); $table->softDeletes();
        });
    }

    public function test_crud_with_uuid_keys_through_the_declared_repository(): void
    {
        $service = new CrudNoteService();
        $this->assertInstanceOf(CrudNoteRepository::class, $service->getRepository());
        $this->assertSame($service->getRepository(), $service->getRepository());

        $note = $service->create(['title' => 'First']);
        $this->assertIsString($note->id);
        $this->assertSame('First', $service->find($note->id)->title);
        $this->assertSame('First', $service->findOrFail($note->id)->title);
        $this->assertNull($service->find('00000000-0000-0000-0000-000000000000'));
        $this->assertSame('Renamed', $service->update($note->id, ['title' => 'Renamed'])->title);

        $service->moveToTrash($note->id);
        $this->assertNull(CrudNote::find($note->id));
        $service->restoreFromTrash($note->id);
        $this->assertNotNull(CrudNote::find($note->id));

        $service->moveToTrash($note->id);
        $this->assertSame('In trash', $service->update($note->id, ['title' => 'In trash'])->title);
        $this->assertTrue(CrudNote::withTrashed()->find($note->id)->trashed());
        $this->assertTrue($service->getRepository()->forceDelete($note->id));
        $this->assertNull(CrudNote::withTrashed()->find($note->id));
    }

    public function test_conditions_on_undeclared_columns_still_filter_without_querying_schema_at_construction(): void
    {
        $service = new CrudNoteService();
        $service->create(['title' => 'A', 'folder' => 'inbox']);
        $service->create(['title' => 'B', 'folder' => 'archive']);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        new CrudNoteRepository();
        $this->assertSame([], \Illuminate\Support\Facades\DB::getQueryLog(), 'constructing a repository must not query the database');

        // CrudNote declares no $fillable ($guarded = []): "folder" comes from the table itself.
        $this->assertSame(['A'], $service->getRepository()->get(['folder' => 'inbox'])->pluck('title')->all());
        $this->assertSame(1, $service->getRepository()->count(['folder' => 'archive']));
        $this->assertSame('B', $service->getRepository()->firstOrFail(['folder' => 'archive'])->title);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $service->getRepository()->firstOrFail(['folder' => 'missing']);
    }

    public function test_trashed_directive_reads_the_trash_despite_the_soft_delete_scope(): void
    {
        $service = new CrudNoteService();
        $live = $service->create(['title' => 'Live']);
        $gone = $service->create(['title' => 'Gone']);
        $service->moveToTrash($gone->id);
        $repo = new CrudNoteRepository();

        $this->assertSame(['Gone'], $repo->get(['@trashed' => true])->pluck('title')->all());
        $this->assertSame(['Live'], $repo->get(['@trashed' => false])->pluck('title')->all());
        $this->assertSame(['Gone', 'Live'], $repo->get(['@trashed' => 'all', '@sortby' => ['title' => 'asc']])->pluck('title')->all());
        $this->assertSame(['Live'], $repo->get()->pluck('title')->all());
        // chained query-builder clauses compose with the directive
        $this->assertSame('Gone', $repo->where('title', 'like', 'G%')->first(['@trashed' => true])->title);
    }

    public function test_a_request_cannot_filter_or_sort_on_undeclared_columns(): void
    {
        $service = new CrudNoteService();
        $service->create(['title' => 'A', 'folder' => 'inbox']);
        $service->create(['title' => 'B', 'folder' => 'archive']);

        // Code may filter on any real column; a URL only on what the model declares (no probing hidden columns).
        $request = \Illuminate\Http\Request::create('/notes', 'GET', ['folder' => 'archive', 'orderby_folder' => 'desc']);
        $this->assertCount(2, (new CrudNoteRepository())->getResults($request));
        $this->assertCount(1, (new CrudNoteRepository())->get(['folder' => 'archive']));
    }

    public function test_update_reads_the_row_once_and_keeps_repository_scope(): void
    {
        $service = new CrudNoteService();
        $note = $service->create(['title' => 'A', 'folder' => 'inbox']);
        $repo = new CrudNoteRepository();
        $repo->get(); // schema columns cached, as in a running process

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $repo->update($note->id, ['title' => 'A2']);
        $sql = array_column(\Illuminate\Support\Facades\DB::getQueryLog(), 'query');
        $this->assertCount(2, $sql, implode("\n", $sql)); // one select, one update: no count(*) round trip
        $this->assertStringStartsWith('update', end($sql));

        // a model the caller already holds (e.g. locked) is not read again
        \Illuminate\Support\Facades\DB::flushQueryLog();
        $repo->update(CrudNote::find($note->id), ['title' => 'A3']);
        $this->assertCount(2, \Illuminate\Support\Facades\DB::getQueryLog()); // the find() above + the update

        // A default condition (how owner_id scoping works) also guards updates, including of trashed rows.
        $scoped = new CrudNoteRepository();
        $scoped->addDefaultParam('folder', 'folder', 'archive');
        $this->assertFalse($scoped->update($note->id, ['title' => 'Hijack']));
        $service->moveToTrash($note->id);
        $this->assertFalse($scoped->update($note->id, ['title' => 'Hijack']));
        $this->assertSame('A3', CrudNote::withTrashed()->find($note->id)->title);
    }

    public function test_misspelt_column_fails_loudly_and_chained_clauses_do_not_leak(): void
    {
        $service = new CrudNoteService();
        $service->create(['title' => 'A', 'folder' => 'inbox']);
        $service->create(['title' => 'B', 'folder' => 'archive']);
        $repo = new CrudNoteRepository();

        try {
            $repo->where('title', 'A')->get(['folderr' => 'inbox']);
            $this->fail('An unknown column must not be dropped silently.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("Unknown column 'folderr'", $e->getMessage());
        }
        // the where('title', 'A') of the failed query is gone
        $this->assertCount(2, $repo->get());
    }

    public function test_service_rejects_undefined_methods(): void
    {
        $this->expectException(\BadMethodCallException::class);
        (new CrudNoteService())->craete(['title' => 'typo']);
    }

    public function test_a_failed_read_is_reported_even_though_it_falls_back(): void
    {
        \Illuminate\Support\Facades\Exceptions::fake();
        $service = new CrudNoteService();
        Schema::drop('crud_notes');
        $this->assertCount(0, $service->getResults(\Illuminate\Http\Request::create('/notes')));
        \Illuminate\Support\Facades\Exceptions::assertReported(\Illuminate\Database\QueryException::class);
    }

    public function test_find_or_fail_throws_for_a_missing_key(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        (new CrudNoteService())->findOrFail('00000000-0000-0000-0000-000000000000');
    }

    public function test_a_failed_write_surfaces_instead_of_returning_false(): void
    {
        $service = new CrudNoteService();
        $service->create(['title' => 'Same']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $service->create(['title' => 'Same']);
    }
}
