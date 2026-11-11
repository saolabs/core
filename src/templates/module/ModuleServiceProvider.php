<?php

namespace {{Namespace}};

use Saola\Core\Providers\ModuleServiceProvider as CoreModuleServiceProvider;
use Saola\Core\System;
{{AdminControllerUseStatement}}
{{ApiControllerUseStatement}}
{{WebControllerUseStatement}}

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    public function define(): void
    {
{{AdminRoutesBlock}}

{{ApiRoutesBlock}}

{{WebRoutesBlock}}
    }
}
