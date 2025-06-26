<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\PasswordHistory;
use App\Services\Security\PasswordPolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 보안 감사 Artisan 명령어
 * 
 * 이 명령어는 시스템의 전반적인 보안 상태를 점검하고 리포트를 생성합니다:
 * - 사용자 계정 보안 상태 분석
 * - 비밀번호 정책 준수 여부 확인
 * - 만료된 비밀번호 및 계정 식별
 * - 보안 설정 검증
 * - 잠재적 보안 위험 요소 탐지
 * 
 * 사용법:
 * php artisan security:audit                    # 전체 보안 감사
 * php artisan security:audit --passwords        # 비밀번호 정책만 검사
 * php artisan security:audit --users            # 사용자 계정만 검사
 * php artisan security:audit --fix              # 발견된 문제 자동 수정
 */
class SecurityAudit extends Command
{
    /**
     * 명령어 시그니처
     */
    protected $signature = 'security:audit 
                            {--passwords : 비밀번호 정책 감사만 수행}
                            {--users : 사용자 계정 감사만 수행}
                            {--settings : 보안 설정 감사만 수행}
                            {--fix : 발견된 문제점을 자동으로 수정}
                            {--format=table : 출력 형식 (table, json, csv)}
                            {--output= : 결과를 파일로 저장할 경로}';

    /**
     * 명령어 설명
     */
    protected $description = '시스템의 보안 상태를 점검하고 감사 리포트를 생성합니다';

    /**
     * 비밀번호 정책 서비스
     */
    protected PasswordPolicyService $passwordPolicyService;

