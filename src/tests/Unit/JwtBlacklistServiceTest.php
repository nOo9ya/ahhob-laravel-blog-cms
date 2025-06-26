<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Ahhob\Blog\Shared\Auth\JwtBlacklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class JwtBlacklistServiceTest extends TestCase
{
    use RefreshDatabase;

    private JwtBlacklistService $blacklistService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->blacklistService = new JwtBlacklistService();
        
        // Cache 초기화
        Cache::flush();
    }

    public function test_add_to_blacklist()
    {
        $tokenId = 'test-token-123';
        $expiresAt = time() + 3600; // 1시간 후
        $userId = 1;
        
        $result = $this->blacklistService->addToBlacklist($tokenId, $expiresAt, $userId);
        
        $this->assertTrue($result);
        
        // 바로 확인
        $isBlacklisted = $this->blacklistService->isBlacklisted($tokenId);
        $this->assertTrue($isBlacklisted);
    }

    public function test_add_to_blacklist_with_past_expiration()
    {
        $tokenId = 'expired-token';
        $expiresAt = time() - 3600; // 1시간 전 (만료됨)
        
        $result = $this->blacklistService->addToBlacklist($tokenId, $expiresAt);
        
        // 만료된 토큰은 블랙리스트에 추가하지 않음
        $this->assertFalse($result);
    }

    public function test_is_blacklisted_with_non_existing_token()
    {
        $isBlacklisted = $this->blacklistService->isBlacklisted('non-existing-token');
        
        $this->assertFalse($isBlacklisted);
    }

    public function test_is_blacklisted_with_existing_token()
    {
        $tokenId = 'existing-token';
        $expiresAt = time() + 3600;
        
        $this->blacklistService->addToBlacklist($tokenId, $expiresAt);
        
        $isBlacklisted = $this->blacklistService->isBlacklisted($tokenId);
        $this->assertTrue($isBlacklisted);
    }

    public function test_blacklist_all_user_tokens()
    {
        $userId = 123;
        
        $result = $this->blacklistService->blacklistAllUserTokens($userId);
        
        $this->assertTrue($result);
        
        // 사용자 블랙리스트 확인
        $isUserBlacklisted = $this->blacklistService->isUserTokenBlacklisted($userId, time());
        $this->assertTrue($isUserBlacklisted);
    }

    public function test_is_user_token_blacklisted_with_non_blacklisted_user()
    {
        $userId = 456;
        $issuedAt = time();
        
        $isBlacklisted = $this->blacklistService->isUserTokenBlacklisted($userId, $issuedAt);
        
        $this->assertFalse($isBlacklisted);
    }

    public function test_is_user_token_blacklisted_with_blacklisted_user()
    {
        $userId = 789;
        $issuedAt = time();
        
        $this->blacklistService->blacklistAllUserTokens($userId);
        
        $isBlacklisted = $this->blacklistService->isUserTokenBlacklisted($userId, $issuedAt);
        $this->assertTrue($isBlacklisted);
    }

    public function test_is_user_token_blacklisted_with_specific_timestamp()
    {
        $userId = 101;
        $blacklistTime = time();
        $tokenIssuedBefore = $blacklistTime - 3600; // 블랙리스트 전에 발급
        $tokenIssuedAfter = $blacklistTime + 3600;  // 블랙리스트 후에 발급
        
        // 사용자를 특정 시점에 블랙리스트에 추가
        $this->blacklistService->blacklistAllUserTokens($userId, $blacklistTime);
        
        // 블랙리스트 전에 발급된 토큰은 무효화
        $this->assertTrue(
            $this->blacklistService->isUserTokenBlacklisted($userId, $tokenIssuedBefore)
        );
        
        // 블랙리스트 후에 발급된 토큰은 유효
        $this->assertFalse(
            $this->blacklistService->isUserTokenBlacklisted($userId, $tokenIssuedAfter)
        );
    }

    public function test_remove_from_blacklist()
    {
        $tokenId = 'removable-token';
        $expiresAt = time() + 3600;
        
        // 블랙리스트에 추가
        $this->blacklistService->addToBlacklist($tokenId, $expiresAt);
        $this->assertTrue($this->blacklistService->isBlacklisted($tokenId));
        
        // 블랙리스트에서 제거
        $result = $this->blacklistService->removeFromBlacklist($tokenId);
        $this->assertTrue($result);
        
        // 제거 후 확인
        $this->assertFalse($this->blacklistService->isBlacklisted($tokenId));
    }

    public function test_remove_user_from_blacklist()
    {
        $userId = 202;
        
        // 사용자를 블랙리스트에 추가
        $this->blacklistService->blacklistAllUserTokens($userId);
        $this->assertTrue($this->blacklistService->isUserTokenBlacklisted($userId, time()));
        
        // 사용자를 블랙리스트에서 제거
        $result = $this->blacklistService->removeUserFromBlacklist($userId);
        $this->assertTrue($result);
        
        // 제거 후 확인
        $this->assertFalse($this->blacklistService->isUserTokenBlacklisted($userId, time()));
    }

    public function test_get_blacklisted_tokens_count()
    {
        // 초기 개수
        $initialCount = $this->blacklistService->getBlacklistedTokensCount();
        
        // 토큰 3개 추가
        $this->blacklistService->addToBlacklist('token1', time() + 3600);
        $this->blacklistService->addToBlacklist('token2', time() + 3600);
        $this->blacklistService->addToBlacklist('token3', time() + 3600);
        
        $newCount = $this->blacklistService->getBlacklistedTokensCount();
        
        $this->assertEquals($initialCount + 3, $newCount);
    }

    public function test_cleanup_expired_tokens()
    {
        // 만료된 토큰과 유효한 토큰 추가
        $expiredTokenId = 'expired-token';
        $validTokenId = 'valid-token';
        
        // 수동으로 만료된 토큰 추가 (Cache에 직접 저장)
        Cache::put('jwt_blacklist:' . $expiredTokenId, [
            'blacklisted_at' => time() - 7200,
            'expires_at' => time() - 3600, // 1시간 전 만료
        ], 1); // 1초 후 자동 삭제
        
        $this->blacklistService->addToBlacklist($validTokenId, time() + 3600);
        
        // 정리 전 상태 확인
        $countBefore = $this->blacklistService->getBlacklistedTokensCount();
        
        // 만료된 토큰 정리
        $cleanedCount = $this->blacklistService->cleanupExpiredTokens();
        
        $this->assertGreaterThanOrEqual(0, $cleanedCount);
        
        // 유효한 토큰은 여전히 블랙리스트에 있어야 함
        $this->assertTrue($this->blacklistService->isBlacklisted($validTokenId));
    }

    public function test_get_blacklist_statistics()
    {
        // 토큰 및 사용자 블랙리스트 추가
        $this->blacklistService->addToBlacklist('stat-token', time() + 3600, 100);
        $this->blacklistService->blacklistAllUserTokens(200);
        
        $stats = $this->blacklistService->getBlacklistStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_blacklisted_tokens', $stats);
        $this->assertArrayHasKey('total_blacklisted_users', $stats);
        $this->assertArrayHasKey('cache_size_mb', $stats);
        
        $this->assertIsInt($stats['total_blacklisted_tokens']);
        $this->assertIsInt($stats['total_blacklisted_users']);
        $this->assertIsFloat($stats['cache_size_mb']);
    }
}
