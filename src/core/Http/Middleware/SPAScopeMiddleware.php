<?php

namespace Saola\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SPAScopeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $scope = 'web'): Response
    {
        // Set SPA scope in request
        $request->attributes->set('spa_scope', $scope);
        
        // Add scope to view data
        view()->share('spa_scope', $scope);
        
        // Add scope to response headers
        $response = $next($request);
        $response->headers->set('X-SPA-Scope', $scope);
        
        return $response;
    }
}
