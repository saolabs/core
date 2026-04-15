<?php

namespace Saola\Core\Http\Controllers;

use Saola\Core\Http\Controllers\Controller;
use Saola\Core\Http\Controllers\Support\ApiResponse;
use Saola\Core\Support\Methods\CacheMethods;

abstract class BaseApiController extends Controller
{
    use ApiResponse, CacheMethods;

}