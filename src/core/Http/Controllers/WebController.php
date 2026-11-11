<?php

namespace Saola\Core\Http\Controllers;

/**
 * Base of the web context. A project keeps its own App\Http\Controllers\WebController extending this class (or a
 * package's web controller, which extends this one); module controllers extend the project's class only.
 */
abstract class WebController extends BaseWebController
{
    protected $context = 'web';
}
