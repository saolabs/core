<?php

namespace Saola\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Saola\Core\View\Compilers\SimplePhpStructureParserService;
use Saola\Core\View\Compilers\CommonDirectiveService;
use Saola\Core\View\Compilers\AttrDirectiveService;
use Saola\Core\View\Compilers\BindingDirectiveService;
use Saola\Core\View\Compilers\BlockDirectiveService;
use Saola\Core\View\Compilers\ClientSideDirectiveService;
use Saola\Core\View\Compilers\ComponentDirectiveService;
use Saola\Core\View\Compilers\EventDirectiveService;
use Saola\Core\View\Compilers\HydrationDirectiveService;
use Saola\Core\View\Compilers\LetConstDirectiveService;
use Saola\Core\View\Compilers\OutDirectiveService;
use Saola\Core\View\Compilers\PageDirectiveService;
use Saola\Core\View\Compilers\ReactiveDirectiveService;
use Saola\Core\View\Compilers\ServerSideDirectiveService;
use Saola\Core\View\Compilers\SetupDirectiveService;
use Saola\Core\View\Compilers\SubscribeDirectiveService;
use Saola\Core\View\Compilers\TemplateDirectiveService;
use Saola\Core\View\Compilers\VarsDirectiveService;
use Saola\Core\View\Compilers\WrapperDirectiveService;
use Saola\Core\View\Compilers\YieldDirectiveService;

class BladeDirectiveServiceProvider extends ServiceProvider
{
    /** @var SimplePhpStructureParserService */
    protected $phpParser;
    /** @var AttrDirectiveService */
    protected $attrService;
    /** @var BindingDirectiveService */
    protected $bindingService;
    /** @var BlockDirectiveService */
    protected $blockService;
    /** @var ClientSideDirectiveService */
    protected $clientSideService;
    /** @var CommonDirectiveService */
    protected $commonService;
    /** @var ComponentDirectiveService */
    protected $componentService;
    /** @var EventDirectiveService */
    protected $eventService;
    /** @var HydrationDirectiveService */
    protected $hydrationService;
    /** @var LetConstDirectiveService */
    protected $letConstService;
    /** @var OutDirectiveService */
    protected $outService;
    /** @var PageDirectiveService */
    protected $pageDirectiveService;
    /** @var ReactiveDirectiveService */
    protected $reactiveService;
    /** @var ServerSideDirectiveService */
    protected $serverSideService;
    /** @var SetupDirectiveService */
    protected $setupService;
    /** @var SubscribeDirectiveService */
    protected $subscribeService;
    /** @var TemplateDirectiveService */
    protected $templateService;
    /** @var VarsDirectiveService */
    protected $varsService;
    /** @var WrapperDirectiveService */
    protected $wrapperService;
    /** @var YieldDirectiveService */
    protected $yieldService;

