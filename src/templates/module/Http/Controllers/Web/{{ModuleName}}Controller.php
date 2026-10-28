<?php

namespace {{Namespace}}\Http\Controllers\Web;

use App\Http\Controllers\WebController;
{{ServiceUse}}
use Illuminate\Http\Request;

class {{ModuleName}}Controller extends WebController
{
{{ServiceConstructor}}
    public function index(Request $request)
    {
        return $this->response(['items' => {{ServiceIndexData}}]);
    }
}
