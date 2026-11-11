<?php

namespace {{Namespace}}\Repositories;

use {{Namespace}}\Models\{{ModelName}};
use Saola\Core\Database\Repositories\BaseRepository;

/** find/get/first/create/update/delete, filters, search and cache come from the core BaseRepository. */
class {{ModuleName}}Repository extends BaseRepository
{
    protected $model = {{ModelName}}::class;

    protected $searchable = ['name'];
}
