<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\View;
use Saola\Core\Engines\ViewContextManager;
use Saola\Core\Engines\ViewContextRegistry;
use Saola\Core\Providers\SaolaServiceProvider;
use Saola\Core\Support\SPA;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\View\Services\ViewHelperService;
use Saola\Core\View\Services\ViewStorageManager;
use Tests\TestCase;

class ViewContextRuntimeTest extends TestCase
{
    public function test_it_selects_theme_views_without_mutating_the_registered_context(): void
    {
        $registry = new ViewContextRegistry();
        $request = new ViewContextManager($registry);
        $request->registerContext('web', ['base' => 'web']);

        $request->setContextViews(
            'web',
            '/var/www/app/resources/saola/themes/storefront/views'
        );

        $this->assertSame('themes.storefront', $request->getContextViews('web'));
        $this->assertSame(
            'themes.storefront.modules.todo.index',
            $request->resolvePath('web', 'todo', 'index', 'modules')
        );
        $this->assertSame(
            'themes.storefront.layouts.',
            $request->getContextVariable('web', '__layout__')
        );

        $nextRequest = new ViewContextManager($registry);

        $this->assertSame('web', $nextRequest->getContextViews('web'));
        $this->assertSame(
            'web.modules.todo.index',
            $nextRequest->resolvePath('web', 'todo', 'index', 'modules')
        );
    }

    public function test_it_accepts_a_context_to_views_map_for_the_current_request(): void
    {
        $manager = new ViewContextManager();
        $manager->registerContext('web', ['base' => 'web']);
        $manager->registerContext('admin', ['base' => 'admin']);

        $manager->setContextViews([
            'web' => 'themes/storefront',
            'admin' => 'themes.control-panel',
        ]);

        $this->assertSame('themes.storefront.pages.about', $manager->resolvePath('web', '', 'about', 'pages'));
        $this->assertSame('themes.control-panel.layouts.main', $manager->resolvePath('admin', '', 'main', 'layouts'));
    }

    public function test_reset_only_clears_request_state_and_keeps_registry_state(): void
    {
        $manager = new ViewContextManager();
        $manager->registerContext('web', ['base' => 'web']);
        $manager->registerContextViewByRoute('web', 'web.home', 'web.pages.home');
        $manager->setContextViews('web', 'themes.storefront');
        $manager->share('web', ['tenant' => 10]);

        $manager->resetInstanceState();

        $this->assertSame('web', $manager->getContextViews('web'));
        $this->assertSame([], $manager->getSharedData('web'));
        $this->assertSame('web.pages.home', $manager->getViewPathByRoute('web', 'web.home'));
    }

    public function test_laravel_container_creates_a_fresh_manager_for_each_scope(): void
    {
        (new SaolaServiceProvider($this->app))->register();

        $first = $this->app->make(ViewContextManager::class);
        $first->registerContext('web', ['base' => 'web']);
        $first->setContextViews('web', 'themes.first');

        $this->app->forgetScopedInstances();

        $second = $this->app->make(ViewContextManager::class);

        $this->assertNotSame($first, $second);
        $this->assertSame('web', $second->getContextViews('web'));
    }

    public function test_view_storage_reset_clears_hydration_system_data(): void
    {
        $storage = new ViewStorageManager();
        $storage->setSystemData(['theme' => 'storefront']);

        $storage->reset();

        $this->assertSame([], $storage->exportSystemData());
    }

    public function test_static_route_descriptors_are_materialized_per_request_context(): void
    {
        SPA::resetRoutes();
        SPA::active();
        SPA::addRoute('web', 'web.todo.index', '/todo', [
            'kind' => 'explicit',
            'logical' => '@MODULE:todo.index',
            'candidates' => ['@MODULE:todo.index'],
        ]);
        SPA::inactive();

        $registry = new ViewContextRegistry();
        $first = new ViewContextManager($registry);
        $first->registerContext('web', ['base' => 'web']);
        $first->setContextViews('web', 'themes.first');
        $firstRoutes = (new ViewHelperService(new ViewStorageManager(), $first))
            ->exportComponentRoutes('web');

        $second = new ViewContextManager($registry);
        $second->setContextViews('web', 'themes.second');
        $secondRoutes = (new ViewHelperService(new ViewStorageManager(), $second))
            ->exportComponentRoutes('web');

        $this->assertSame('@MODULE:todo.index', SPA::getComponentRoutes('web')[0]['component']['logical']);
        $this->assertSame('web', $firstRoutes[0]['context']);
        $this->assertSame('@MODULE:todo.index', $firstRoutes[0]['logicalComponent']);
        $this->assertSame('themes.first.modules.todo.index', $firstRoutes[0]['component']);
        $this->assertSame('themes.second.modules.todo.index', $secondRoutes[0]['component']);
        $this->assertNotSame(
            $first->getContextViewRevision('web'),
            $second->getContextViewRevision('web')
        );
    }

