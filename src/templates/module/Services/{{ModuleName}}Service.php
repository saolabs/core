<?php

namespace {{Namespace}}\Services;

use {{Namespace}}\Repositories\{{ModuleName}}Repository;
use Saola\Core\Services\ModuleService;

/**
 * Business rules of the {{ModuleName}} module. ModuleService already gives find/create/update/delete,
 * getResults/getDetail and the trash operations through the repository below; add only real rules here.
 */
class {{ModuleName}}Service extends ModuleService
{
    protected $repositoryClass = {{ModuleName}}Repository::class;
}
