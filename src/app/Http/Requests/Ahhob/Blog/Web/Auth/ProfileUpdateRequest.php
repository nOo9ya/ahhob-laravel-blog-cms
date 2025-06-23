<?php

namespace App\Http\Requests\Ahhob\Blog\Web\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'name' => 'required|string|max:100',
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($userId),
                'regex:/^[a-zA-Z0-9_]+$/',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'bio' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'social_twitter' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_]+$/',
            'social_github' => 'nullable|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
            'social_linkedin' => 'nullable|string|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'password' => [
                'nullable',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'current_password' => 'required_with:password|current_password',
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
            'username.unique' => '이미 사용중인 사용자명입니다.',
            'username.regex' => '사용자명은 영문, 숫자, 밑줄(_)만 사용할 수 있습니다.',
            'email.required' => '이메일을 입력해주세요.',
            'email.email' => '올바른 이메일 형식을 입력해주세요.',
            'email.unique' => '이미 등록된 이메일입니다.',
            'bio.max' => '소개는 500자를 초과할 수 없습니다.',
            'website.url' => '올바른 웹사이트 URL을 입력해주세요.',
            'social_twitter.regex' => '올바른 트위터 사용자명을 입력해주세요.',
            'social_github.regex' => '올바른 GitHub 사용자명을 입력해주세요.',
            'avatar.image' => '아바타는 이미지 파일이어야 합니다.',
            'avatar.mimes' => '아바타는 jpeg, png, jpg, webp 형식만 가능합니다.',
            'avatar.max' => '아바타 파일 크기는 2MB를 초과할 수 없습니다.',
            'password.confirmed' => '새 비밀번호 확인이 일치하지 않습니다.',
            'current_password.required_with' => '비밀번호 변경 시 현재 비밀번호를 입력해주세요.',
            'current_password.current_password' => '현재 비밀번호가 올바르지 않습니다.',
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
            'bio' => '소개',
            'website' => '웹사이트',
            'social_twitter' => '트위터',
            'social_github' => 'GitHub',
            'social_linkedin' => 'LinkedIn',
            'avatar' => '아바타',
            'password' => '새 비밀번호',
            'current_password' => '현재 비밀번호',
        ];
    }
}
