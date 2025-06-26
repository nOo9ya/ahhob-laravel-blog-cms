<?php

namespace App\Http\Middleware;

use App\Services\Ahhob\Blog\Shared\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseInterface;

/**
 * JWT 인증 미들웨어
 * 
 * JWT 토큰의 유효성을 검증하고 사용자 인증을 처리합니다.
 * 추가적인 보안 검사와 로깅 기능을 제공합니다.
 */
class JwtAuthMiddleware
{
    public function __construct(
        private JwtService $jwtService
    ) {}

    /**
     * 요청 처리
     *
     * @param Request $request
     * @param Closure $next
     * @return ResponseInterface
     */
    public function handle(Request $request, Closure $next): ResponseInterface
    {
        try {
            $token = $this->extractToken($request);

            if (!$token) {
                return $this->unauthorizedResponse('토큰이 제공되지 않았습니다.');
            }

            // 토큰 유효성 검증
            if (!$this->jwtService->validateToken($token)) {
                return $this->unauthorizedResponse('유효하지 않은 토큰입니다.');
            }

            // 사용자 정보 조회 및 설정
            $user = $this->jwtService->getUserFromToken($token);
            
            if (!$user) {
                return $this->unauthorizedResponse('사용자를 찾을 수 없습니다.');
            }

            // 계정 활성화 상태 확인
            if (!$user->is_active) {
                return $this->unauthorizedResponse('비활성화된 계정입니다.');
            }

            // 요청에 사용자 정보 설정
            auth()->setUser($user);
            $request->setUserResolver(fn() => $user);

            // 토큰 만료 시간 확인 및 갱신 권장
            $this->checkTokenExpiration($request, $token);

            return $next($request);

        } catch (\Exception $e) {
            \Log::warning('JWT authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->unauthorizedResponse($e->getMessage());
        }
    }

    /**
     * 요청에서 토큰 추출
     * 
     * @param Request $request
     * @return string|null
     */
    private function extractToken(Request $request): ?string
    {
        // Authorization 헤더에서 Bearer 토큰 추출
        $authHeader = $request->header('Authorization');
        
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // 쿼리 파라미터에서 토큰 추출 (웹소켓 등에서 사용)
        return $request->query('token');
    }

    /**
     * 토큰 만료 시간 확인
     * 
     * @param Request $request
     * @param string $token
     * @return void
     */
    private function checkTokenExpiration(Request $request, string $token): void
    {
        try {
            $expiration = $this->jwtService->getTokenExpiration($token);
            $now = now();
            $minutesUntilExpiration = $now->diffInMinutes($expiration);

            // 10분 이내 만료 시 응답 헤더에 갱신 권장 추가
            if ($minutesUntilExpiration <= 10) {
                $request->attributes->set('jwt_refresh_recommended', true);
            }

            // 응답 헤더에 만료 시간 추가
            $request->attributes->set('jwt_expires_at', $expiration->toISOString());

        } catch (\Exception $e) {
            // 만료 시간 확인 실패는 로그만 남기고 계속 진행
            \Log::debug('Failed to check token expiration', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 인증 실패 응답
     * 
     * @param string $message
     * @return Response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
        ], 401);
    }
}