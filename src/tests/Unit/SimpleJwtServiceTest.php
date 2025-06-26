<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\Ahhob\Blog\Shared\Auth\JwtService;
use App\Services\Ahhob\Blog\Shared\Auth\JwtBlacklistService;
use Illuminate\Support\Facades\Hash;

class SimpleJwtServiceTest extends TestCase
{
    private JwtService $jwtService;
    private JwtBlacklistService $blacklistService;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->blacklistService = $this->createMock(JwtBlacklistService::class);
        $this->jwtService = new JwtService($this->blacklistService);
        
        $this->testUser = new User();
        $this->testUser->id = 1;
        $this->testUser->email = 'test@example.com';
        $this->testUser->name = 'Test User';
        $this->testUser->password = Hash::make('password123');
        $this->testUser->role = 'user';
        $this->testUser->is_active = true;
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
}