    /**
     * Register services.
     */
    public function register(): void
    {
        $services = [
            SimplePhpStructureParserService::class,
            CommonDirectiveService::class,
            AttrDirectiveService::class,
            BindingDirectiveService::class,
            BlockDirectiveService::class,
            ClientSideDirectiveService::class,
            ComponentDirectiveService::class,
            EventDirectiveService::class,
            HydrationDirectiveService::class,
            LetConstDirectiveService::class,
            OutDirectiveService::class,
            PageDirectiveService::class,
            ReactiveDirectiveService::class,
            ServerSideDirectiveService::class,
            SetupDirectiveService::class,
            SubscribeDirectiveService::class,
            TemplateDirectiveService::class,
            VarsDirectiveService::class,
            WrapperDirectiveService::class,
            YieldDirectiveService::class,
        ];

        foreach ($services as $service) {
            $this->app->singleton($service);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        app('view.finder')->addExtension('one');

        // Resolve all services from the container
        $this->phpParser = $this->app->make(SimplePhpStructureParserService::class);
        $this->attrService = $this->app->make(AttrDirectiveService::class);
        $this->bindingService = $this->app->make(BindingDirectiveService::class);
        $this->blockService = $this->app->make(BlockDirectiveService::class);
        $this->clientSideService = $this->app->make(ClientSideDirectiveService::class);
        $this->commonService = $this->app->make(CommonDirectiveService::class);
        $this->componentService = $this->app->make(ComponentDirectiveService::class);
        $this->eventService = $this->app->make(EventDirectiveService::class);
        $this->hydrationService = $this->app->make(HydrationDirectiveService::class);
        $this->letConstService = $this->app->make(LetConstDirectiveService::class);
        $this->outService = $this->app->make(OutDirectiveService::class);
        $this->pageDirectiveService = $this->app->make(PageDirectiveService::class);
        $this->reactiveService = $this->app->make(ReactiveDirectiveService::class);
        $this->serverSideService = $this->app->make(ServerSideDirectiveService::class);
        $this->setupService = $this->app->make(SetupDirectiveService::class);
        $this->subscribeService = $this->app->make(SubscribeDirectiveService::class);
        $this->templateService = $this->app->make(TemplateDirectiveService::class);
        $this->varsService = $this->app->make(VarsDirectiveService::class);
        $this->wrapperService = $this->app->make(WrapperDirectiveService::class);
        $this->yieldService = $this->app->make(YieldDirectiveService::class);

        // Register all directives
        $this->registerDirectives();
        $this->attrService->registerDirectives();
        $this->bindingService->registerDirectives();
        $this->blockService->registerDirectives();
        $this->clientSideService->registerDirectives();
        $this->componentService->registerDirectives();
        $this->eventService->registerDirectives();
        $this->hydrationService->registerDirectives();
        $this->letConstService->registerDirectives();
        $this->outService->registerDirectives();
        $this->pageDirectiveService->registerDirectives();
        $this->reactiveService->registerDirectives();
        $this->serverSideService->registerDirectives();
        $this->setupService->registerDirectives();
        $this->subscribeService->registerDirectives();
        $this->templateService->registerDirectives();
        $this->wrapperService->registerDirectives();
        $this->yieldService->registerDirectives();

        $this->registerScriptDirective();
        $this->registerResourcesDirective();
        $this->registerStylesDirective();
        $this->registerVueDirective();
        $this->registerRegisterDirective();
        $this->registerViewTypeDirective();
    }

    /**
     * Đăng ký các Blade directive tùy chỉnh
     */
    protected function registerDirectives(): void
    {

        // Directive @await - await directive
        Blade::directive('await', function ($expression) {
            return "";
        });

        // Directive @vars - khai báo và kiểm tra biến
        Blade::directive('vars', function ($expression) {
            return $this->varsService->processVarsDirective($expression);
        });

        // Directive @viewId - tự động sinh UUID cho mỗi view
        Blade::directive('viewId', function ($expression) {
            return '<?php echo $__VIEW_ID__ ?? \Illuminate\Support\Str::uuid(); ?>';
        });


        // Directive @fetch - fetch directive
        Blade::directive('fetch', function ($expression) {
            return "";
        });

        // @attr handled by AttrDirectiveService
        // @checked and @selected are built-in Laravel directives

    }

    protected function registerViewTypeDirective(): void
    {
        Blade::directive('viewType', function ($expression) {
            return "<?php \$__VIEW_TYPE__ = \$__helper->registerViewType({$expression}) ?? (\$__VIEW_TYPE__ ?? 'view'); ?>";
        });
    }


    /**
     * Register Script directive
     */
    protected function registerScriptDirective(): void
    {
        Blade::directive('scripts', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__ . '_script'); ?>";
        });

        Blade::directive('endscripts', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->addScript(\$__VIEW_ID__,\$__env->yieldContent(\$__VIEW_ID__.'_script')); ?>";
        });

        Blade::directive('endScripts', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->addScript(\$__VIEW_ID__,\$__env->yieldContent(\$__VIEW_ID__.'_script')); ?>";
        });
    }

    /**
     * Đăng ký Resources directive
     */
    protected function registerResourcesDirective(): void
    {
        Blade::directive('resources', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__ . '_resources'); ?>";
        });

        Blade::directive('endresources', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->addResources(\$__VIEW_ID__,\$__env->yieldContent(\$__VIEW_ID__.'_resources')); ?>";
        });

        Blade::directive('endResources', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->addResources(\$__VIEW_ID__,\$__env->yieldContent(\$__VIEW_ID__.'_resources')); ?>";
        });
    }

    /**
     * Đăng ký Styles directive
     */
    protected function registerStylesDirective(): void
    {
        Blade::directive('styles', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__ . '_styles'); ?>";
        });

        Blade::directive('endstyles', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->addStyles(\$__VIEW_ID__,\$__env->yieldContent(\$__VIEW_ID__.'_styles')); ?>";
        });

        Blade::directive('endStyles', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->addStyles(\$__VIEW_ID__,\$__env->yieldContent(\$__VIEW_ID__.'_styles')); ?>";
        });
    }

    /**
     * Đăng ký Vue directive
     */
    protected function registerVueDirective(): void
    {
        Blade::directive('vue', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__ . '_vue'); ?>";
        });

        Blade::directive('endvue', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->compileVue(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_vue')); ?>";
        });
    }

    /**
     * Đăng ký Register directive
     */
    protected function registerRegisterDirective(): void
    {
        Blade::directive('register', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        });

        Blade::directive('Register', function ($expression) {
            return "<?php \$__env->startSection(\$__VIEW_ID__.'_register'); ?>";
        });

        Blade::directive('endregister', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });

        Blade::directive('endRegister', function ($expression) {
            return "<?php \$__env->stopSection(); \$__helper->registerResources(\$__VIEW_ID__, \$__env->yieldContent(\$__VIEW_ID__.'_register')); ?>";
        });
    }
}
