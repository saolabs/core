<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class ServerSideDirectiveService
{
    public function registerDirectives(): void {
        // Directive @serverside - kiểm tra biến $_______show_server_side______
        Blade::directive('serverside', function ($expression) {
            return "<?php if (true): // @serverside ?>";
        });
        Blade::directive('Serverside', function ($expression) {
            return "<?php if (true): // @serverside ?>";
        });
        Blade::directive('serverSide', function ($expression) {
            return "<?php if (true): // @serverside ?>";
        });
        Blade::directive('ServerSide', function ($expression) {
            return "<?php if (true): // @serverside ?>";
        });
        Blade::directive('SSR', function ($expression) {
            return "<?php if (true): // @serverside ?>";
        });
        Blade::directive('Ssr', function ($expression) {
            return "<?php if (true): // @serverside ?>";
        });
        Blade::directive('ssr', function ($expression) {
            return "<?php if (true): // @serverside ?>";
        });

        Blade::directive('endserverside', function () {
            return "<?php endif; // @endserverside ?>";
        });
        Blade::directive('endServerside', function () {
            return "<?php endif; // @endserverside ?>";
        });
        Blade::directive('endServerSide', function () {
            return "<?php endif; // @endserverside ?>";
        });
        Blade::directive('EndSSR', function () {
            return "<?php endif; // @endserverside ?>";
        });
        Blade::directive('EndSsr', function () {
            return "<?php endif; // @endserverside ?>";
        });
        Blade::directive('endSSR', function () {
            return "<?php endif; // @endserverside ?>";
        });
        Blade::directive('endSsr', function () {
            return "<?php endif; // @endserverside ?>";
        });
        Blade::directive('endssr', function () {
            return "<?php endif; // @endserverside ?>";
        });
    }
}
