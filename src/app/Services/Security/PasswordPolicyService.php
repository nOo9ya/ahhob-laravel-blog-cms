<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

/**
 * 비밀번호 보안 정책 서비스
 * 
 * 이 서비스는 강력한 비밀번호 정책을 시행하여 계정 보안을 강화합니다:
 * - 비밀번호 복잡도 요구사항 검증
 * - 비밀번호 히스토리 관리 (이전 비밀번호 재사용 방지)
 * - 비밀번호 만료 정책 관리
 * - 계정 잠금 정책 시행
 * - 비밀번호 강도 평가 및 권장사항 제공
 * 
 * 모든 정책은 config/security.php에서 설정 가능합니다.
 */
class PasswordPolicyService
{
    /**
     * 비밀번호 강도 레벨 상수
     */
    const STRENGTH_VERY_WEAK = 1;
    const STRENGTH_WEAK = 2;
    const STRENGTH_FAIR = 3;
    const STRENGTH_GOOD = 4;
    const STRENGTH_STRONG = 5;

    /**
     * 일반적으로 사용되는 약한 비밀번호 목록
     * 
     * 실제 서비스에서는 더 포괄적인 목록을 사용하거나
     * 외부 데이터베이스를 참조할 수 있습니다.
     */
    protected array $commonPasswords = [
        'password', '123456', '123456789', 'qwerty', 'abc123',
        'password123', 'admin', 'letmein', 'welcome', 'monkey',
        '1234567890', 'iloveyou', 'princess', 'rockyou', '12345',
        '123123', 'dragon', 'passw0rd', 'master', 'hello',
        'freedom', 'sunshine', 'football', 'starwars', 'computer'
    ];

    /**
     * 비밀번호 복잡도 검증
     * 
     * 설정된 복잡도 요구사항에 따라 비밀번호를 검증합니다.
     * 
     * @param string $password 검증할 비밀번호
     * @param User|null $user 사용자 객체 (개인화된 검증용)
     * @return array 검증 결과 ['valid' => bool, 'errors' => array, 'score' => int]
     */
    public function validatePassword(string $password, ?User $user = null): array
    {
        $errors = [];
        $config = config('security.password_policy.complexity', []);

        // 1. 길이 검증
        $minLength = $config['min_length'] ?? 8;
        $maxLength = $config['max_length'] ?? 128;
        
        if (strlen($password) < $minLength) {
            $errors[] = "비밀번호는 최소 {$minLength}자 이상이어야 합니다.";
        }
        
        if (strlen($password) > $maxLength) {
            $errors[] = "비밀번호는 최대 {$maxLength}자 이하여야 합니다.";
        }

        // 2. 문자 종류 요구사항 검증
        if ($config['require_uppercase'] ?? true) {
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = '비밀번호에 대문자가 포함되어야 합니다.';
            }
        }

