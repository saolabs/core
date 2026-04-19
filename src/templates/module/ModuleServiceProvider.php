<?php

namespace {{Namespace}};

use Saola\Core\Providers\ModuleServiceProvider as CoreModuleServiceProvider;
use Saola\Core\Routing\System;
{{AdminControllerUseStatement}}
{{ApiControllerUseStatement}}
{{WebControllerUseStatement}}
{{ServiceUseStatement}}

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    public function register(): void
    {
{{RegisterBindings}}
    }

    // public function boot(): void
    // {
    //     parent::boot();
    //     // Các logic boot khác nếu cần
    // }

    public function routes(): void
    {
{{AdminRoutesBlock}}

{{ApiRoutesBlock}}

{{WebRoutesBlock}}
    }
}
