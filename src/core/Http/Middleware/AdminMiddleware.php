<?php

namespace Saola\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra user đã đăng nhập chưa
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'api_version' => 'v1',
                    'timestamp' => now()->toISOString(),
                ], 401);
            }

            return redirect()->route('auth.login')->with('error', 'Vui lòng đăng nhập để truy cập trang này');
        }

        // Kiểm tra user có role admin không
        // if (!Auth::user()->hasRole('admin')) {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Forbidden - Admin access required',
        //             'api_version' => 'v1',
        //             'timestamp' => now()->toISOString(),
        //         ], 403);
        //     }

        //     abort(403, 'Bạn không có quyền truy cập trang này');
        // }

        return $next($request);
    }
} 