        if ($config['require_lowercase'] ?? true) {
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = '비밀번호에 소문자가 포함되어야 합니다.';
            }
        }

        if ($config['require_numbers'] ?? true) {
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = '비밀번호에 숫자가 포함되어야 합니다.';
            }
        }

        if ($config['require_symbols'] ?? true) {
            $allowedSymbols = $config['allowed_symbols'] ?? '!@#$%^&*()_+-=[]{}|;:,.<>?';
            $symbolPattern = '/[' . preg_quote($allowedSymbols, '/') . ']/';
            
            if (!preg_match($symbolPattern, $password)) {
                $errors[] = '비밀번호에 특수문자가 포함되어야 합니다. (' . $allowedSymbols . ')';
            }
        }

        // 3. 개인 정보 사용 검증 (사용자 정보가 제공된 경우)
        if ($user) {
            $personalInfoErrors = $this->checkPersonalInfoUsage($password, $user);
            $errors = array_merge($errors, $personalInfoErrors);
        }

        // 4. 일반적인 약한 비밀번호 검증
        if ($this->isCommonPassword($password)) {
            $errors[] = '너무 일반적인 비밀번호입니다. 더 복잡한 비밀번호를 선택해주세요.';
        }

        // 5. 패턴 검증 (연속된 문자, 반복 문자 등)
        $patternErrors = $this->checkPasswordPatterns($password);
        $errors = array_merge($errors, $patternErrors);

        // 6. 비밀번호 강도 점수 계산
        $strengthScore = $this->calculatePasswordStrength($password);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength_score' => $strengthScore,
            'strength_level' => $this->getStrengthLevel($strengthScore),
            'suggestions' => $this->getPasswordSuggestions($password, $errors)
        ];
    }

    /**
     * 개인 정보 사용 검증
     * 
     * 비밀번호에 사용자의 개인 정보(이름, 이메일 등)가 포함되었는지 확인합니다.
     * 
     * @param string $password 검증할 비밀번호
     * @param User $user 사용자 객체
     * @return array 오류 메시지 배열
     */
    protected function checkPersonalInfoUsage(string $password, User $user): array
    {
        $errors = [];
        $passwordLower = strtolower($password);

        // 사용자명 포함 여부 확인
        if ($user->name && strlen($user->name) >= 3) {
            $nameLower = strtolower($user->name);
            if (strpos($passwordLower, $nameLower) !== false) {
                $errors[] = '비밀번호에 사용자명을 포함할 수 없습니다.';
            }
        }

        // 이메일 주소 포함 여부 확인
        if ($user->email) {
            $emailParts = explode('@', strtolower($user->email));
            $emailUser = $emailParts[0];
            
            if (strlen($emailUser) >= 3 && strpos($passwordLower, $emailUser) !== false) {
                $errors[] = '비밀번호에 이메일 주소의 일부를 포함할 수 없습니다.';
            }
        }

        // 생년월일 포함 여부 확인 (profile 정보가 있는 경우)
        if ($user->profile && $user->profile->birth_date) {
            $birthYear = $user->profile->birth_date->format('Y');
            if (strpos($password, $birthYear) !== false) {
                $errors[] = '비밀번호에 생년월일을 포함할 수 없습니다.';
            }
        }

        return $errors;
    }

    /**
     * 일반적인 약한 비밀번호 확인
     * 
     * @param string $password 확인할 비밀번호
     * @return bool 일반적인 비밀번호 여부
     */
    protected function isCommonPassword(string $password): bool
    {
        $passwordLower = strtolower($password);
        
        // 정확히 일치하는 경우
        if (in_array($passwordLower, $this->commonPasswords)) {
            return true;
        }

        // 숫자나 특수문자가 약간 추가된 경우도 감지
        foreach ($this->commonPasswords as $commonPassword) {
            $variations = [
                $commonPassword . '123',
                $commonPassword . '!',
                $commonPassword . '1',
                '123' . $commonPassword,
                $commonPassword . '2024'
            ];

            foreach ($variations as $variation) {
                if ($passwordLower === $variation) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 비밀번호 패턴 검증
     * 
     * 연속된 문자, 반복 문자, 키보드 패턴 등을 검사합니다.
     * 
     * @param string $password 검증할 비밀번호
     * @return array 패턴 관련 오류 메시지
     */
    protected function checkPasswordPatterns(string $password): array
    {
        $errors = [];

        // 1. 연속된 동일 문자 검증 (3개 이상)
        if (preg_match('/(.)\1{2,}/', $password)) {
            $errors[] = '같은 문자가 3번 이상 연속으로 사용될 수 없습니다.';
        }

        // 2. 연속된 숫자 패턴 검증
        if (preg_match('/(?:012|123|234|345|456|567|678|789|890|987|876|765|654|543|432|321|210)/', $password)) {
            $errors[] = '연속된 숫자 패턴을 사용할 수 없습니다.';
        }

        // 3. 키보드 패턴 검증
        $keyboardPatterns = ['qwerty', 'asdfgh', 'zxcvbn', '123456', 'qwertyuiop'];
        foreach ($keyboardPatterns as $pattern) {
            if (stripos($password, $pattern) !== false) {
                $errors[] = '키보드 패턴을 사용할 수 없습니다.';
                break;
            }
        }

        // 4. 단순한 패턴 검증 (abc, 123 등)
        if (preg_match('/abc|123|xyz|789/', strtolower($password))) {
            $errors[] = '단순한 문자/숫자 패턴을 사용할 수 없습니다.';
        }

        return $errors;
    }

    /**
     * 비밀번호 강도 점수 계산
     * 
     * 다양한 기준을 바탕으로 비밀번호의 강도를 0-100점으로 계산합니다.
     * 
     * @param string $password 평가할 비밀번호
     * @return int 강도 점수 (0-100)
     */
    protected function calculatePasswordStrength(string $password): int
    {
        $score = 0;
        $length = strlen($password);

        // 1. 길이 점수 (최대 30점)
        if ($length >= 8) $score += 5;
        if ($length >= 12) $score += 10;
        if ($length >= 16) $score += 15;

        // 2. 문자 종류 다양성 점수 (최대 40점)
        if (preg_match('/[a-z]/', $password)) $score += 5;  // 소문자
        if (preg_match('/[A-Z]/', $password)) $score += 5;  // 대문자
        if (preg_match('/[0-9]/', $password)) $score += 5;  // 숫자
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 10; // 특수문자

        // 3. 복잡성 점수 (최대 20점)
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= 5) $score += 5;
        if ($uniqueChars >= 8) $score += 5;
        if ($uniqueChars >= 12) $score += 10;

        // 4. 예측 불가능성 점수 (최대 10점)
        if (!$this->isCommonPassword($password)) $score += 5;
        if (!$this->hasObviousPatterns($password)) $score += 5;

        // 5. 보너스 점수 (혼합된 케이스, 문자/숫자 혼합)
        if (preg_match('/[a-z].*[A-Z]|[A-Z].*[a-z]/', $password)) $score += 5;
        if (preg_match('/[a-zA-Z].*[0-9]|[0-9].*[a-zA-Z]/', $password)) $score += 5;

        return min(100, $score);
    }

    /**
     * 명백한 패턴 존재 여부 확인
     * 
     * @param string $password 확인할 비밀번호
     * @return bool 명백한 패턴 존재 여부
     */
    protected function hasObviousPatterns(string $password): bool
    {
        // 연속된 동일 문자
        if (preg_match('/(.)\1{2,}/', $password)) {
            return true;
        }

        // 간단한 반복 패턴
        if (preg_match('/^(.+)\1+$/', $password)) {
            return true;
        }

        // 키보드 패턴
        $patterns = ['qwerty', 'asdf', 'zxcv', '1234', 'abcd'];
        foreach ($patterns as $pattern) {
            if (stripos($password, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 강도 점수를 레벨로 변환
     * 
     * @param int $score 강도 점수
     * @return int 강도 레벨
     */
    protected function getStrengthLevel(int $score): int
    {
        if ($score < 20) return self::STRENGTH_VERY_WEAK;
        if ($score < 40) return self::STRENGTH_WEAK;
        if ($score < 60) return self::STRENGTH_FAIR;
        if ($score < 80) return self::STRENGTH_GOOD;
        return self::STRENGTH_STRONG;
    }

    /**
     * 비밀번호 개선 제안
     * 
     * 현재 비밀번호의 문제점을 분석하여 개선 방안을 제안합니다.
     * 
     * @param string $password 현재 비밀번호
     * @param array $errors 발견된 오류들
     * @return array 개선 제안 목록
     */
    protected function getPasswordSuggestions(string $password, array $errors): array
    {
        $suggestions = [];
        $length = strlen($password);

        // 길이 관련 제안
        if ($length < 12) {
            $suggestions[] = '비밀번호를 12자 이상으로 늘려보세요.';
        }

        // 문자 종류 다양성 제안
        if (!preg_match('/[A-Z]/', $password)) {
            $suggestions[] = '대문자를 추가해보세요.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $suggestions[] = '숫자를 추가해보세요.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $suggestions[] = '특수문자(!@#$%^&* 등)를 추가해보세요.';
        }

        // 일반적인 제안
        if ($this->isCommonPassword($password)) {
            $suggestions[] = '좀 더 독창적인 비밀번호를 선택해보세요.';
        }

        if (empty($suggestions) && $this->calculatePasswordStrength($password) < 80) {
            $suggestions[] = '단어, 숫자, 특수문자를 조합하여 더 복잡하게 만들어보세요.';
        }

        return $suggestions;
    }

    /**
     * 비밀번호 히스토리 검증
     * 
     * 새 비밀번호가 이전에 사용된 비밀번호와 같은지 확인합니다.
     * 
     * @param User $user 사용자 객체
     * @param string $newPassword 새 비밀번호
     * @return bool 이전 비밀번호 사용 여부
     */
    public function isPasswordReused(User $user, string $newPassword): bool
    {
        if (!config('security.password_policy.history.enabled', true)) {
            return false;
        }

        $historyCount = config('security.password_policy.history.remember_count', 5);

        // 현재 비밀번호와 비교
        if (Hash::check($newPassword, $user->password)) {
            return true;
        }

        // 비밀번호 히스토리와 비교
        $passwordHistories = PasswordHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($historyCount)
            ->get();

        foreach ($passwordHistories as $history) {
            if (Hash::check($newPassword, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 비밀번호 히스토리 저장
     * 
     * 새 비밀번호를 히스토리에 저장하고 오래된 기록을 정리합니다.
     * 
     * @param User $user 사용자 객체
     * @param string $oldPassword 이전 비밀번호 (해시된 값)
     * @return void
     */
    public function savePasswordHistory(User $user, string $oldPassword): void
    {
        if (!config('security.password_policy.history.enabled', true)) {
            return;
        }

        // 새 히스토리 레코드 생성
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => $oldPassword,
            'created_at' => now()
        ]);

        // 오래된 히스토리 정리
        $this->cleanupPasswordHistory($user);
    }

    /**
     * 오래된 비밀번호 히스토리 정리
     * 
     * @param User $user 사용자 객체
     * @return void
     */
    protected function cleanupPasswordHistory(User $user): void
    {
        $historyCount = config('security.password_policy.history.remember_count', 5);

        $oldHistories = PasswordHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->skip($historyCount)
            ->get();

        foreach ($oldHistories as $history) {
            $history->delete();
        }
    }

    /**
     * 비밀번호 만료 여부 확인
     * 
     * @param User $user 사용자 객체
     * @return array 만료 정보 ['expired' => bool, 'days_until_expiry' => int, 'warning' => bool]
     */
    public function checkPasswordExpiration(User $user): array
    {
        if (!config('security.password_policy.expiration.enabled', false)) {
            return ['expired' => false, 'days_until_expiry' => null, 'warning' => false];
        }

        $expirationDays = config('security.password_policy.expiration.days', 90);
        $warningDays = config('security.password_policy.expiration.warning_days', 7);

        $passwordChangedAt = $user->password_changed_at ?? $user->created_at;
        $expiryDate = $passwordChangedAt->addDays($expirationDays);
        $now = now();

        $daysUntilExpiry = $now->diffInDays($expiryDate, false);

        return [
            'expired' => $daysUntilExpiry < 0,
            'days_until_expiry' => max(0, $daysUntilExpiry),
            'warning' => $daysUntilExpiry <= $warningDays && $daysUntilExpiry >= 0
        ];
    }

    /**
     * Laravel 비밀번호 검증 규칙 생성
     * 
     * Laravel의 비밀번호 검증 규칙을 설정에 따라 동적으로 생성합니다.
     * 
     * @return Password Laravel 비밀번호 규칙 객체
     */
    public function getPasswordRules(): Password
    {
        $config = config('security.password_policy.complexity', []);

        $rules = Password::min($config['min_length'] ?? 8);

        if ($config['require_uppercase'] ?? true) {
            $rules->mixedCase();
        }

        if ($config['require_numbers'] ?? true) {
            $rules->numbers();
        }

        if ($config['require_symbols'] ?? true) {
            $rules->symbols();
        }

        // 일반적인 비밀번호 차단
        $rules->uncompromised();

        return $rules;
    }

    /**
     * 비밀번호 정책 정보 반환
     * 
     * 프론트엔드에서 사용할 수 있는 비밀번호 정책 정보를 반환합니다.
     * 
     * @return array 비밀번호 정책 정보
     */
    public function getPasswordPolicyInfo(): array
    {
        $config = config('security.password_policy.complexity', []);

        return [
            'min_length' => $config['min_length'] ?? 8,
            'max_length' => $config['max_length'] ?? 128,
            'require_uppercase' => $config['require_uppercase'] ?? true,
            'require_lowercase' => $config['require_lowercase'] ?? true,
            'require_numbers' => $config['require_numbers'] ?? true,
            'require_symbols' => $config['require_symbols'] ?? true,
            'allowed_symbols' => $config['allowed_symbols'] ?? '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'history_enabled' => config('security.password_policy.history.enabled', true),
            'history_count' => config('security.password_policy.history.remember_count', 5),
            'expiration_enabled' => config('security.password_policy.expiration.enabled', false),
            'expiration_days' => config('security.password_policy.expiration.days', 90)
        ];
    }

    /**
     * 보안 이벤트 로깅
     * 
     * @param string $event 이벤트 타입
     * @param User $user 사용자 객체
     * @param array $context 추가 컨텍스트
     * @return void
     */
    protected function logSecurityEvent(string $event, User $user, array $context = []): void
    {
        Log::info("Password security event: {$event}", array_merge([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ], $context));
    }
}