<?php

namespace App\Http\Requests\Ahhob\Blog\Web\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'remember' => 'nullable|boolean',
        ];
    }

    /**
     * @return string[]|array<string, string>
     */
    public function message(): array
    {
        return [
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'email.max' => '이메일은 255자를 초과할 수 없습니다.',
            'password.required' => '비밀번호를 입력해주세요.',
            'password.min' => '비밀번호는 최소 6자 이상이어야 합니다.',
        ];
    }

    /**
     * @return string[]|array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => '이메일',
            'password' => '비밀번호',
            'remember' => '로그인 상태 유지',
        ];
    }
}
