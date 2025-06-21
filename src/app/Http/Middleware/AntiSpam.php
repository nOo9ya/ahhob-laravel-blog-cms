<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AntiSpam
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        // IP당 분당 3개 댓글 제한
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '너무 많은 댓글을 작성했습니다. ' . $seconds . '초 후 다시 시도해주세요.'
                ], 429);
            }

            return back()->withErrors([
                'comment' => '너무 많은 댓글을 작성했습니다. ' . $seconds . '초 후 다시 시도해주세요.'
            ]);
        }

        $response = $next($request);

        // 성공적인 댓글 작성 시 제한 카운터 증가
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            RateLimiter::hit($key, 60); // 1분간 유효
        }

        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1($request->ip() . '|' . $request->userAgent());
    }
}
