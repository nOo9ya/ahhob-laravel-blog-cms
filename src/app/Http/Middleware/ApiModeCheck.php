<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiModeCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = config('ahhob_blog.mode');

        // API 모드가 아닌 경우 API 접근 차단
        if ($mode !== 'api' && $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'API mode is disabled'
            ], 403);
        }

        // 웹 모드가 아닌 경우 웹 접근 차단 (선택사항)
        if ($mode === 'api' && !$request->is('api/*') && !$request->is('admin/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Web mode is disabled'
            ], 403);
        }

        return $next($request);
    }
}