    /**
     * 감사 결과 저장
     */
    protected array $auditResults = [
        'summary' => [],
        'password_issues' => [],
        'user_issues' => [],
        'settings_issues' => [],
        'recommendations' => [],
        'fixed_issues' => []
    ];

    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct();
        $this->passwordPolicyService = app(PasswordPolicyService::class);
    }

    /**
     * 명령어 실행
     */
    public function handle(): int
    {
        $this->info('🔒 보안 감사 시작');
        $this->info('==================');

        $startTime = now();

        try {
            // 감사 범위 결정
            $auditPasswords = $this->option('passwords') || !$this->hasSpecificOption();
            $auditUsers = $this->option('users') || !$this->hasSpecificOption();
            $auditSettings = $this->option('settings') || !$this->hasSpecificOption();

            // 감사 실행
            if ($auditPasswords) {
                $this->auditPasswordPolicies();
            }

            if ($auditUsers) {
                $this->auditUserAccounts();
            }

            if ($auditSettings) {
                $this->auditSecuritySettings();
            }

            // 자동 수정 실행
            if ($this->option('fix')) {
                $this->fixIssues();
            }

            // 결과 처리 및 출력
            $this->generateSummary($startTime);
            $this->displayResults();

            // 파일 출력
            if ($this->option('output')) {
                $this->saveToFile();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('⚠️  감사 실행 중 오류가 발생했습니다: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * 특정 옵션이 지정되었는지 확인
     */
    protected function hasSpecificOption(): bool
    {
        return $this->option('passwords') || 
               $this->option('users') || 
               $this->option('settings');
    }

    /**
     * 비밀번호 정책 감사
     */
    protected function auditPasswordPolicies(): void
    {
        $this->info('🔑 비밀번호 정책 감사 중...');

        $passwordIssues = [];

        // 1. 약한 비밀번호를 가진 사용자 찾기
        $this->line('  - 약한 비밀번호 검사...');
        $weakPasswordUsers = $this->findWeakPasswords();
        if (!empty($weakPasswordUsers)) {
            $passwordIssues[] = [
                'type' => 'weak_passwords',
                'severity' => 'high',
                'count' => count($weakPasswordUsers),
                'description' => '복잡도 요구사항을 충족하지 않는 약한 비밀번호',
                'users' => $weakPasswordUsers
            ];
        }

        // 2. 만료된 비밀번호 확인
        $this->line('  - 만료된 비밀번호 검사...');
        $expiredPasswords = $this->findExpiredPasswords();
        if (!empty($expiredPasswords)) {
            $passwordIssues[] = [
                'type' => 'expired_passwords',
                'severity' => 'medium',
                'count' => count($expiredPasswords),
                'description' => '만료된 비밀번호를 사용하는 계정',
                'users' => $expiredPasswords
            ];
        }

        // 3. 비밀번호 히스토리 정리 필요 계정
        $this->line('  - 비밀번호 히스토리 검사...');
        $historyCleanup = $this->checkPasswordHistoryCleanup();
        if ($historyCleanup['needs_cleanup'] > 0) {
            $passwordIssues[] = [
                'type' => 'history_cleanup',
                'severity' => 'low',
                'count' => $historyCleanup['needs_cleanup'],
                'description' => '비밀번호 히스토리 정리가 필요한 계정',
                'details' => $historyCleanup
            ];
        }

        // 4. 비밀번호 정책 설정 검증
        $this->line('  - 비밀번호 정책 설정 검증...');
        $policyIssues = $this->validatePasswordPolicySettings();
        if (!empty($policyIssues)) {
            $passwordIssues = array_merge($passwordIssues, $policyIssues);
        }

        $this->auditResults['password_issues'] = $passwordIssues;
    }

    /**
     * 사용자 계정 감사
     */
    protected function auditUserAccounts(): void
    {
        $this->info('👥 사용자 계정 감사 중...');

        $userIssues = [];

        // 1. 비활성화되지 않은 오래된 계정
        $this->line('  - 오래된 계정 검사...');
        $inactiveAccounts = $this->findInactiveAccounts();
        if (!empty($inactiveAccounts)) {
            $userIssues[] = [
                'type' => 'inactive_accounts',
                'severity' => 'medium',
                'count' => count($inactiveAccounts),
                'description' => '90일 이상 미사용 계정',
                'users' => $inactiveAccounts
            ];
        }

        // 2. 관리자 권한 검사
        $this->line('  - 관리자 권한 검사...');
        $adminIssues = $this->auditAdminAccounts();
        if (!empty($adminIssues)) {
            $userIssues = array_merge($userIssues, $adminIssues);
        }

        // 3. 계정 잠금 상태 검사
        $this->line('  - 계정 잠금 상태 검사...');
        $lockedAccounts = $this->findLockedAccounts();
        if (!empty($lockedAccounts)) {
            $userIssues[] = [
                'type' => 'locked_accounts',
                'severity' => 'info',
                'count' => count($lockedAccounts),
                'description' => '현재 잠금된 계정',
                'users' => $lockedAccounts
            ];
        }

        // 4. 이메일 인증되지 않은 계정
        $this->line('  - 이메일 인증 상태 검사...');
        $unverifiedAccounts = $this->findUnverifiedAccounts();
        if (!empty($unverifiedAccounts)) {
            $userIssues[] = [
                'type' => 'unverified_emails',
                'severity' => 'low',
                'count' => count($unverifiedAccounts),
                'description' => '이메일 인증이 완료되지 않은 계정',
                'users' => $unverifiedAccounts
            ];
        }

        $this->auditResults['user_issues'] = $userIssues;
    }

    /**
     * 보안 설정 감사
     */
    protected function auditSecuritySettings(): void
    {
        $this->info('⚙️  보안 설정 감사 중...');

        $settingsIssues = [];

        // 1. 환경 설정 검증
        $this->line('  - 환경 설정 검증...');
        $envIssues = $this->validateEnvironmentSettings();
        if (!empty($envIssues)) {
            $settingsIssues = array_merge($settingsIssues, $envIssues);
        }

        // 2. 보안 헤더 설정 확인
        $this->line('  - 보안 헤더 설정 확인...');
        $headerIssues = $this->validateSecurityHeaders();
        if (!empty($headerIssues)) {
            $settingsIssues = array_merge($settingsIssues, $headerIssues);
        }

        // 3. 암호화 설정 검증
        $this->line('  - 암호화 설정 검증...');
        $encryptionIssues = $this->validateEncryptionSettings();
        if (!empty($encryptionIssues)) {
            $settingsIssues = array_merge($settingsIssues, $encryptionIssues);
        }

        $this->auditResults['settings_issues'] = $settingsIssues;
    }

    /**
     * 약한 비밀번호를 가진 사용자 찾기
     */
    protected function findWeakPasswords(): array
    {
        $weakPasswordUsers = [];
        
        // 실제 운영 환경에서는 성능상 이유로 모든 비밀번호를 검사하지 않을 수 있음
        // 샘플링하거나 특정 조건의 사용자만 검사
        $users = User::whereNotNull('password')
            ->where('created_at', '>', now()->subMonths(6)) // 최근 6개월 가입자만
            ->limit(100) // 성능을 위해 제한
            ->get();

        foreach ($users as $user) {
            // 실제 비밀번호는 해시되어 있으므로 직접 검증 불가능
            // 대신 비밀번호 변경 이력이나 패턴을 분석
            if ($this->hasWeakPasswordIndicators($user)) {
                $weakPasswordUsers[] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'last_password_change' => $user->password_changed_at,
                    'indicators' => $this->getWeakPasswordIndicators($user)
                ];
            }
        }

        return $weakPasswordUsers;
    }

    /**
     * 약한 비밀번호 지표 확인
     */
    protected function hasWeakPasswordIndicators(User $user): bool
    {
        // 1. 비밀번호를 변경한 적이 없음
        if (!$user->password_changed_at) {
            return true;
        }

        // 2. 계정 생성 후 비밀번호를 변경하지 않음
        if ($user->password_changed_at->equalTo($user->created_at)) {
            return true;
        }

        // 3. 비밀번호 히스토리가 없음 (한 번도 변경하지 않음)
        $historyCount = PasswordHistory::where('user_id', $user->id)->count();
        if ($historyCount === 0 && $user->created_at < now()->subDays(30)) {
            return true;
        }

        return false;
    }

    /**
     * 약한 비밀번호 지표 목록 반환
     */
    protected function getWeakPasswordIndicators(User $user): array
    {
        $indicators = [];

        if (!$user->password_changed_at) {
            $indicators[] = '비밀번호 변경 이력 없음';
        }

        if ($user->password_changed_at && $user->password_changed_at->equalTo($user->created_at)) {
            $indicators[] = '기본 비밀번호 사용 중';
        }

        $historyCount = PasswordHistory::where('user_id', $user->id)->count();
        if ($historyCount === 0 && $user->created_at < now()->subDays(30)) {
            $indicators[] = '비밀번호 변경 없이 30일 경과';
        }

        return $indicators;
    }

    /**
     * 만료된 비밀번호 찾기
     */
    protected function findExpiredPasswords(): array
    {
        if (!config('security.password_policy.expiration.enabled', false)) {
            return [];
        }

        $expirationDays = config('security.password_policy.expiration.days', 90);
        $expiryDate = now()->subDays($expirationDays);

        return User::where(function ($query) use ($expiryDate) {
            $query->where('password_changed_at', '<', $expiryDate)
                  ->orWhere(function ($q) use ($expiryDate) {
                      $q->whereNull('password_changed_at')
                        ->where('created_at', '<', $expiryDate);
                  });
        })
        ->get()
        ->map(function ($user) {
            $passwordAge = $user->password_changed_at 
                ? $user->password_changed_at->diffInDays(now())
                : $user->created_at->diffInDays(now());

            return [
                'id' => $user->id,
                'email' => $user->email,
                'password_age_days' => $passwordAge,
                'last_login' => $user->last_login_at
            ];
        })
        ->toArray();
    }

    /**
     * 비밀번호 히스토리 정리 필요 확인
     */
    protected function checkPasswordHistoryCleanup(): array
    {
        $historyLimit = config('security.password_policy.history.remember_count', 5);
        
        $usersNeedingCleanup = DB::table('password_histories')
            ->select('user_id', DB::raw('COUNT(*) as history_count'))
            ->groupBy('user_id')
            ->having('history_count', '>', $historyLimit)
            ->get();

        $totalExcessRecords = DB::table('password_histories')
            ->selectRaw('
                user_id,
                COUNT(*) - ? as excess_records
            ', [$historyLimit])
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > ?', [$historyLimit])
            ->sum('excess_records');

        return [
            'needs_cleanup' => $usersNeedingCleanup->count(),
            'total_excess_records' => $totalExcessRecords ?? 0,
            'users' => $usersNeedingCleanup->toArray()
        ];
    }

    /**
     * 비밀번호 정책 설정 검증
     */
    protected function validatePasswordPolicySettings(): array
    {
        $issues = [];

        // 최소 길이가 너무 짧은 경우
        $minLength = config('security.password_policy.complexity.min_length', 8);
        if ($minLength < 8) {
            $issues[] = [
                'type' => 'weak_min_length',
                'severity' => 'high',
                'description' => "비밀번호 최소 길이가 너무 짧습니다 (현재: {$minLength}자)",
                'recommendation' => '최소 8자 이상으로 설정하세요'
            ];
        }

        // 복잡도 요구사항이 비활성화된 경우
        $requirements = config('security.password_policy.complexity', []);
        $disabledRequirements = [];

        if (!($requirements['require_uppercase'] ?? true)) {
            $disabledRequirements[] = '대문자';
        }
        if (!($requirements['require_numbers'] ?? true)) {
            $disabledRequirements[] = '숫자';
        }
        if (!($requirements['require_symbols'] ?? true)) {
            $disabledRequirements[] = '특수문자';
        }

        if (!empty($disabledRequirements)) {
            $issues[] = [
                'type' => 'disabled_complexity',
                'severity' => 'medium',
                'description' => '비밀번호 복잡도 요구사항이 비활성화됨: ' . implode(', ', $disabledRequirements),
                'recommendation' => '모든 복잡도 요구사항을 활성화하세요'
            ];
        }

        return $issues;
    }

    /**
     * 비활성 계정 찾기
     */
    protected function findInactiveAccounts(): array
    {
        $inactiveDate = now()->subDays(90);

        return User::where(function ($query) use ($inactiveDate) {
            $query->where('last_login_at', '<', $inactiveDate)
                  ->orWhereNull('last_login_at');
        })
        ->where('created_at', '<', $inactiveDate)
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'last_login_at' => $user->last_login_at,
                'days_inactive' => $user->last_login_at 
                    ? $user->last_login_at->diffInDays(now())
                    : $user->created_at->diffInDays(now())
            ];
        })
        ->toArray();
    }

    /**
     * 관리자 계정 감사
     */
    protected function auditAdminAccounts(): array
    {
        $issues = [];

        $adminUsers = User::where('role', 'admin')->get();

        // 너무 많은 관리자 계정
        if ($adminUsers->count() > 5) {
            $issues[] = [
                'type' => 'too_many_admins',
                'severity' => 'medium',
                'count' => $adminUsers->count(),
                'description' => '관리자 계정이 너무 많습니다',
                'recommendation' => '필요하지 않은 관리자 권한을 제거하세요'
            ];
        }

        // 오랫동안 로그인하지 않은 관리자
        $inactiveAdmins = $adminUsers->filter(function ($admin) {
            if (!$admin->last_login_at) {
                return true;
            }
            return $admin->last_login_at < now()->subDays(30);
        });

        if ($inactiveAdmins->count() > 0) {
            $issues[] = [
                'type' => 'inactive_admins',
                'severity' => 'high',
                'count' => $inactiveAdmins->count(),
                'description' => '30일 이상 미사용 관리자 계정',
                'users' => $inactiveAdmins->map(function ($admin) {
                    return [
                        'id' => $admin->id,
                        'email' => $admin->email,
                        'last_login_at' => $admin->last_login_at
                    ];
                })->toArray()
            ];
        }

        return $issues;
    }

    /**
     * 잠금된 계정 찾기
     */
    protected function findLockedAccounts(): array
    {
        // 계정 잠금 기능이 구현된 경우
        return User::whereNotNull('locked_at')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'locked_at' => $user->locked_at ?? null,
                    'lock_reason' => $user->lock_reason ?? 'Unknown'
                ];
            })
            ->toArray();
    }

    /**
     * 이메일 인증되지 않은 계정 찾기
     */
    protected function findUnverifiedAccounts(): array
    {
        return User::whereNull('email_verified_at')
            ->where('created_at', '<', now()->subDays(7)) // 7일 이상 된 계정만
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'days_unverified' => $user->created_at->diffInDays(now())
                ];
            })
            ->toArray();
    }

    /**
     * 환경 설정 검증
     */
    protected function validateEnvironmentSettings(): array
    {
        $issues = [];

        // APP_DEBUG가 운영환경에서 활성화된 경우
        if (app()->environment('production') && config('app.debug')) {
            $issues[] = [
                'type' => 'debug_enabled_production',
                'severity' => 'critical',
                'description' => '운영 환경에서 디버그 모드가 활성화되어 있습니다',
                'recommendation' => 'APP_DEBUG=false로 설정하세요'
            ];
        }

        // APP_KEY가 설정되지 않은 경우
        if (empty(config('app.key'))) {
            $issues[] = [
                'type' => 'missing_app_key',
                'severity' => 'critical',
                'description' => '애플리케이션 키가 설정되지 않았습니다',
                'recommendation' => 'php artisan key:generate 명령어를 실행하세요'
            ];
        }

        // HTTPS가 강제되지 않은 경우 (운영환경)
        if (app()->environment('production') && !config('app.force_https', false)) {
            $issues[] = [
                'type' => 'https_not_forced',
                'severity' => 'high',
                'description' => '운영 환경에서 HTTPS가 강제되지 않습니다',
                'recommendation' => 'HTTPS를 강제하도록 설정하세요'
            ];
        }

        return $issues;
    }

    /**
     * 보안 헤더 설정 확인
     */
    protected function validateSecurityHeaders(): array
    {
        $issues = [];

        if (!config('security.security_headers.enabled', true)) {
            $issues[] = [
                'type' => 'security_headers_disabled',
                'severity' => 'high',
                'description' => '보안 헤더가 비활성화되어 있습니다',
                'recommendation' => 'SECURITY_HEADERS_ENABLED=true로 설정하세요'
            ];
        }

        if (!config('security.xss_protection.csp.enabled', true)) {
            $issues[] = [
                'type' => 'csp_disabled',
                'severity' => 'medium',
                'description' => 'Content Security Policy가 비활성화되어 있습니다',
                'recommendation' => 'CSP_ENABLED=true로 설정하세요'
            ];
        }

        return $issues;
    }

    /**
     * 암호화 설정 검증
     */
    protected function validateEncryptionSettings(): array
    {
        $issues = [];

        // 약한 암호화 알고리즘 사용
        $cipher = config('app.cipher');
        if ($cipher !== 'AES-256-CBC') {
            $issues[] = [
                'type' => 'weak_encryption_cipher',
                'severity' => 'high',
                'description' => "약한 암호화 알고리즘을 사용하고 있습니다: {$cipher}",
                'recommendation' => 'AES-256-CBC를 사용하세요'
            ];
        }

        return $issues;
    }

    /**
     * 발견된 문제점 자동 수정
     */
    protected function fixIssues(): void
    {
        $this->info('🔧 발견된 문제점 자동 수정 중...');

        $fixedCount = 0;

        // 1. 비밀번호 히스토리 정리
        $historyCleanup = $this->auditResults['password_issues'] ?? [];
        foreach ($historyCleanup as $issue) {
            if ($issue['type'] === 'history_cleanup') {
                $cleaned = PasswordHistory::cleanupAll();
                $this->auditResults['fixed_issues'][] = [
                    'type' => 'history_cleanup',
                    'description' => "비밀번호 히스토리 정리 완료",
                    'details' => $cleaned
                ];
                $fixedCount++;
                break;
            }
        }

        // 2. 기타 자동 수정 가능한 문제들...

        if ($fixedCount > 0) {
            $this->info("✅ {$fixedCount}개의 문제가 자동으로 수정되었습니다.");
        } else {
            $this->warn('자동으로 수정 가능한 문제가 없습니다.');
        }
    }

    /**
     * 감사 요약 생성
     */
    protected function generateSummary(Carbon $startTime): void
    {
        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $totalIssues = 0;
        $criticalIssues = 0;
        $highIssues = 0;
        $mediumIssues = 0;
        $lowIssues = 0;

        $allIssues = array_merge(
            $this->auditResults['password_issues'],
            $this->auditResults['user_issues'],
            $this->auditResults['settings_issues']
        );

        foreach ($allIssues as $issue) {
            $totalIssues++;
            switch ($issue['severity'] ?? 'low') {
                case 'critical':
                    $criticalIssues++;
                    break;
                case 'high':
                    $highIssues++;
                    break;
                case 'medium':
                    $mediumIssues++;
                    break;
                case 'low':
                    $lowIssues++;
                    break;
            }
        }

        $this->auditResults['summary'] = [
            'audit_date' => $endTime->toISOString(),
            'duration_seconds' => $duration,
            'total_issues' => $totalIssues,
            'critical_issues' => $criticalIssues,
            'high_issues' => $highIssues,
            'medium_issues' => $mediumIssues,
            'low_issues' => $lowIssues,
            'fixed_issues' => count($this->auditResults['fixed_issues']),
            'security_score' => $this->calculateSecurityScore($totalIssues, $criticalIssues, $highIssues)
        ];

        // 권장사항 생성
        $this->generateRecommendations();
    }

    /**
     * 보안 점수 계산
     */
    protected function calculateSecurityScore(int $total, int $critical, int $high): int
    {
        $baseScore = 100;
        
        // 중요도별 감점
        $penalty = ($critical * 20) + ($high * 10) + (max(0, $total - $critical - $high) * 5);
        
        return max(0, $baseScore - $penalty);
    }

    /**
     * 권장사항 생성
     */
    protected function generateRecommendations(): void
    {
        $recommendations = [];

        $summary = $this->auditResults['summary'];

        if ($summary['critical_issues'] > 0) {
            $recommendations[] = '🚨 즉시 조치 필요: 치명적인 보안 문제가 발견되었습니다.';
        }

        if ($summary['security_score'] < 70) {
            $recommendations[] = '⚠️ 전반적인 보안 강화가 필요합니다.';
        }

        if (empty($this->auditResults['fixed_issues'])) {
            $recommendations[] = '🔧 --fix 옵션을 사용하여 자동 수정 가능한 문제들을 해결하세요.';
        }

        $recommendations[] = '📅 정기적인 보안 감사를 수행하세요 (권장: 월 1회).';
        $recommendations[] = '📚 보안 정책 문서를 최신 상태로 유지하세요.';

        $this->auditResults['recommendations'] = $recommendations;
    }

    /**
     * 결과 표시
     */
    protected function displayResults(): void
    {
        $this->line('');
        $this->info('📊 보안 감사 결과');
        $this->info('=================');

        $summary = $this->auditResults['summary'];

        // 요약 정보
        $this->table(
            ['항목', '값'],
            [
                ['감사 일시', $summary['audit_date']],
                ['소요 시간', $summary['duration_seconds'] . '초'],
                ['보안 점수', $summary['security_score'] . '/100'],
                ['전체 문제', $summary['total_issues']],
                ['치명적', $summary['critical_issues']],
                ['높음', $summary['high_issues']],
                ['보통', $summary['medium_issues']],
                ['낮음', $summary['low_issues']],
                ['수정됨', $summary['fixed_issues']],
            ]
        );

        // 상세 문제들 표시
        if ($summary['total_issues'] > 0) {
            $this->displayDetailedIssues();
        }

        // 권장사항 표시
        $this->line('');
        $this->info('💡 권장사항:');
        foreach ($this->auditResults['recommendations'] as $recommendation) {
            $this->line('  ' . $recommendation);
        }
    }

    /**
     * 상세 문제 표시
     */
    protected function displayDetailedIssues(): void
    {
        $allIssues = array_merge(
            $this->auditResults['password_issues'],
            $this->auditResults['user_issues'],
            $this->auditResults['settings_issues']
        );

        if (empty($allIssues)) {
            return;
        }

        $this->line('');
        $this->warn('🔍 발견된 문제들:');

        foreach ($allIssues as $issue) {
            $severityIcon = match($issue['severity'] ?? 'low') {
                'critical' => '🚨',
                'high' => '⚠️',
                'medium' => '⚡',
                'low' => 'ℹ️',
                default => '❓'
            };

            $this->line("  {$severityIcon} [{$issue['severity']}] {$issue['description']}");
            
            if (isset($issue['count'])) {
                $this->line("     영향: {$issue['count']}개 항목");
            }
            
            if (isset($issue['recommendation'])) {
                $this->line("     권장: {$issue['recommendation']}");
            }
        }
    }

    /**
     * 결과를 파일로 저장
     */
    protected function saveToFile(): void
    {
        $outputPath = $this->option('output');
        $format = $this->option('format');

        try {
            switch ($format) {
                case 'json':
                    file_put_contents($outputPath, json_encode($this->auditResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    break;
                case 'csv':
                    $this->saveToCsv($outputPath);
                    break;
                default:
                    $this->saveToText($outputPath);
            }

            $this->info("📄 결과가 {$outputPath}에 저장되었습니다.");

        } catch (\Exception $e) {
            $this->error("파일 저장 실패: " . $e->getMessage());
        }
    }

    /**
     * CSV 형식으로 저장
     */
    protected function saveToCsv(string $path): void
    {
        $fp = fopen($path, 'w');
        
        // CSV 헤더
        fputcsv($fp, ['유형', '심각도', '설명', '개수', '권장사항']);

        $allIssues = array_merge(
            $this->auditResults['password_issues'],
            $this->auditResults['user_issues'],
            $this->auditResults['settings_issues']
        );

        foreach ($allIssues as $issue) {
            fputcsv($fp, [
                $issue['type'],
                $issue['severity'] ?? 'low',
                $issue['description'],
                $issue['count'] ?? 1,
                $issue['recommendation'] ?? ''
            ]);
        }

        fclose($fp);
    }

    /**
     * 텍스트 형식으로 저장
     */
    protected function saveToText(string $path): void
    {
        $content = "보안 감사 리포트\n";
        $content .= "=================\n\n";
        
        $summary = $this->auditResults['summary'];
        $content .= "감사 일시: {$summary['audit_date']}\n";
        $content .= "보안 점수: {$summary['security_score']}/100\n";
        $content .= "전체 문제: {$summary['total_issues']}\n\n";

        // 상세 문제들
        $allIssues = array_merge(
            $this->auditResults['password_issues'],
            $this->auditResults['user_issues'],
            $this->auditResults['settings_issues']
        );

        foreach ($allIssues as $issue) {
            $content .= "[{$issue['severity']}] {$issue['description']}\n";
            if (isset($issue['recommendation'])) {
                $content .= "권장: {$issue['recommendation']}\n";
            }
            $content .= "\n";
        }

        file_put_contents($path, $content);
    }
}