<?php

namespace Saola\Core\Http\Controllers;

use Saola\Core\Http\Controllers\Controller;
use Saola\Core\Http\Controllers\Support\WebResponse;
use Saola\Core\Support\Methods\CacheMethods;
use Saola\Core\Support\Methods\ResponseMethods;
use Saola\Core\Support\Methods\ViewMethods;

abstract class BaseWebController extends Controller
{
    use ViewMethods, ResponseMethods, WebResponse, CacheMethods;

    public function moduleKey($action = null){
        return $this->context . '.' . $this->module . ($action ? '.' . $action : '');
    }
}