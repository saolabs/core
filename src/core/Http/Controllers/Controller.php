<?php

namespace Saola\Core\Http\Controllers;

use Saola\Core\Events\EventMethods;
use Saola\Core\Support\Methods\SmartInit;
abstract class Controller
{
    use EventMethods, SmartInit;
    protected $service = null;
    public function __construct()
    {
        $this->init();
    }


    
}