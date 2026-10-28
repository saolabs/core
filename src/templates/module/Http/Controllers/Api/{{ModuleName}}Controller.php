<?php

namespace {{Namespace}}\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
{{ServiceUse}}
use Illuminate\Http\Request;

class {{ModuleName}}Controller extends ApiController
{
{{ServiceConstructor}}
    public function index(Request $request)
    {
        return $this->jsonSuccess({{ServiceIndexData}});
    }
}
