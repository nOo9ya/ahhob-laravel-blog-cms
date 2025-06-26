<?php

namespace App\Services\Ahhob\Blog\Shared\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * JWT 블랙리스트 서비스
 * 
 * 무효화된 JWT 토큰을 관리하여 로그아웃된 토큰의 재사용을 방지합니다.
 * Redis 캐시를 사용하여 고성능을 보장합니다.
 */
class JwtBlacklistService
{
    /**
     * 블랙리스트 캐시 접두사
     */
    private const BLACKLIST_PREFIX = 'jwt_blacklist:';

    /**
     * 사용자별 블랙리스트 접두사
     */
    private const USER_BLACKLIST_PREFIX = 'jwt_user_blacklist:';

    /**
     * 토큰을 블랙리스트에 추가
     * 
     * @param string $tokenId JWT ID (jti)
     * @param int $expiresAt 만료 시간 (타임스탬프)
     * @param int|null $userId 사용자 ID
     * @return bool
     */
    public function addToBlacklist(string $tokenId, int $expiresAt, ?int $userId = null): bool
    {
        try {
            $key = self::BLACKLIST_PREFIX . $tokenId;
            $ttl = $expiresAt - time();

            // 이미 만료된 토큰은 블랙리스트에 추가하지 않음
            if ($ttl <= 0) {
                return true;
            }

            $data = [
                'blacklisted_at' => time(),
                'expires_at' => $expiresAt,
                'user_id' => $userId,
            ];

            $result = Cache::put($key, $data, $ttl);

            // 사용자별 블랙리스트에도 추가
            if ($userId) {
                $this->addToUserBlacklist($userId, $tokenId, $expiresAt);
            }

            Log::info('Token added to blacklist', [
                'token_id' => $tokenId,
                'user_id' => $userId,
                'expires_at' => $expiresAt,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to add token to blacklist', [
                'token_id' => $tokenId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 토큰이 블랙리스트에 있는지 확인
     * 
     * @param string $tokenId JWT ID (jti)
     * @return bool
     */
    public function isBlacklisted(string $tokenId): bool
    {
        try {
            $key = self::BLACKLIST_PREFIX . $tokenId;
            return Cache::has($key);

        } catch (\Exception $e) {
            Log::error('Failed to check blacklist status', [
                'token_id' => $tokenId,
                'error' => $e->getMessage(),
            ]);

            // 캐시 오류 시 안전을 위해 블랙리스트에 있다고 가정
            return true;
        }
    }

    /**
     * 블랙리스트에서 토큰 제거
     * 
     * @param string $tokenId JWT ID (jti)
     * @return bool
     */
    public function removeFromBlacklist(string $tokenId): bool
    {
        try {
            $key = self::BLACKLIST_PREFIX . $tokenId;
            $result = Cache::forget($key);

            Log::info('Token removed from blacklist', [
                'token_id' => $tokenId,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to remove token from blacklist', [
                'token_id' => $tokenId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 사용자의 모든 토큰을 블랙리스트에 추가
     * 
     * @param int $userId 사용자 ID
     * @return bool
     */
    public function blacklistAllUserTokens(int $userId): bool
    {
        try {
            $userBlacklistKey = self::USER_BLACKLIST_PREFIX . $userId;
            $blacklistEntry = [
                'blacklisted_at' => time(),
                'all_tokens' => true,
            ];

            // 24시간 동안 유지 (일반적인 JWT TTL보다 충분히 긴 시간)
            $ttl = 24 * 60 * 60;
            $result = Cache::put($userBlacklistKey, $blacklistEntry, $ttl);

            Log::info('All user tokens blacklisted', [
                'user_id' => $userId,
                'blacklisted_at' => time(),
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to blacklist all user tokens', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 사용자의 토큰이 블랙리스트에 있는지 확인
     * 
     * @param int $userId 사용자 ID
     * @param int $tokenIssuedAt 토큰 발행 시간 (타임스탬프)
     * @return bool
     */
    public function isUserTokenBlacklisted(int $userId, int $tokenIssuedAt): bool
    {
        try {
            $userBlacklistKey = self::USER_BLACKLIST_PREFIX . $userId;
            $blacklistEntry = Cache::get($userBlacklistKey);

            if (!$blacklistEntry) {
                return false;
            }

            // 모든 토큰이 블랙리스트된 경우
            if (isset($blacklistEntry['all_tokens']) && $blacklistEntry['all_tokens']) {
                return $tokenIssuedAt < $blacklistEntry['blacklisted_at'];
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to check user token blacklist status', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 사용자별 블랙리스트에 토큰 추가
     * 
     * @param int $userId 사용자 ID
     * @param string $tokenId JWT ID
     * @param int $expiresAt 만료 시간
     * @return bool
     */
    private function addToUserBlacklist(int $userId, string $tokenId, int $expiresAt): bool
    {
        try {
            $userBlacklistKey = self::USER_BLACKLIST_PREFIX . $userId;
            $existingBlacklist = Cache::get($userBlacklistKey, []);

            // 기존 블랙리스트가 "모든 토큰" 타입이면 개별 토큰은 추가하지 않음
            if (isset($existingBlacklist['all_tokens'])) {
                return true;
            }

            // 개별 토큰 추가
            $existingBlacklist['tokens'][$tokenId] = [
                'blacklisted_at' => time(),
                'expires_at' => $expiresAt,
            ];

            $ttl = max($expiresAt - time(), 3600); // 최소 1시간
            return Cache::put($userBlacklistKey, $existingBlacklist, $ttl);

        } catch (\Exception $e) {
            Log::error('Failed to add token to user blacklist', [
                'user_id' => $userId,
                'token_id' => $tokenId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 만료된 블랙리스트 항목 정리
     * 
     * 주기적으로 실행되어야 하는 정리 작업입니다.
     * 
     * @return int 정리된 항목 수
     */
    public function cleanup(): int
    {
        // 이 메서드는 실제로는 Redis의 TTL에 의해 자동으로 처리되므로
        // 별도의 구현이 필요하지 않습니다.
        // 하지만 추가적인 정리 로직이 필요한 경우 여기에 구현할 수 있습니다.
        
        Log::info('JWT blacklist cleanup completed');
        return 0;
    }

    /**
     * 블랙리스트 통계 조회
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        try {
            // Redis 키 패턴으로 블랙리스트 항목 수 조회
            $blacklistCount = 0;
            $userBlacklistCount = 0;

            // 실제 구현에서는 Redis SCAN 명령어를 사용하여 효율적으로 계산
            // 여기서는 기본적인 구조만 제공

            return [
                'total_blacklisted_tokens' => $blacklistCount,
                'users_with_blacklisted_tokens' => $userBlacklistCount,
                'last_cleanup' => Cache::get('jwt_blacklist_last_cleanup'),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get blacklist statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'total_blacklisted_tokens' => 0,
                'users_with_blacklisted_tokens' => 0,
                'last_cleanup' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}