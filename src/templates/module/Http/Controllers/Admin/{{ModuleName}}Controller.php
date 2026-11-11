<?php

namespace {{Namespace}}\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
{{ServiceUse}}
use Illuminate\Http\Request;

class {{ModuleName}}Controller extends AdminController
{
{{ServiceConstructor}}
    public function index(Request $request)
    {
        return $this->response(['items' => {{ServiceIndexData}}]);
    }
}
