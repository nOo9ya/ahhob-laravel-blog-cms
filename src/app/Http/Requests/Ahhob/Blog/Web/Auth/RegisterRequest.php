<?php

namespace App\Http\Requests\Ahhob\Blog\Web\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_]+$/',
            ],
            'email' => 'required|email|max:255|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'terms' => 'required|accepted',
        ];
    }

    /**
     * @return string[]|array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => '이름을 입력해주세요.',
            'name.max' => '이름은 100자를 초과할 수 없습니다.',
            'username.required' => '사용자명을 입력해주세요.',
            'username.max' => '사용자명은 50자를 초과할 수 없습니다.',
            'username.unique' => '이미 사용중인 사용자명입니다.',
            'username.regex' => '사용자명은 영문, 숫자, 밑줄(_)만 사용할 수 있습니다.',
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'email.unique' => '이미 등록된 이메일입니다.',
            'password.required' => '비밀번호를 입력해주세요.',
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            'terms.required' => '이용약관에 동의해주세요.',
            'terms.accepted' => '이용약관에 동의해주세요.',
        ];
    }

    /**
     * @return string[]|array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '이름',
            'username' => '사용자명',
            'email' => '이메일',
            'password' => '비밀번호',
            'password_confirmation' => '비밀번호 확인',
            'terms' => '이용약관 동의',
        ];
    }
}