    public function test_legacy_concrete_route_paths_are_rebased_without_mutating_registry(): void
    {
        // Khoá component gửi cho client chỉ đổi sang theme khi theme THẬT SỰ
        // mang view đó: registry JS chỉ có khoá của view theme đã compile, nên
        // trả khoá theme cho view theme không đè = client không hydrate được.
        $root = sys_get_temp_dir() . '/sao-rc-' . bin2hex(random_bytes(4));
        mkdir($root . '/themes/storefront/pages', 0777, true);
        mkdir($root . '/web/pages', 0777, true);
        file_put_contents($root . '/themes/storefront/pages/home.blade.php', 'x');
        file_put_contents($root . '/web/pages/about.blade.php', 'x');   // chỉ base có
        View::getFinder()->addLocation($root);

        $registry = new ViewContextRegistry();
        $manager = new ViewContextManager($registry);
        $manager->registerContext('web', ['base' => 'web']);
        $manager->registerContextViewByRoute('web', 'web.home', 'web.pages.home');
        $manager->setContextViews('web', 'themes.storefront');

        $this->assertSame(
            'themes.storefront.pages.home',
            $manager->resolveRouteComponent('web', 'web.pages.home')
        );
        // Theme không đè `pages.about` ⇒ giữ khoá của base.
        $this->assertSame(
            'web.pages.about',
            $manager->resolveRouteComponent('web', 'web.pages.about')
        );
        $this->assertSame('web.pages.home', $registry->get('web')['routeViews']['web.home']['view']);

        @unlink($root . '/themes/storefront/pages/home.blade.php');
        @unlink($root . '/web/pages/about.blade.php');
        @rmdir($root . '/web/pages');
        @rmdir($root . '/web');
        @rmdir($root . '/themes/storefront/pages');
        @rmdir($root . '/themes/storefront');
        @rmdir($root . '/themes');
        @rmdir($root);
    }

