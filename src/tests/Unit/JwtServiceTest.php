<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\Ahhob\Blog\Shared\Auth\JwtService;
use App\Services\Ahhob\Blog\Shared\Auth\JwtBlacklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtServiceTest extends TestCase
{

    private JwtService $jwtService;
    private JwtBlacklistService $blacklistService;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->blacklistService = $this->createMock(JwtBlacklistService::class);
        $this->jwtService = new JwtService($this->blacklistService);
        
        $this->testUser = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    public function test_authenticate_with_valid_credentials()
    {
        $result = $this->jwtService->authenticate('test@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        
        $this->assertEquals('bearer', $result['token_type']);
        $this->assertEquals($this->testUser->id, $result['user']->id);
        $this->assertIsString($result['access_token']);
        $this->assertIsString($result['refresh_token']);
        $this->assertIsInt($result['expires_in']);
    }

    public function test_authenticate_with_invalid_email()
    {
        $result = $this->jwtService->authenticate('invalid@example.com', 'password123');
        
        $this->assertNull($result);
    }

    public function test_authenticate_with_invalid_password()
    {
        $result = $this->jwtService->authenticate('test@example.com', 'wrongpassword');
        
        $this->assertNull($result);
    }

    public function test_authenticate_with_inactive_user()
    {
        $this->testUser->update(['is_active' => false]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('계정이 비활성화되어 있습니다.');
        
        $this->jwtService->authenticate('test@example.com', 'password123');
    }

    public function test_create_token_returns_string()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_create_refresh_token_returns_string()
    {
        $refreshToken = $this->jwtService->createRefreshToken($this->testUser);
        
        $this->assertIsString($refreshToken);
        $this->assertNotEmpty($refreshToken);
    }

    public function test_validate_token_with_valid_token()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $isValid = $this->jwtService->validateToken($token);
        
        $this->assertTrue($isValid);
    }

    public function test_validate_token_with_invalid_token()
    {
        $isValid = $this->jwtService->validateToken('invalid.token.here');
        
        $this->assertFalse($isValid);
    }

    public function test_get_user_from_token()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $this->blacklistService
            ->method('isBlacklisted')
            ->willReturn(false);
            
        $this->blacklistService
            ->method('isUserTokenBlacklisted')
            ->willReturn(false);
        
        $user = $this->jwtService->getUserFromToken($token);
        
        $this->assertNotNull($user);
        $this->assertEquals($this->testUser->id, $user->id);
    }

    public function test_get_user_from_blacklisted_token()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $this->blacklistService
            ->method('isBlacklisted')
            ->willReturn(true);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('무효화된 토큰입니다.');
        
        $this->jwtService->getUserFromToken($token);
    }

    public function test_get_user_from_user_blacklisted_token()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $this->blacklistService
            ->method('isBlacklisted')
            ->willReturn(false);
            
        $this->blacklistService
            ->method('isUserTokenBlacklisted')
            ->willReturn(true);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('사용자의 토큰이 무효화되었습니다.');
        
        $this->jwtService->getUserFromToken($token);
    }

    public function test_refresh_token()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $result = $this->jwtService->refreshToken($token);
        
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        
        $this->assertEquals('bearer', $result['token_type']);
        $this->assertIsString($result['access_token']);
        $this->assertIsString($result['refresh_token']);
        $this->assertIsInt($result['expires_in']);
    }

    public function test_invalidate_token()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $this->blacklistService
            ->expects($this->once())
            ->method('addToBlacklist')
            ->willReturn(true);
        
        $result = $this->jwtService->invalidateToken($token);
        
        $this->assertTrue($result);
    }

    public function test_get_token_payload()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $payload = $this->jwtService->getTokenPayload($token);
        
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('sub', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertEquals($this->testUser->id, $payload['sub']);
    }

    public function test_get_token_ttl()
    {
        $ttl = $this->jwtService->getTokenTTL();
        
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
    }

    public function test_get_refresh_token_ttl()
    {
        $refreshTtl = $this->jwtService->getRefreshTokenTTL();
        
        $this->assertIsInt($refreshTtl);
        $this->assertGreaterThan(0, $refreshTtl);
    }

    public function test_get_token_expiration()
    {
        $token = $this->jwtService->createToken($this->testUser);
        
        $expiration = $this->jwtService->getTokenExpiration($token);
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $expiration);
        $this->assertTrue($expiration->isFuture());
    }

    public function test_invalidate_all_user_tokens()
    {
        $this->blacklistService
            ->expects($this->once())
            ->method('blacklistAllUserTokens')
            ->with($this->testUser->id)
            ->willReturn(true);
        
        $result = $this->jwtService->invalidateAllUserTokens($this->testUser);
        
        $this->assertTrue($result);
    }

    public function test_is_refresh_token_with_refresh_token()
    {
        $refreshToken = $this->jwtService->createRefreshToken($this->testUser);
        
        $isRefresh = $this->jwtService->isRefreshToken($refreshToken);
        
        $this->assertTrue($isRefresh);
    }

    public function test_is_refresh_token_with_access_token()
    {
        $accessToken = $this->jwtService->createToken($this->testUser);
        
        $isRefresh = $this->jwtService->isRefreshToken($accessToken);
        
        $this->assertFalse($isRefresh);
    }

    public function test_register_new_user()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'user',
        ];
        
        $result = $this->jwtService->register($userData);
        
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        
        $this->assertEquals('New User', $result['user']->name);
        $this->assertEquals('newuser@example.com', $result['user']->email);
        $this->assertEquals('user', $result['user']->role);
        $this->assertTrue($result['user']->is_active);
        
        // 데이터베이스에 사용자가 생성되었는지 확인
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    }

    public function test_register_user_with_username()
    {
        $userData = [
            'name' => 'User With Username',
            'email' => 'usernamed@example.com',
            'username' => 'testusername',
            'password' => 'password123',
        ];
        
        $result = $this->jwtService->register($userData);
        
        $this->assertEquals('testusername', $result['user']->username);
        
        $this->assertDatabaseHas('users', [
            'username' => 'testusername',
        ]);
    }
}
