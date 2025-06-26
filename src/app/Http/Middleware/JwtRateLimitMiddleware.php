<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT 속도 제한 미들웨어
 * 
 * JWT 관련 엔드포인트에 대한 보안 강화를 위한 속도 제한을 적용합니다.
 * 무차별 대입 공격과 토큰 남용을 방지합니다.
 */
class JwtRateLimitMiddleware
{
    /**
     * 요청 처리
     *
     * @param Request $request
     * @param Closure $next
     * @param string $maxAttempts
     * @param string $decayMinutes
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, (int) $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            // 로그인 시도 횟수 초과 로깅
            \Log::warning('JWT rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'endpoint' => $request->path(),
                'retry_after' => $retryAfter,
            ]);

            return $this->buildTooManyAttemptsResponse($retryAfter);
        }

        $response = $next($request);

        // 실패한 인증 시도인 경우 속도 제한 카운터 증가
        if ($this->isFailedAuthAttempt($response)) {
            RateLimiter::hit($key, (int) $decayMinutes * 60);
        } else {
            // 성공한 경우 카운터 리셋
            RateLimiter::clear($key);
        }

        return $response;
    }

    /**
     * 요청 서명 생성
     * 
     * @param Request $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // IP 주소와 엔드포인트 기반으로 키 생성
        $ip = $request->ip();
        $endpoint = $request->path();
        
        return 'jwt_rate_limit:' . sha1($ip . '|' . $endpoint);
    }

    /**
     * 실패한 인증 시도인지 확인
     * 
     * @param Response $response
     * @return bool
     */
    protected function isFailedAuthAttempt(Response $response): bool
    {
        return $response->getStatusCode() === 401;
    }

    /**
     * 속도 제한 초과 응답 생성
     * 
     * @param int $retryAfter
     * @return Response
     */
    protected function buildTooManyAttemptsResponse(int $retryAfter): Response
    {
        return response()->json([
            'success' => false,
            'message' => '너무 많은 요청입니다. 잠시 후 다시 시도해주세요.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', $retryAfter);
    }
}