<?php

namespace Saola\Core\Http\Controllers;

/**
 * Base of the admin context. A project keeps its own App\Http\Controllers\AdminController extending this class (or a
 * package's admin controller, which extends this one); module controllers extend the project's class only.
 */
abstract class AdminController extends BaseWebController
{
    protected $context = 'admin';
}
