<?php

namespace Saola\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientSideMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Chỉ xử lý response HTML
        if ($response->headers->get('Content-Type') && 
            str_contains($response->headers->get('Content-Type'), 'text/html')) {
            
            $content = $response->getContent();
            
            // Xóa toàn bộ nội dung bên trong @clientside...@endclientside
            $pattern = '/(@clientside)(.*?)(@endclientside)/s';
            $content = preg_replace($pattern, '', $content);
            
            $response->setContent($content);
        }
        
        return $response;
    }
}

