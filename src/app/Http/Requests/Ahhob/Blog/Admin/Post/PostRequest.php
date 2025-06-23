<?php

namespace App\Http\Requests\Ahhob\Blog\Admin\Post;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PostRequest extends FormRequest
{
    /**
     * 요청에 대한 권한을 확인합니다.
     * 생성(store)과 수정(update) 시나리오를 모두 처리합니다.
     */
    public function authorize(): bool
    {
        // 라우트 모델 바인딩으로 'post' 객체가 넘어오면 수정(update) 요청입니다.
        if ($post = $this->route('post')) {
            return Auth::check() && $post->canBeEditedBy(Auth::user());
        }

        // 'post' 객체가 없으면 생성(store) 요청입니다.
        return Auth::check() && Auth::user()->isWriter();
    }

    /**
     * 유효성 검사 규칙을 가져옵니다.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        // 수정 시에는 해당 post의 id를, 생성 시에는 null을 사용합니다.
        $postId = $this->route('post')?->id;

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                // 수정 시에는 현재 포스트의 slug는 중복 검사에서 제외합니다.
                Rule::unique('posts', 'slug')->ignore($postId),
                'regex:/^[a-z0-9-]+$/',
            ],
            'content' => 'required|string|min:100',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published,archived',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_featured' => 'nullable|boolean',
            'allow_comments' => 'nullable|boolean',
            // 생성 시에만 'after_or_equal' 규칙을 적용합니다.
            'published_at' => $this->isMethod('post')
                ? 'nullable|date|after_or_equal:now'
                : 'nullable|date',

            // SEO 정보
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'og_title' => 'nullable|string|max:60',
            'og_description' => 'nullable|string|max:200',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'meta_keywords' => 'nullable|array|max:10',
            'meta_keywords.*' => 'string|max:50',
            'canonical_url' => 'nullable|url|max:255',
            'index_follow' => 'nullable|boolean',

            // 태그
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
        ];
    }

    /**
     * 유효성 검사 메시지를 정의합니다.
     *
     * @return string[]|array<string, string>
     */
    public function messages(): array
    {
        // 두 파일의 메시지를 모두 포함합니다.
        return [
            'title.required' => '제목을 입력해주세요.',
            'title.max' => '제목은 255자를 초과할 수 없습니다.',
            'slug.unique' => '이미 사용중인 슬러그입니다.',
            'slug.regex' => '슬러그는 소문자, 숫자, 하이픈(-)만 사용할 수 있습니다.',
            'content.required' => '내용을 입력해주세요.',
            'content.min' => '내용은 최소 100자 이상 작성해주세요.',
            'category_id.required' => '카테고리를 선택해주세요.',
            'category_id.exists' => '존재하지 않는 카테고리입니다.',
            'status.required' => '발행 상태를 선택해주세요.',
            'status.in' => '올바른 발행 상태를 선택해주세요.',
            'featured_image.image' => '대표 이미지는 이미지 파일이어야 합니다.',
            'featured_image.mimes' => '대표 이미지는 jpeg, png, jpg, webp 형식만 가능합니다.',
            'featured_image.max' => '대표 이미지 크기는 5MB를 초과할 수 없습니다.',
            'published_at.after_or_equal' => '발행일은 현재 시간 이후로 설정해주세요.',
            'meta_title.max' => 'SEO 제목은 60자를 초과할 수 없습니다.',
            'meta_description.max' => 'SEO 설명은 160자를 초과할 수 없습니다.',
            'og_title.max' => 'OG 제목은 60자를 초과할 수 없습니다.',
            'og_description.max' => 'OG 설명은 200자를 초과할 수 없습니다.',
            'canonical_url.url' => '올바른 URL을 입력해주세요.',
            'tags.max' => '태그는 최대 20개까지 추가할 수 있습니다.',
        ];
    }

    /**
     * 유효성 검사 속성 이름을 정의합니다.
     *
     * @return string[]|array<string, string>
     */
    public function attributes(): array
    {
        // 두 파일의 속성을 모두 포함합니다.
        return [
            'title' => '제목',
            'slug' => '슬러그',
            'content' => '내용',
            'excerpt' => '발췌문',
            'category_id' => '카테고리',
            'status' => '발행 상태',
            'featured_image' => '대표 이미지',
            'is_featured' => '추천 글',
            'allow_comments' => '댓글 허용',
            'published_at' => '발행일',
            'meta_title' => 'SEO 제목',
            'meta_description' => 'SEO 설명',
            'og_title' => 'OG 제목',
            'og_description' => 'OG 설명',
            'og_image' => 'OG 이미지',
            'meta_keywords' => 'SEO 키워드',
            'canonical_url' => '정규 URL',
            'index_follow' => '검색엔진 색인',
            'tags' => '태그',
        ];
    }

    /**
     * 유효성 검사를 통과한 후 추가 처리를 합니다.
     */
    protected function passedValidation(): void
    {
        // Boolean 값들의 기본값을 설정합니다 (공통 로직).
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'allow_comments' => $this->boolean('allow_comments', true),
            'index_follow' => $this->boolean('index_follow', true),
        ]);

        // 생성(store) 요청일 경우의 추가 처리
        if ($this->isMethod('post')) {
            // 슬러그가 없으면 제목으로 자동 생성
            if (!$this->slug && $this->title) {
                $this->merge(['slug' => Str::slug($this->title)]);
            }
            // 발행 상태가 'published'이고 발행일이 없으면 현재 시간으로 설정
            if ($this->status === 'published' && !$this->published_at) {
                $this->merge(['published_at' => now()]);
            }
        }

        // 수정(update) 요청일 경우의 추가 처리
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $post = $this->route('post');
            // 발행 상태가 'published'로 변경되었고 발행일이 없으면 현재 시간으로 설정
            if ($this->status === 'published' && $post->status !== 'published' && !$this->published_at) {
                $this->merge(['published_at' => now()]);
            }
        }
    }
}
