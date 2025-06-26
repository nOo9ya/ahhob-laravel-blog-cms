<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 회원가입 요청 검증
 * 
 * JWT 기반 API 회원가입을 위한 요청 검증 클래스입니다.
 */
class RegisterRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'username' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('users', 'username'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$/',
            ],
            'role' => [
                'nullable',
                'string',
                Rule::in(['user', 'writer']), // admin은 직접 등록 불가
            ],
            'bio' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'website' => [
                'nullable',
                'url',
                'max:255',
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
            'name.required' => '이름을 입력해주세요.',
            'name.string' => '이름은 문자열이어야 합니다.',
            'name.max' => '이름은 255자를 초과할 수 없습니다.',
            
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'email.max' => '이메일은 255자를 초과할 수 없습니다.',
            'email.unique' => '이미 사용 중인 이메일입니다.',
            
            'username.string' => '사용자명은 문자열이어야 합니다.',
            'username.max' => '사용자명은 50자를 초과할 수 없습니다.',
            'username.regex' => '사용자명은 영문, 숫자, 언더스코어, 하이픈만 사용 가능합니다.',
            'username.unique' => '이미 사용 중인 사용자명입니다.',
            
            'password.required' => '비밀번호를 입력해주세요.',
            'password.string' => '비밀번호는 문자열이어야 합니다.',
            'password.min' => '비밀번호는 최소 8자 이상이어야 합니다.',
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            'password.regex' => '비밀번호는 대문자, 소문자, 숫자를 각각 최소 1개씩 포함해야 합니다.',
            
            'role.string' => '역할은 문자열이어야 합니다.',
            'role.in' => '올바른 역할을 선택해주세요.',
            
            'bio.string' => '소개는 문자열이어야 합니다.',
            'bio.max' => '소개는 1000자를 초과할 수 없습니다.',
            
            'website.url' => '올바른 웹사이트 URL을 입력해주세요.',
            'website.max' => '웹사이트 URL은 255자를 초과할 수 없습니다.',
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
            'name' => '이름',
            'email' => '이메일',
            'username' => '사용자명',
            'password' => '비밀번호',
            'role' => '역할',
            'bio' => '소개',
            'website' => '웹사이트',
        ];
    }

    /**
     * 데이터 정리 및 변환
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // 기본 역할 설정
        if (!$this->has('role')) {
            $this->merge(['role' => 'user']);
        }

        // username이 없으면 email의 로컬 부분을 사용
        if (!$this->has('username') && $this->has('email')) {
            $emailLocal = explode('@', $this->email)[0];
            $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $emailLocal);
            
            // 중복 확인 후 숫자 추가
            $originalUsername = $username;
            $counter = 1;
            while (\App\Models\User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }
            
            $this->merge(['username' => $username]);
        }
    }
}