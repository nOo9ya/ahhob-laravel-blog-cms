<?php

namespace App\Services\Ahhob\Blog\Shared\Auth;

use App\Models\User;
use App\Services\Ahhob\Blog\Shared\Auth\JwtBlacklistService;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

/**
 * JWT 인증 서비스
 * 
 * JWT 토큰의 생성, 검증, 갱신 등을 담당하는 서비스 클래스입니다.
 * Sanctum 대신 JWT를 사용하여 다중 플랫폼 호환성을 제공합니다.
 */
class JwtService
{
    public function __construct(
        private JwtBlacklistService $blacklistService
    ) {}
    /**
     * 사용자 인증 및 토큰 생성
     * 
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function authenticate(string $email, string $password): ?array
    {
        // 사용자 조회
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        // 계정 활성화 상태 확인
        if (!$user->is_active) {
            throw new \Exception('계정이 비활성화되어 있습니다.');
        }

        // JWT 토큰 생성
        $token = $this->createToken($user);
        $refreshToken = $this->createRefreshToken($user);

        // 최종 로그인 시간 업데이트
        $user->update(['last_login_at' => now()]);

        return [
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => $this->getTokenTTL() * 60, // 초 단위
        ];
    }

    /**
     * 액세스 토큰 생성
     * 
     * @param User $user
     * @return string
     */
    public function createToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /**
     * 리프레시 토큰 생성
     * 
     * @param User $user
     * @return string
     */
    public function createRefreshToken(User $user): string
    {
        $customClaims = [
            'type' => 'refresh',
            'user_id' => $user->id,
        ];

        return JWTAuth::customClaims($customClaims)->fromUser($user);
    }

    /**
     * 토큰에서 사용자 정보 조회
     * 
     * @param string|null $token
     * @return User|null
     */
    public function getUserFromToken(?string $token = null): ?User
    {
        try {
            if ($token) {
                JWTAuth::setToken($token);
            }

            $payload = JWTAuth::parseToken()->getPayload();
            
            // 블랙리스트 확인
            $tokenId = $payload->get('jti');
            if ($this->blacklistService->isBlacklisted($tokenId)) {
                throw new \Exception('무효화된 토큰입니다.');
            }

            // 사용자별 블랙리스트 확인
            $userId = $payload->get('sub');
            $issuedAt = $payload->get('iat');
            if ($this->blacklistService->isUserTokenBlacklisted($userId, $issuedAt)) {
                throw new \Exception('사용자의 토큰이 무효화되었습니다.');
            }

            return JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            throw new \Exception('토큰이 만료되었습니다.');
        } catch (TokenInvalidException $e) {
            throw new \Exception('유효하지 않은 토큰입니다.');
        } catch (JWTException $e) {
            throw new \Exception('토큰을 찾을 수 없습니다.');
        }
    }

    /**
     * 토큰 갱신
     * 
     * @param string|null $token
     * @return array
     */
    public function refreshToken(?string $token = null): array
    {
        try {
            if ($token) {
                JWTAuth::setToken($token);
            }

            $newToken = JWTAuth::refresh();
            $user = JWTAuth::setToken($newToken)->toUser();

            return [
                'access_token' => $newToken,
                'refresh_token' => $this->createRefreshToken($user),
                'token_type' => 'bearer',
                'expires_in' => $this->getTokenTTL() * 60,
            ];
        } catch (TokenExpiredException $e) {
            throw new \Exception('리프레시 토큰이 만료되었습니다. 다시 로그인해주세요.');
        } catch (JWTException $e) {
            throw new \Exception('토큰 갱신에 실패했습니다.');
        }
    }

    /**
     * 토큰 무효화 (로그아웃)
     * 
     * @param string|null $token
     * @return bool
     */
    public function invalidateToken(?string $token = null): bool
    {
        try {
            if ($token) {
                JWTAuth::setToken($token);
            }

            $payload = JWTAuth::parseToken()->getPayload();
            $tokenId = $payload->get('jti');
            $expiresAt = $payload->get('exp');
            $userId = $payload->get('sub');

            // 블랙리스트에 추가
            $this->blacklistService->addToBlacklist($tokenId, $expiresAt, $userId);

            // JWT Auth의 기본 무효화도 실행
            JWTAuth::invalidate();
            
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * 토큰 유효성 검증
     * 
     * @param string $token
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        try {
            JWTAuth::setToken($token)->checkOrFail();
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * 토큰 페이로드 조회
     * 
     * @param string|null $token
     * @return array
     */
    public function getTokenPayload(?string $token = null): array
    {
        try {
            if ($token) {
                JWTAuth::setToken($token);
            }

            return JWTAuth::parseToken()->getPayload()->toArray();
        } catch (JWTException $e) {
            throw new \Exception('토큰 페이로드를 읽을 수 없습니다.');
        }
    }

    /**
     * 토큰 TTL 조회 (분 단위)
     * 
     * @return int
     */
    public function getTokenTTL(): int
    {
        return config('jwt.ttl', 60);
    }

    /**
     * 리프레시 토큰 TTL 조회 (분 단위)
     * 
     * @return int
     */
    public function getRefreshTokenTTL(): int
    {
        return config('jwt.refresh_ttl', 20160);
    }

    /**
     * 토큰 만료 시간 조회
     * 
     * @param string|null $token
     * @return \Carbon\Carbon
     */
    public function getTokenExpiration(?string $token = null): \Carbon\Carbon
    {
        $payload = $this->getTokenPayload($token);
        return \Carbon\Carbon::createFromTimestamp($payload['exp']);
    }

    /**
     * 사용자의 모든 토큰 무효화
     * 
     * @param User $user
     * @return bool
     */
    public function invalidateAllUserTokens(User $user): bool
    {
        try {
            // 사용자의 모든 토큰을 블랙리스트에 추가
            $this->blacklistService->blacklistAllUserTokens($user->id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 토큰이 리프레시 토큰인지 확인
     * 
     * @param string $token
     * @return bool
     */
    public function isRefreshToken(string $token): bool
    {
        try {
            $payload = $this->getTokenPayload($token);
            return isset($payload['type']) && $payload['type'] === 'refresh';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 사용자 등록 후 토큰 생성
     * 
     * @param array $userData
     * @return array
     */
    public function register(array $userData): array
    {
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'username' => $userData['username'] ?? null,
            'password' => Hash::make($userData['password']),
            'role' => $userData['role'] ?? 'user',
            'is_active' => true,
        ]);

        $token = $this->createToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'user' => $user,
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => $this->getTokenTTL() * 60,
        ];
    }
}