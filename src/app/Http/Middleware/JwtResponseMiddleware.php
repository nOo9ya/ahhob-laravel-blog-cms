<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT 응답 미들웨어
 * 
 * JWT 관련 정보를 응답 헤더에 추가합니다.
 * 토큰 만료 시간, 갱신 권장 등의 정보를 클라이언트에 제공합니다.
 */
class JwtResponseMiddleware
{
    /**
     * 요청 처리
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // JWT 인증을 거친 요청인 경우에만 헤더 추가
        if (auth()->check()) {
            // 토큰 만료 시간 헤더 추가
            if ($request->attributes->has('jwt_expires_at')) {
                $response->headers->set('X-JWT-Expires-At', $request->attributes->get('jwt_expires_at'));
            }

            // 토큰 갱신 권장 헤더 추가
            if ($request->attributes->get('jwt_refresh_recommended')) {
                $response->headers->set('X-JWT-Refresh-Recommended', 'true');
            }

            // 사용자 정보 헤더 추가 (옵션)
            if (config('jwt.include_user_headers', false)) {
                $user = auth()->user();
                $response->headers->set('X-User-ID', $user->id);
                $response->headers->set('X-User-Role', $user->role);
            }

            // CORS 허용 헤더에 JWT 관련 헤더 추가
            $response->headers->set('Access-Control-Expose-Headers', 
                'X-JWT-Expires-At, X-JWT-Refresh-Recommended, X-User-ID, X-User-Role'
            );
        }

        return $response;
    }
}