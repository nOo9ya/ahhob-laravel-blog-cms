<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * 비밀번호 재확인 미들웨어
 * 
 * 민감한 작업을 수행하기 전에 사용자의 비밀번호를 재확인하는 미들웨어입니다.
 * 다음과 같은 보안 기능을 제공합니다:
 * 
 * - 설정된 시간 내에 비밀번호를 확인했는지 검사
 * - 민감한 액션 수행 전 비밀번호 재확인 요구
 * - 세션 기반 확인 상태 관리
 * - 자동 만료 및 갱신 기능
 * 
 * 사용 예시:
 * Route::middleware(['auth', 'password.confirm'])->group(function () {
 *     Route::post('/profile/delete', [ProfileController::class, 'destroy']);
 *     Route::put('/settings/security', [SecurityController::class, 'update']);
 * });
 */
class RequirePasswordConfirmation
{
    /**
     * 비밀번호 확인이 필요한 액션들
     * 
     * 이 배열에 정의된 액션들은 항상 비밀번호 재확인을 요구합니다.
     * 설정 파일에서 추가로 정의할 수도 있습니다.
     */
    protected array $sensitiveActions = [
        'profile.update',
        'profile.destroy', 
        'password.update',
        'account.delete',
        'security.settings',
        'admin.settings',
        'payment.methods',
        'two-factor.enable',
        'two-factor.disable'
    ];

    /**
     * 미들웨어 핸들러
     * 
     * @param Request $request HTTP 요청 객체
     * @param Closure $next 다음 미들웨어 체인
     * @param string|null $action 특정 액션 지정 (선택사항)
     * @return Response HTTP 응답 객체
     */
    public function handle(Request $request, Closure $next, ?string $action = null): Response
    {
        // 비밀번호 확인 기능이 비활성화된 경우 패스
        if (!config('security.password_policy.confirmation.enabled', true)) {
            return $next($request);
        }

        // 사용자가 로그인되어 있지 않으면 인증 미들웨어에서 처리
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // 1. 현재 액션이 비밀번호 확인이 필요한지 판단
        if (!$this->requiresPasswordConfirmation($request, $action)) {
            return $next($request);
        }

        // 2. 최근에 비밀번호를 확인했는지 검사
        if ($this->hasRecentPasswordConfirmation($request)) {
            // 확인 시간 갱신
            $this->updateLastConfirmationTime($request);
            return $next($request);
        }

        // 3. AJAX 요청인 경우 JSON 응답
        if ($request->expectsJson()) {
            return response()->json([
                'message' => '이 작업을 수행하려면 비밀번호 확인이 필요합니다.',
                'password_confirmation_required' => true,
                'redirect_url' => route('password.confirm', [
                    'redirect' => $request->fullUrl()
                ])
            ], 423); // 423 Locked
        }

        // 4. 비밀번호 확인 페이지로 리다이렉트
        return redirect()->route('password.confirm', [
            'redirect' => $request->fullUrl()
        ])->with('message', '보안을 위해 비밀번호를 다시 입력해주세요.');
    }

    /**
     * 현재 요청이 비밀번호 확인을 필요로 하는지 판단
     * 
     * @param Request $request HTTP 요청 객체
     * @param string|null $action 특정 액션
     * @return bool 비밀번호 확인 필요 여부
     */
    protected function requiresPasswordConfirmation(Request $request, ?string $action = null): bool
    {
        // 1. 명시적으로 액션이 지정된 경우
        if ($action && $this->isActionSensitive($action)) {
            return true;
        }

        // 2. 라우트 이름 기반 검사
        $routeName = $request->route()?->getName();
        if ($routeName && $this->isActionSensitive($routeName)) {
            return true;
        }

        // 3. URL 패턴 기반 검사
        return $this->isUrlPatternSensitive($request->path());
    }

