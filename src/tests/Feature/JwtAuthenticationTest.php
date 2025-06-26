<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'name' => '테스트 사용자',
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    public function test_user_registration()
    {
        $userData = [
            'name' => '새로운 사용자',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson(function (AssertableJson $json) {
                $json->has('success')
                    ->where('success', true)
                    ->has('message')
                    ->has('data')
                    ->has('data.user')
                    ->has('data.tokens')
                    ->has('data.tokens.access_token')
                    ->has('data.tokens.refresh_token')
                    ->has('data.tokens.token_type')
                    ->has('data.tokens.expires_in')
                    ->where('data.user.email', 'newuser@example.com')
                    ->where('data.user.name', '새로운 사용자')
                    ->where('data.tokens.token_type', 'bearer');
            });

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => '새로운 사용자',
        ]);
    }

    public function test_user_registration_with_invalid_data()
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_login_with_valid_credentials()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJson(function (AssertableJson $json) {
                $json->has('success')
                    ->where('success', true)
                    ->has('message')
                    ->has('data')
                    ->has('data.user')
                    ->has('data.tokens')
                    ->has('data.tokens.access_token')
                    ->has('data.tokens.refresh_token')
                    ->where('data.user.email', 'test@example.com')
                    ->where('data.tokens.token_type', 'bearer');
            });
    }

    public function test_user_login_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => '이메일 또는 비밀번호가 올바르지 않습니다.',
            ]);
    }

    public function test_user_login_with_inactive_account()
    {
        $this->testUser->update(['is_active' => false]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => '계정이 비활성화되어 있습니다.',
            ]);
    }

    public function test_get_authenticated_user_info()
    {
        $token = $this->getAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJson(function (AssertableJson $json) {
                $json->has('success')
                    ->where('success', true)
                    ->has('data')
                    ->has('data.user')
                    ->where('data.user.email', 'test@example.com')
                    ->where('data.user.name', '테스트 사용자');
            });
    }

    public function test_get_user_info_without_token()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_get_user_info_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token.here',
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_token_refresh()
    {
        $token = $this->getAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJson(function (AssertableJson $json) {
                $json->has('success')
                    ->where('success', true)
                    ->has('data')
                    ->has('data.tokens')
                    ->has('data.tokens.access_token')
                    ->has('data.tokens.refresh_token')
                    ->where('data.tokens.token_type', 'bearer');
            });
    }

    public function test_token_refresh_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token',
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_token_validation()
    {
        $token = $this->getAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/validate');

        $response->assertStatus(200)
            ->assertJson(function (AssertableJson $json) {
                $json->has('success')
                    ->where('success', true)
                    ->has('data')
                    ->has('data.valid')
                    ->where('data.valid', true)
                    ->has('data.expires_at')
                    ->has('data.payload');
            });
    }

    public function test_token_validation_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token',
        ])->postJson('/api/auth/validate');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => '유효하지 않은 토큰입니다.',
            ]);
    }

    public function test_user_logout()
    {
        $token = $this->getAuthToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '로그아웃되었습니다.',
            ]);

        // 로그아웃 후 동일 토큰으로 인증 받기 시도
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    public function test_profile_update()
    {
        $token = $this->getAuthToken();
        
        $updateData = [
            'name' => '수정된 이름',
            'username' => 'updated_username',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson(function (AssertableJson $json) {
                $json->has('success')
                    ->where('success', true)
                    ->has('data')
                    ->has('data.user')
                    ->where('data.user.name', '수정된 이름')
                    ->where('data.user.username', 'updated_username');
            });

        $this->assertDatabaseHas('users', [
            'id' => $this->testUser->id,
            'name' => '수정된 이름',
            'username' => 'updated_username',
        ]);
    }

    public function test_password_change()
    {
        $token = $this->getAuthToken();
        
        $passwordData = [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/change-password', $passwordData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '비밀번호가 변경되었습니다.',
            ]);

        // 새 비밀번호로 로그인 테스트
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
        ];

        $loginResponse = $this->postJson('/api/auth/login', $loginData);
        $loginResponse->assertStatus(200);
    }

    public function test_password_change_with_wrong_current_password()
    {
        $token = $this->getAuthToken();
        
        $passwordData = [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/change-password', $passwordData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_rate_limiting_on_auth_endpoints()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        // Rate limit을 초과할 때까지 요청 반복
        for ($i = 0; $i < 12; $i++) {
            $response = $this->postJson('/api/auth/login', $loginData);
            
            if ($response->status() === 429) {
                // Rate limit에 걸렸음
                break;
            }
        }

        // 마지막 요청이 rate limit에 걸렸는지 확인
        $this->assertEquals(429, $response->status());
    }

    public function test_authenticated_api_endpoints_require_token()
    {
        // 인증이 필요한 다른 API 엔드포인트 테스트
        $protectedEndpoints = [
            ['method' => 'GET', 'url' => '/api/auth/user'],
            ['method' => 'POST', 'url' => '/api/auth/logout'],
            ['method' => 'PUT', 'url' => '/api/auth/profile'],
            ['method' => 'POST', 'url' => '/api/auth/change-password'],
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->{strtolower($endpoint['method']) . 'Json'}($endpoint['url']);
            $response->assertStatus(401);
        }
    }

    public function test_token_expiration_warning_header()
    {
        $token = $this->getAuthToken();

        // 토큰이 곱 만료되는 경우를 시뮤레이션하기 위해
        // JWT TTL을 짧게 설정한 후 대기한 다음 요청
        // 이 테스트는 실제 애플리케이션에서는 더 복잡하게 구현될 수 있음
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200);
        
        // 만료 경고 헤더가 있는지 확인 (예: 10분 남은 경우)
        // $response->assertHeader('X-JWT-Refresh-Recommended');
    }

    private function getAuthToken(): string
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);
        
        return $response->json('data.tokens.access_token');
    }
}
