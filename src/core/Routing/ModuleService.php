<?php

namespace Saola\Core\Routing;

use Saola\Core\Services\Service;
use Saola\Core\Support\Methods\CacheMethods;
use Saola\Core\Support\Methods\ModuleMethods;
use Saola\Core\Support\Methods\CRUDMethods;

/**
 * đây là service cho từng context
 * 
 */
abstract class ModuleService extends Service
{
    use ModuleMethods, CRUDMethods, CacheMethods;
    protected $context = null;
    protected $module = null;
    public function __construct() {
        $this->init();
    }

} 