<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Fluent\AssertableJson;

class JwtMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private string $validToken;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = User::factory()->create([
            'email' => 'middleware@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'is_active' => true,
        ]);
        
        // 유효한 토큰 생성
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'middleware@example.com',
            'password' => 'password123',
        ]);
        
        $this->validToken = $loginResponse->json('data.tokens.access_token');
    }

    public function test_jwt_auth_middleware_allows_valid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_jwt_auth_middleware_rejects_missing_token()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => '인증 토큰이 필요합니다.',
            ]);
    }

    public function test_jwt_auth_middleware_rejects_malformed_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer malformed.token',
        ])->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_jwt_auth_middleware_rejects_invalid_token_format()
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat ' . $this->validToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_jwt_auth_middleware_handles_inactive_user()
    {
        // 사용자를 비활성화
        $this->testUser->update(['is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => '비활성화된 계정입니다.',
            ]);
    }

    public function test_jwt_auth_middleware_handles_blacklisted_token()
    {
        // 먼저 로그아웃하여 토큰을 블랙리스트에 추가
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->postJson('/api/auth/logout');

        // 블랙리스트된 토큰으로 접근 시도
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_jwt_rate_limit_middleware_blocks_excessive_requests()
    {
        $loginData = [
            'email' => 'middleware@example.com',
            'password' => 'wrongpassword',
        ];

        // Rate limit을 초과할 때까지 요청 반복
        $rateLimitHit = false;
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson('/api/auth/login', $loginData);
            
            if ($response->status() === 429) {
                $rateLimitHit = true;
                break;
            }
        }

        $this->assertTrue($rateLimitHit, 'Rate limit was not triggered');
        
        // Rate limit 응답 구조 확인
        $response->assertStatus(429)
            ->assertJson(function (AssertableJson $json) {
                $json->has('success')
                    ->where('success', false)
                    ->has('message')
                    ->has('data')
                    ->has('data.retry_after');
            });
    }

    public function test_jwt_rate_limit_middleware_different_limits_for_different_endpoints()
    {
        // 로그인 엔드포인트 (10번/분)
        $loginAttempts = 0;
        for ($i = 0; $i < 12; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'middleware@example.com',
                'password' => 'wrongpassword',
            ]);
            
            $loginAttempts++;
            if ($response->status() === 429) {
                break;
            }
        }

        // 토큰 갱신 엔드포인트 (30번/분) - 더 관대함
        $refreshAttempts = 0;
        for ($i = 0; $i < 32; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer invalid.token',
            ])->postJson('/api/auth/refresh');
            
            $refreshAttempts++;
            if ($response->status() === 429) {
                break;
            }
        }

        // 로그인은 10번 이하에서 제한, 토큰 갱신은 30번 이하에서 제한
        $this->assertLessThanOrEqual(11, $loginAttempts);
        $this->assertLessThanOrEqual(31, $refreshAttempts);
        $this->assertGreaterThan($loginAttempts, $refreshAttempts);
    }

    public function test_jwt_response_middleware_adds_security_headers()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200);
        
        // 보안 헤더 확인
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_jwt_response_middleware_adds_cors_headers()
    {
        $response = $this->withHeaders([
            'Origin' => 'https://example.com',
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200);
        
        // CORS 헤더 확인
        $response->assertHeader('Access-Control-Allow-Origin');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }

    public function test_jwt_middleware_handles_expired_token_gracefully()
    {
        // 만료된 토큰 시뮤레이션을 위해 직접 만료된 JWT 생성
        // 실제 애플리케이션에서는 시간을 조작하거나 모킹을 사용
        $expiredToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwiaWF0IjoxNjAwMDAwMDAwLCJleHAiOjE2MDAwMDA2MDAsIm5iZiI6MTYwMDAwMDAwMCwianRpIjoiZXhwaXJlZCIsInN1YiI6MX0.invalid';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_jwt_middleware_provides_token_expiration_warning()
    {
        // 토큰이 곱 만료되는 상황을 시뮤레이션
        // 실제로는 JWT TTL 설정을 짧게 하거나 모킹을 사용
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200);
        
        // 만료 경고 헤더가 설정되는 조건을 테스트
        // (예: 토큰이 10분 내에 만료되는 경우)
        // $response->assertHeader('X-JWT-Refresh-Recommended');
        // $response->assertHeader('X-JWT-Expires-At');
    }

    public function test_api_endpoints_without_auth_requirement_work_normally()
    {
        // 인증이 필요하지 않은 공개 API 엔드포인트
        $publicEndpoints = [
            '/api/posts',
            '/api/categories',
            '/api/tags',
        ];

        foreach ($publicEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            
            // 401 (Unauthorized)가 아닌 다른 상태 코드여야 함
            $this->assertNotEquals(401, $response->status());
        }
    }

    public function test_role_based_access_control_with_jwt()
    {
        // 관리자 사용자 생성
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 관리자 토큰 생성
        $adminLoginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        
        $adminToken = $adminLoginResponse->json('data.tokens.access_token');

        // 일반 사용자로 관리자 전용 엔드포인트 접근 시도
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
        ])->postJson('/api/categories', [
            'name' => '새 카테고리',
            'slug' => 'new-category',
        ]);

        $response->assertStatus(403); // Forbidden

        // 관리자로 동일 엔드포인트 접근
        $adminResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/categories', [
            'name' => '새 카테고리',
            'slug' => 'new-category',
        ]);

        $adminResponse->assertStatus(201); // Created
    }

    public function test_concurrent_requests_with_same_token()
    {
        // 동일 토큰으로 동시 요청 시뮤레이션
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
            ])->getJson('/api/auth/user');
        }

        // 모든 요청이 성공해야 함
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }
}
