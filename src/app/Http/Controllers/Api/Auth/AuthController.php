<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Services\Ahhob\Blog\Shared\Auth\JwtService;
use App\Traits\Ahhob\Blog\ControllerResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API 인증 컨트롤러
 * 
 * JWT 기반 API 인증을 처리합니다.
 * Sanctum에서 JWT로 마이그레이션하여 다중 플랫폼 호환성을 제공합니다.
 */
class AuthController extends Controller
{
    use ControllerResponseTrait;

    public function __construct(
        private JwtService $jwtService
    ) {}

    /**
     * 사용자 등록
     * 
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->jwtService->register($request->validated());

            Log::info('User registered successfully', [
                'user_id' => $result['user']->id,
                'email' => $result['user']->email,
            ]);

            return $this->createdResponse([
                'user' => $result['user'],
                'tokens' => [
                    'access_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ], '회원가입이 완료되었습니다.');

        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('회원가입에 실패했습니다.', 400);
        }
    }

    /**
     * 로그인
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->jwtService->authenticate(
                $request->email,
                $request->password
            );

            if (!$result) {
                return $this->errorResponse('이메일 또는 비밀번호가 올바르지 않습니다.', 401);
            }

            Log::info('User logged in successfully', [
                'user_id' => $result['user']->id,
                'email' => $result['user']->email,
            ]);

            return $this->successResponse([
                'user' => $result['user'],
                'tokens' => [
                    'access_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ], '로그인되었습니다.');

        } catch (\Exception $e) {
            Log::error('User login failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * 로그아웃
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();
            $this->jwtService->invalidateToken($token);

            Log::info('User logged out successfully', [
                'user_id' => auth()->id(),
            ]);

            return $this->successResponse(null, '로그아웃되었습니다.');

        } catch (\Exception $e) {
            Log::error('User logout failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('로그아웃에 실패했습니다.', 400);
        }
    }

    /**
     * 현재 사용자 정보 조회
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse('인증되지 않은 사용자입니다.');
            }

            return $this->successResponse($user, '사용자 정보를 조회했습니다.');

        } catch (\Exception $e) {
            Log::error('Failed to get user info', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('사용자 정보 조회에 실패했습니다.', 400);
        }
    }

    /**
     * 토큰 갱신
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return $this->errorResponse('토큰이 필요합니다.', 400);
            }

            $result = $this->jwtService->refreshToken($token);

            Log::info('Token refreshed successfully', [
                'user_id' => auth()->id(),
            ]);

            return $this->successResponse([
                'tokens' => $result,
            ], '토큰이 갱신되었습니다.');

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * 프로필 업데이트
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:1000',
            'website' => 'sometimes|url|max:255',
            'avatar' => 'sometimes|image|max:1024',
        ]);

        try {
            $user = auth()->user();
            $data = $request->only(['name', 'bio', 'website']);

            // 아바타 파일 처리
            if ($request->hasFile('avatar')) {
                // TODO: 이미지 업로드 서비스 사용
                // $data['avatar'] = $this->imageService->upload($request->file('avatar'));
            }

            $user->update($data);

            Log::info('User profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($data),
            ]);

            return $this->successResponse($user, '프로필이 업데이트되었습니다.');

        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('프로필 업데이트에 실패했습니다.', 400);
        }
    }

    /**
     * 비밀번호 변경
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $user = auth()->user();

            if (!\Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('현재 비밀번호가 올바르지 않습니다.', 400);
            }

            $user->update([
                'password' => \Hash::make($request->new_password),
            ]);

            // 기존 토큰들 무효화
            $this->jwtService->invalidateAllUserTokens($user);

            Log::info('Password changed successfully', [
                'user_id' => $user->id,
            ]);

            return $this->successResponse(null, '비밀번호가 변경되었습니다. 다시 로그인해주세요.');

        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('비밀번호 변경에 실패했습니다.', 400);
        }
    }

    /**
     * 토큰 유효성 검증
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return $this->errorResponse('토큰이 필요합니다.', 400);
            }

            $isValid = $this->jwtService->validateToken($token);

            if (!$isValid) {
                return $this->errorResponse('유효하지 않은 토큰입니다.', 401);
            }

            $payload = $this->jwtService->getTokenPayload($token);
            $expiration = $this->jwtService->getTokenExpiration($token);

            return $this->successResponse([
                'valid' => true,
                'expires_at' => $expiration->toISOString(),
                'payload' => [
                    'user_id' => $payload['sub'],
                    'issued_at' => \Carbon\Carbon::createFromTimestamp($payload['iat'])->toISOString(),
                    'expires_at' => \Carbon\Carbon::createFromTimestamp($payload['exp'])->toISOString(),
                ],
            ], '토큰이 유효합니다.');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }
}