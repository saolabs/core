<?php

namespace Saola\Core\Services;

use Saola\Core\Support\Methods\ModuleMethods;
use Saola\Core\Support\Methods\CRUDMethods;
use Saola\Core\Support\Methods\CacheMethods;


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