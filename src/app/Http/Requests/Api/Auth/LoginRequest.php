<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 로그인 요청 검증
 * 
 * JWT 기반 API 로그인을 위한 요청 검증 클래스입니다.
 */
class LoginRequest extends FormRequest
{
    /**
     * 요청 권한 확인
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 유효성 검증 규칙
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
        ];
    }

    /**
     * 커스텀 에러 메시지
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'email.max' => '이메일은 255자를 초과할 수 없습니다.',
            'password.required' => '비밀번호를 입력해주세요.',
            'password.string' => '비밀번호는 문자열이어야 합니다.',
            'password.min' => '비밀번호는 최소 6자 이상이어야 합니다.',
        ];
    }

    /**
     * 필드 속성명
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => '이메일',
            'password' => '비밀번호',
        ];
    }
}