    /**
     * 특정 액션이 민감한 액션인지 확인
     * 
     * @param string $action 액션 이름
     * @return bool 민감한 액션 여부
     */
    protected function isActionSensitive(string $action): bool
    {
        // 설정에서 정의된 민감한 액션들
        $configuredActions = config('security.password_policy.confirmation.required_for', []);
        $allSensitiveActions = array_merge($this->sensitiveActions, $configuredActions);

        // 정확한 매치
        if (in_array($action, $allSensitiveActions)) {
            return true;
        }

        // 패턴 매치 (와일드카드 지원)
        foreach ($allSensitiveActions as $sensitiveAction) {
            if (str_contains($sensitiveAction, '*')) {
                $pattern = str_replace('*', '.*', $sensitiveAction);
                if (preg_match('/^' . $pattern . '$/', $action)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * URL 패턴이 민감한 작업인지 확인
     * 
     * @param string $path 요청 경로
     * @return bool 민감한 경로 여부
     */
    protected function isUrlPatternSensitive(string $path): bool
    {
        $sensitivePatterns = [
            'admin/settings',
            'profile/delete',
            'account/delete',
            'password/change',
            'security/.*',
            'payment/.*',
            'billing/.*'
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match('/^' . str_replace('*', '.*', $pattern) . '/', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 최근에 비밀번호를 확인했는지 검사
     * 
     * @param Request $request HTTP 요청 객체
     * @return bool 최근 확인 여부
     */
    protected function hasRecentPasswordConfirmation(Request $request): bool
    {
        $lastConfirmation = $request->session()->get('password_confirmed_at');
        
        if (!$lastConfirmation) {
            return false;
        }

        $timeout = config('security.password_policy.confirmation.timeout', 10800); // 기본 3시간
        $expiryTime = $lastConfirmation + $timeout;

        return time() < $expiryTime;
    }

    /**
     * 비밀번호 확인 시간 업데이트
     * 
     * @param Request $request HTTP 요청 객체
     * @return void
     */
    protected function updateLastConfirmationTime(Request $request): void
    {
        $request->session()->put('password_confirmed_at', time());
    }

    /**
     * 비밀번호 확인 처리 (다른 컨트롤러에서 사용)
     * 
     * 이 메서드는 비밀번호 확인 컨트롤러에서 실제 비밀번호 검증 후 호출됩니다.
     * 
     * @param Request $request HTTP 요청 객체
     * @param string $password 입력된 비밀번호
     * @return bool 비밀번호 확인 성공 여부
     */
    public static function confirmPassword(Request $request, string $password): bool
    {
        $user = Auth::user();

        if (!$user || !Hash::check($password, $user->password)) {
            return false;
        }

        // 확인 성공 시 세션에 저장
        $request->session()->put('password_confirmed_at', time());

        // 보안 이벤트 로깅
        \Log::info('Password confirmation successful', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        return true;
    }

    /**
     * 비밀번호 확인 상태 무효화
     * 
     * 민감한 작업 완료 후나 보안상 필요할 때 확인 상태를 초기화합니다.
     * 
     * @param Request $request HTTP 요청 객체
     * @return void
     */
    public static function invalidateConfirmation(Request $request): void
    {
        $request->session()->forget('password_confirmed_at');
    }

    /**
     * 비밀번호 확인 상태 확인 (헬퍼 메서드)
     * 
     * @param Request $request HTTP 요청 객체
     * @return bool 현재 확인 상태
     */
    public static function isPasswordConfirmed(Request $request): bool
    {
        $lastConfirmation = $request->session()->get('password_confirmed_at');
        
        if (!$lastConfirmation) {
            return false;
        }

        $timeout = config('security.password_policy.confirmation.timeout', 10800);
        return time() < ($lastConfirmation + $timeout);
    }

    /**
     * 남은 확인 시간 반환 (초 단위)
     * 
     * @param Request $request HTTP 요청 객체
     * @return int 남은 시간 (초), 확인되지 않았으면 0
     */
    public static function getTimeUntilExpiry(Request $request): int
    {
        $lastConfirmation = $request->session()->get('password_confirmed_at');
        
        if (!$lastConfirmation) {
            return 0;
        }

        $timeout = config('security.password_policy.confirmation.timeout', 10800);
        $expiryTime = $lastConfirmation + $timeout;
        
        return max(0, $expiryTime - time());
    }

    /**
     * 비밀번호 확인 정보 반환 (API 응답용)
     * 
     * @param Request $request HTTP 요청 객체
     * @return array 확인 상태 정보
     */
    public static function getConfirmationStatus(Request $request): array
    {
        $isConfirmed = static::isPasswordConfirmed($request);
        $timeUntilExpiry = static::getTimeUntilExpiry($request);

        return [
            'is_confirmed' => $isConfirmed,
            'time_until_expiry' => $timeUntilExpiry,
            'expiry_timestamp' => $isConfirmed ? time() + $timeUntilExpiry : null,
            'timeout_minutes' => config('security.password_policy.confirmation.timeout', 10800) / 60
        ];
    }
}