    public function test_json_response_only_sends_materialized_routes_when_revision_changes(): void
    {
        $manager = $this->app->make(ViewContextManager::class);
        if (!$manager->hasContext('web')) {
            $manager->registerContext('web', ['base' => 'web']);
        }
        $manager->setContextViews('web', 'themes.storefront');
        $this->app->instance(ViewContextManager::class, $manager);
        // Đếm số lần dựng cả bảng route: response không đổi revision không được dựng.
        $helper = new class(new ViewStorageManager(), $manager) extends ViewHelperService {
            public int $tableExports = 0;
            public function exportSpaRoutes($context = null)
            {
                $this->tableExports++;
                return parent::exportSpaRoutes($context);
            }
            public function exportComponentRoutes($context = null)
            {
                $this->tableExports++;
                return parent::exportComponentRoutes($context);
            }
        };
        $this->app->instance(ViewHelperService::class, $helper);

        SPA::resetRoutes();
        SPA::active();
        SPA::addRoute('web', 'web.home', '/', [
            'kind' => 'explicit',
            'logical' => '@PAGE:home',
            'candidates' => ['@PAGE:home'],
        ]);
        SPA::inactive();

        $route = (new \Illuminate\Routing\Route(['GET'], '/', fn () => null))->name('web.home');
        $request = \Illuminate\Http\Request::create('/', 'GET', server: [
            'HTTP_X_SAOLA_VIEW_REVISION' => 'stale-revision',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);

        $controller = new class {
            use ResponseMethods;
            protected string $context = 'web';
        };
        $changed = $controller->response(['ok' => true], null, ['forceJson' => true]);
        $payload = $changed->getData(true);

        $this->assertTrue($payload['viewContext']['changed']);
        $this->assertSame('themes.storefront.pages.home', $payload['viewContext']['component']);
        $this->assertSame('themes.storefront.pages.home', $payload['viewContext']['routes'][0]['component']);
        $this->assertSame('themes.storefront.pages.home', $payload['view']);
        $this->assertSame(1, $helper->tableExports);

        $request->headers->set('X-Saola-View-Revision', $payload['viewContext']['revision']);
        $unchanged = $controller->response(['ok' => true], null, ['forceJson' => true]);
        $unchangedPayload = $unchanged->getData(true);

        $this->assertSame(1, $helper->tableExports, 'Revision khớp thì không được dựng lại bảng route.');
        // Revision khớp: chỉ còn data, auth và khoá view — không khối viewContext nào.
        $this->assertEqualsCanonicalizing(['data', 'auth', 'view'], array_keys($unchangedPayload));
        $this->assertSame('themes.storefront.pages.home', $unchangedPayload['view']);
        $this->assertSame($payload['viewContext']['revision'], $unchanged->headers->get('X-Saola-View-Revision'));

        // Dữ liệu share của context đổi ⇒ revision đổi ⇒ response kế tiếp mang bản mới.
        $site = ['name' => 'Cũ'];
        $manager->shareUsing('web', function () use (&$site) { return ['site' => $site]; });
        $shared = $controller->response(['ok' => true], null, ['forceJson' => true])->getData(true);
        $this->assertTrue($shared['viewContext']['changed']);
        $this->assertSame(['site' => ['name' => 'Cũ']], $shared['viewContext']['sharedData']);
        $this->assertArrayNotHasKey('site', $shared['data'], 'Shared data không đi lẫn vào data của trang.');

        $request->headers->set('X-Saola-View-Revision', $shared['viewContext']['revision']);
        $this->assertArrayNotHasKey('viewContext', $controller->response(['ok' => true], null, ['forceJson' => true])->getData(true));

        $site = ['name' => 'Mới'];
        $refreshed = $controller->response(['ok' => true], null, ['forceJson' => true])->getData(true);
        $this->assertSame('Mới', $refreshed['viewContext']['sharedData']['site']['name']);
        // Revision của script bootstrap dùng chung không phụ thuộc shared data.
        $this->assertSame($payload['viewContext']['revision'], $manager->getContextViewRevision('web', false));
    }

    private function siteProvider(array &$data, string &$version): \Saola\Core\Contracts\SiteProvider
    {
        return new class($data, $version) implements \Saola\Core\Contracts\SiteProvider {
            public int $reads = 0;
            public function __construct(private array &$data, private string &$version) {}
            public function version(): string { return $this->version; }
            public function data(string $locale): array { $this->reads++; return $this->data + ['locale' => $locale]; }
        };
    }

    public function test_site_is_a_system_variable_that_cannot_be_overridden(): void
    {
        $manager = new ViewContextManager(new ViewContextRegistry());
        $manager->registerContext('web', ['base' => 'web']);
        $data = ['general' => ['website_name' => 'Gehihi']];
        $version = '1';
        $provider = $this->siteProvider($data, $version);
        $manager->provideSite($provider);

        $system = $manager->exportContextState('web')['systemData'];
        $this->assertSame('Gehihi', $system['__site']->general['website_name']);
        $this->assertSame('web', $system['__context__']);
        // Một lần giải mỗi request dù gọi nhiều lần.
        $manager->site();
        $manager->getContextViewRevision('web');
        $this->assertSame(1, $provider->reads);

        // Share hay data mang tên hệ thống: debug thì báo lỗi, view data thì bị bỏ.
        config(['app.debug' => true]);
        try {
            $manager->share('web', ['__site' => []]);
            $this->fail('share(__site) phải bị chặn.');
        } catch (\InvalidArgumentException) {
        }
        config(['app.debug' => false]);
        $manager->share('web', ['__site' => ['x' => 1], '__layout__' => 'x', 'ok' => 1]);
        $this->assertSame(['ok' => 1], $manager->getSharedData('web'));
        $manager->shareUsing('web', fn () => ['__site' => 'hack', 'other' => 2]);
        $this->assertSame(['other' => 2, 'ok' => 1], $manager->getSharedData('web'));

        $dir = sys_get_temp_dir().'/saola-vcm-'.getmypid();
        @mkdir($dir.'/web', 0777, true);
        file_put_contents($dir.'/web/home.blade.php', '');
        View::addLocation($dir);
        $view = $manager->render('web', '', 'home', ['__site' => 'hack', '__context__' => 'admin', 'title' => 'T']);
        $this->assertSame('Gehihi', $view->getData()['__site']['general']['website_name']);
        $this->assertSame('Gehihi', $view->getData()['__site']->general->website_name, 'Blade dịch __site.general.x thành ->.');
        $this->assertSame('{}', json_encode(ViewContextManager::siteObject([])));
        $this->assertSame('{"pwa":{"icons":[{"src":"/a.png"}]}}', json_encode(ViewContextManager::siteObject(['pwa' => ['icons' => [['src' => '/a.png']]]]), JSON_UNESCAPED_SLASHES));
        $this->assertSame('/a.png', ViewContextManager::siteObject(['pwa' => ['icons' => [['src' => '/a.png']]]])->pwa->icons[0]->src);
        $this->assertSame('web', $view->getData()['__context__']);
        $this->assertSame('T', $view->getData()['title']);

        // Reset Octane: lần giải đã nhớ biến mất, provider (registry) vẫn còn.
        $manager->resetInstanceState();
        $data['general']['website_name'] = 'Mới';
        $this->assertSame('Mới', $manager->site()['general']['website_name']);
    }

    public function test_site_version_drives_view_revision_but_not_bootstrap_revision(): void
    {
        $manager = new ViewContextManager(new ViewContextRegistry());
        $manager->registerContext('web', ['base' => 'web']);
        $before = $manager->getContextViewRevision('web');
        $data = ['general' => ['website_name' => 'A']];
        $version = '1';
        $manager->provideSite($this->siteProvider($data, $version));
        $withSite = $manager->getContextViewRevision('web');
        $this->assertNotSame($before, $withSite);
        $version = '2';
        $this->assertNotSame($withSite, $manager->getContextViewRevision('web'));
        $this->assertSame($before, $manager->getContextViewRevision('web', false));
        app()->setLocale('en');
        $en = $manager->getContextViewRevision('web');
        app()->setLocale('vi');
        $this->assertNotSame($en, $manager->getContextViewRevision('web'));
    }

    public function test_new_client_json_body_only_carries_data_view_and_auth(): void
    {
        $manager = new ViewContextManager(new ViewContextRegistry());
        if (!$manager->hasContext('web')) {
            $manager->registerContext('web', ['base' => 'web']);
        }
        $data = ['general' => ['website_name' => 'Gehihi']];
        $version = '1';
        $manager->provideSite($this->siteProvider($data, $version));
        $this->app->instance(ViewContextManager::class, $manager);
        $this->app->instance(ViewHelperService::class, new ViewHelperService(new ViewStorageManager(), $manager));

        SPA::resetRoutes();
        SPA::active();
        SPA::addRoute('web', 'web.home', '/', ['kind' => 'explicit', 'logical' => '@PAGE:home', 'candidates' => ['@PAGE:home']]);
        SPA::inactive();
        $route = (new \Illuminate\Routing\Route(['GET'], '/', fn () => null))->name('web.home');
        $request = \Illuminate\Http\Request::create('/', 'GET', server: [
            'HTTP_X_SAOLA_VIEW_REVISION' => 'stale',
            'HTTP_X_SAOLA_CLIENT' => '2',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set('saola.i18n', fn () => ['scope' => 'web:abc', 'catalogs' => ['vi' => ['revision' => 7, 'messages' => ['a' => 'b']]]]);
        $this->app->instance('request', $request);
        $controller = new class {
            use ResponseMethods;
            protected string $context = 'web';
        };

        $response = $controller->response(['ok' => true], null, ['forceJson' => true]);
        $payload = $response->getData(true);
        // Revision lệch, catalog thiếu, có __site — body vẫn chỉ ba khoá.
        $this->assertEqualsCanonicalizing(['data', 'view', 'auth'], array_keys($payload));
        $this->assertSame(['ok' => true], $payload['data']);
        $this->assertSame('1', $response->headers->get('X-Saola-Envelope'));
        $this->assertSame($manager->getContextViewRevision('web'), $response->headers->get('X-Saola-View-Revision'));
        $this->assertSame('web:abc;vi=7', $response->headers->get('X-Saola-Language-Revision'));
        $this->assertStringNotContainsString('Gehihi', $response->getContent(), '__site không bao giờ nằm trong body JSON.');

        // Client đã giữ catalog: không header ngôn ngữ.
        $request->headers->set('X-Saola-Language', 'web:abc;vi=7');
        $request->attributes->set('saola.i18n', fn ($held) => \Saola\Core\Language\ClientCatalogs::withoutHeld(['scope' => 'web:abc', 'catalogs' => ['vi' => ['revision' => 7]]], $held));
        $this->assertNull($controller->response(['ok' => true], null, ['forceJson' => true])->headers->get('X-Saola-Language-Revision'));
    }
}
