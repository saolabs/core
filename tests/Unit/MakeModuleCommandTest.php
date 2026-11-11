<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Saola\Core\Console\Commands\MakeModuleCommand;
use Saola\Core\Database\Repositories\BaseRepository;
use Saola\Core\Masks\Mask;
use Saola\Core\Models\Model;
use Saola\Core\Services\ModuleService;
use Tests\TestCase;

/** `make:module` builds on the core classes: Model, BaseRepository, ModuleService ($repositoryClass), controllers injecting the service. */
class MakeModuleCommandTest extends TestCase
{
    private string $root;
    private string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = $this->app->basePath();
        $this->root = sys_get_temp_dir() . '/sao-module-' . bin2hex(random_bytes(4));
        mkdir($this->root, 0777, true);
        $this->app->setBasePath($this->root);
        Artisan::registerCommand($this->app->make(MakeModuleCommand::class));
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->base);
        exec('rm -rf ' . escapeshellarg($this->root));
        parent::tearDown();
    }

    private function file(string $path): string
    {
        return file_get_contents($this->root . '/app/Modules/' . $path);
    }

    private function lint(string $module): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root . '/app/Modules/' . $module, \FilesystemIterator::SKIP_DOTS)) as $file) {
            exec('php -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $out, $code);
            $this->assertSame(0, $code, implode("\n", $out));
            $this->assertStringNotContainsString('{{', file_get_contents($file->getPathname()), $file->getPathname());
        }
    }

    public function test_generates_the_module_on_core_base_classes_without_extra_layers(): void
    {
        $this->artisan('make:module', ['name' => 'Shop', '--model' => 'Product'])->assertSuccessful();
        $this->lint('Shop');

        foreach (['Models/Product', 'Repositories/ShopRepository', 'Services/ShopService', 'Masks/ShopMask', 'Masks/ShopCollection'] as $class) {
            require_once $this->root . '/app/Modules/Shop/' . $class . '.php';
        }
        $this->assertTrue(is_subclass_of('App\Modules\Shop\Models\Product', Model::class));
        $this->assertTrue(is_subclass_of('App\Modules\Shop\Repositories\ShopRepository', BaseRepository::class));
        $this->assertTrue(is_subclass_of('App\Modules\Shop\Services\ShopService', ModuleService::class));
        $this->assertTrue(is_subclass_of('App\Modules\Shop\Masks\ShopMask', Mask::class));
        $this->assertSame('products', (new \ReflectionClass('App\Modules\Shop\Models\Product'))->getDefaultProperties()['table']);
        $this->assertSame('App\Modules\Shop\Repositories\ShopRepository', (new \ReflectionClass('App\Modules\Shop\Services\ShopService'))->getDefaultProperties()['repositoryClass']);

        // No interfaces, no bindings, no hand-written CRUD: the core classes already provide them.
        $this->assertDirectoryDoesNotExist($this->root . '/app/Modules/Shop/Contracts');
        $this->assertSame([], glob($this->root . '/app/Modules/Shop/*/*Interface.php'));
        $this->assertStringNotContainsString('->bind(', $this->file('Shop/ModuleServiceProvider.php'));
        $this->assertStringNotContainsString('public function', preg_replace('/^.*class ShopRepository.*?\{/s', '', $this->file('Shop/Repositories/ShopRepository.php')));
        $this->assertStringNotContainsString('public function', preg_replace('/^.*class ShopService.*?\{/s', '', $this->file('Shop/Services/ShopService.php')));
        foreach (['Api', 'Web', 'Admin'] as $context) {
            $controller = $this->file("Shop/Http/Controllers/{$context}/ShopController.php");
            $this->assertStringContainsString('public function __construct(private ShopService $service) {}', $controller);
            $this->assertStringContainsString('$this->service->getResults($request)', $controller);
        }
    }

    public function test_ignoring_the_repository_also_skips_the_service_that_needs_it(): void
    {
        $this->artisan('make:module', ['name' => 'Report', '--ignore' => ['repository']])
            ->expectsOutputToContain('Skipping service')->assertSuccessful();
        $this->lint('Report');
        $this->assertDirectoryDoesNotExist($this->root . '/app/Modules/Report/Repositories');
        $this->assertDirectoryDoesNotExist($this->root . '/app/Modules/Report/Services');
        $controller = $this->file('Report/Http/Controllers/Api/ReportController.php');
        $this->assertStringNotContainsString('__construct', $controller);
        $this->assertStringContainsString('jsonSuccess([])', $controller);
    }
}
