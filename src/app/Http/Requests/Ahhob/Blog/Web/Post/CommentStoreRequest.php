<?php

namespace App\Http\Requests\Ahhob\Blog\Web\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CommentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return string[]|array<string, string>
     */
    public function rules(): array
    {
        $rules = [
            'post_id' => 'required|exists:posts,id',
            'content' => 'required|string|min:10|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ];

        // 비회원인 경우 추가 정보 필요
        if (!Auth::check()) {
            $rules['author_name'] = 'required|string|max:100';
            $rules['author_email'] = 'required|email|max:255';
            $rules['author_website'] = 'nullable|url|max:255';
        }

        return $rules;
    }

    /**
     * @return string[]|array<string, string>
     */
    public function messages(): array
    {
        return [
            'post_id.required' => '게시물 정보가 필요합니다.',
            'post_id.exists' => '존재하지 않는 게시물입니다.',
            'content.required' => '댓글 내용을 입력해주세요.',
            'content.min' => '댓글은 최소 10자 이상 작성해주세요.',
            'content.max' => '댓글은 2000자를 초과할 수 없습니다.',
            'parent_id.exists' => '존재하지 않는 댓글입니다.',
            'author_name.required' => '이름을 입력해주세요.',
            'author_name.max' => '이름은 100자를 초과할 수 없습니다.',
            'author_email.required' => '이메일을 입력해주세요.',
            'author_email.email' => '올바른 이메일 형식을 입력해주세요.',
            'author_website.url' => '올바른 웹사이트 URL을 입력해주세요.',
        ];
    }

    /**
     * @return string[]|array<string, string>
     */
    public function attributes(): array
    {
        return [
            'post_id' => '게시물',
            'content' => '댓글 내용',
            'parent_id' => '부모 댓글',
            'author_name' => '이름',
            'author_email' => '이메일',
            'author_website' => '웹사이트',
        ];
    }

    /**
     * 유효성 검사를 통과한 후 추가 처리
     */
    public function passedValidation(): void
    {
        // 댓글 내용 정리 (HTML 태그 제거, XSS 방지)
        $this->merge([
            'content' => strip_tags($this->content),
        ]);

        // 부모 댓글이 있는 경우 깊이 제한 확인
        if ($this->parent_id) {
            $parentComment = \App\Models\Blog\Comment::find($this->parent_id);
            if ($parentComment && $parentComment->depth >= 2) {
                $this->failedValidation(
                    validator([], [], [], [
                        'parent_id' => '대댓글의 대댓글은 작성할 수 없습니다.'
                    ])
                );
            }
        }
    }
}
