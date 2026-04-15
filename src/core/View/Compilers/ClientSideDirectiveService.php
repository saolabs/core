<?php

namespace Saola\Core\View\Compilers;

use Illuminate\Support\Facades\Blade;

class ClientSideDirectiveService
{
    public function registerDirectives(): void {
        // Directive @clientside - kiểm tra biến $_______show_client_side______
        Blade::directive('clientside', function ($expression) {
            return $this->processClientSideDirective($expression);
        });
        Blade::directive('Clientside', function ($expression) {
            return $this->processClientSideDirective($expression);
        });
        Blade::directive('clientSide', function ($expression) {
            return $this->processClientSideDirective($expression);
        });
        Blade::directive('ClientSide', function ($expression) {
            return $this->processClientSideDirective($expression);
        });
        Blade::directive('CSR', function ($expression) {
            return $this->processClientSideDirective($expression);
        });
        Blade::directive('Csr', function ($expression) {
            return $this->processClientSideDirective($expression);
        });
        Blade::directive('csr', function ($expression) {
            return $this->processClientSideDirective($expression);
        });

        Blade::directive('endclientside', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('Endclientside', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('Endclientside', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('Endclientside', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('endCSR', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('endCsr', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('endcsr', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('EndCSR', function () {
            return "<?php endif; ?>";
        });
        Blade::directive('EndCsr', function () {
            return "<?php endif; ?>";
        });
    }
    /**
     * Process @clientside directive
     */
    public function processClientSideDirective($expression)
    {
        return "<?php if(isset(\$_______show_client_side______) && \$_______show_client_side______): ?>";
    }

    /**
     * Process @endclientside directive
     */
    public function processEndClientSideDirective($expression)
    {
        return "<?php endif; ?>";
    }

    /**
     * Process @serverside directive
     */
    public function processServerSideDirective($expression)
    {
        return "<?php if(!isset(\$_______show_client_side______) || !\$_______show_client_side______): ?>";
    }

    /**
     * Process @endserverside directive
     */
    public function processEndServerSideDirective($expression)
    {
        return "<?php endif; ?>";
    }
}

