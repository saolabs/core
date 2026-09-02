<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Saola\Core\Providers\BladeDirectiveServiceProvider;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\View\Services\ViewHelperService;
use Tests\TestCase;

/**
 * $__helper phải có trong MỌI view, không phụ thuộc middleware.
 *
 * Directive nào của core cũng sinh ra `$__helper->...`, nên route quên alias
 * 'webview' là fatal — hoặc tệ hơn dưới Octane: view factory là singleton và
 * Octane KHÔNG xoá shared data giữa các request, trong khi ViewHelperService
 * là scoped nên BỊ xoá; request sau dùng lại $__helper của request trước.
 */
class ViewHelperAvailabilityTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function bootSaola(): void
    {
        (new SaolaServiceProvider($this->app))->register();
        $provider = new BladeDirectiveServiceProvider($this->app);
        $provider->register();
        $provider->boot();
    }

    private function bladeFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sao').'.blade.php';
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_view_render_duoc_khi_route_khong_khai_bao_middleware(): void
    {
        $this->bootSaola();

        $html = View::file($this->bladeFile(
            "@startMarker('output', 'o1'){{ \$n }}@endMarker('output', 'o1')"
        ), ['n' => 7])->render();

        $this->assertStringContainsString('7', $html);
    }

    public function test_moi_request_thay_helper_moi_va_storage_rong(): void
    {
        $this->bootSaola();
        $path = $this->bladeFile("@startMarker('output', 'o1'){{ \$n }}@endMarker('output', 'o1')");

        // "Request" 1 — render xong, storage của helper đã có marker.
        $first = View::file($path, ['n' => 1]);
        $first->render();
        $helperOne = $first->getData()['__helper'];

        // Octane giữa hai request: Listeners\FlushTemporaryContainerInstances.
        // View factory (singleton) KHÔNG bị đụng tới — nếu $__helper còn đến từ
        // View::share() thì đây đúng là chỗ nó rò sang request sau.
        $this->app->forgetScopedInstances();

        // "Request" 2 — trên route KHÔNG có middleware nào chạy reset().
        $second = View::file($path, ['n' => 2]);
        $second->render();
        $helperTwo = $second->getData()['__helper'];

        $this->assertInstanceOf(ViewHelperService::class, $helperOne);
        $this->assertInstanceOf(ViewHelperService::class, $helperTwo);
        $this->assertNotSame($helperOne, $helperTwo, 'request sau vẫn dùng lại helper của request trước');
    }

    public function test_data_cua_view_thang_shared_data_cu(): void
    {
        $this->bootSaola();

        // Đúng thứ WebViewManager để lại: một helper cũ nằm trong shared data
        // của view factory. Nó sống sót qua forgetScopedInstances() vì factory
        // là singleton — nên composer PHẢI đè được nó.
        $stale = new ViewHelperService(
            $this->app->make(\Saola\Core\View\Services\ViewStorageManager::class),
            $this->app->make(\Saola\Core\Engines\ViewContextManager::class),
        );
        View::share('__helper', $stale);

        $view = View::file($this->bladeFile('x'), []);
        $view->render();

        $this->assertNotSame($stale, $view->getData()['__helper']);
    }
}
