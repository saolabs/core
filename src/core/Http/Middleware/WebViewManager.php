<?php

namespace Saola\Core\Http\Middleware;

use Saola\Core\View\Services\ViewHelperService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
class WebViewManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $__helper = app(ViewHelperService::class);
        $__helper->reset();
        View::share('__helper', $__helper);

        return $next($request);
    }
}
