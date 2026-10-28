<?php

namespace Saola\Core\Http\Controllers;

/**
 * Base of the api context. A project keeps its own App\Http\Controllers\ApiController extending this class (or a
 * package's api controller, which extends this one); module controllers extend the project's class only.
 */
abstract class ApiController extends BaseApiController
{
    protected $context = 'api';
}
