<?php

namespace App\Traits\Ahhob\Blog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 요청 유효성 검증 공통 기능 트레이트
 * 
 * 컨트롤러에서 자주 사용되는 유효성 검증 로직을 재사용 가능하게 만듭니다.
 * 블로그 시스템에 특화된 검증 규칙들을 제공합니다.
 */
trait RequestValidationTrait
{
    /**
     * 게시물 생성/수정 유효성 검증 규칙
     * 
     * @param int|null $postId 업데이트 시 제외할 ID
     * @return array
     */
    protected function getPostValidationRules(?int $postId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('posts')->ignore($postId),
            ],
            'status' => ['required', 'in:draft,published,archived'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'og_title' => ['nullable', 'string', 'max:60'],
            'og_description' => ['nullable', 'string', 'max:160'],
            'og_image' => ['nullable', 'image', 'max:2048'],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'allow_comments' => ['boolean'],
        ];
    }

    /**
     * 카테고리 생성/수정 유효성 검증 규칙
     * 
     * @param int|null $categoryId 업데이트 시 제외할 ID
     * @return array
     */
    protected function getCategoryValidationRules(?int $categoryId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('categories')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * 댓글 생성/수정 유효성 검증 규칙
     * 
     * @return array
     */
    protected function getCommentValidationRules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1000'],
            'author_name' => ['required_without:user_id', 'string', 'max:100'],
            'author_email' => ['required_without:user_id', 'email', 'max:255'],
            'author_url' => ['nullable', 'url', 'max:255'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ];
    }

    /**
     * 태그 생성/수정 유효성 검증 규칙
     * 
     * @param int|null $tagId 업데이트 시 제외할 ID
     * @return array
     */
    protected function getTagValidationRules(?int $tagId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'slug' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tags')->ignore($tagId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ];
    }

    /**
     * 페이지 생성/수정 유효성 검증 규칙
     * 
     * @param int|null $pageId 업데이트 시 제외할 ID
     * @return array
     */
    protected function getPageValidationRules(?int $pageId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('pages')->ignore($pageId),
            ],
            'status' => ['required', 'in:draft,published'],
            'template' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'is_in_menu' => ['boolean'],
            'menu_order' => ['integer', 'min:0'],
        ];
    }

    /**
     * 이미지 업로드 유효성 검증 규칙
     * 
     * @param int $maxSize MB 단위
     * @return array
     */
    protected function getImageValidationRules(int $maxSize = 2): array
    {
        $maxSizeKb = $maxSize * 1024;
        
        return [
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                "max:{$maxSizeKb}",
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * 사용자 프로필 유효성 검증 규칙
     * 
     * @param int|null $userId 업데이트 시 제외할 ID
     * @return array
     */
    protected function getUserProfileValidationRules(?int $userId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:1024'],
            'social_links' => ['nullable', 'array'],
            'social_links.twitter' => ['nullable', 'url'],
            'social_links.facebook' => ['nullable', 'url'],
            'social_links.instagram' => ['nullable', 'url'],
            'social_links.linkedin' => ['nullable', 'url'],
        ];
    }

    /**
     * 검색 파라미터 유효성 검증 규칙
     * 
     * @return array
     */
    protected function getSearchValidationRules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'tag' => ['nullable', 'string', 'exists:tags,slug'],
            'author' => ['nullable', 'integer', 'exists:users,id'],
            'sort' => ['nullable', 'in:created_at,updated_at,title,published_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * 유효성 검증 실행
     * 
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @return \Illuminate\Validation\Validator
     */
    protected function validateRequest(
        Request $request, 
        array $rules, 
        array $messages = [], 
        array $attributes = []
    ): \Illuminate\Validation\Validator {
        return Validator::make($request->all(), $rules, $messages, $attributes);
    }

    /**
     * 커스텀 유효성 검증 메시지 (한국어)
     * 
     * @return array
     */
    protected function getCustomValidationMessages(): array
    {
        return [
            'required' => ':attribute 필드는 필수입니다.',
            'string' => ':attribute 필드는 문자열이어야 합니다.',
            'max' => ':attribute 필드는 :max자를 초과할 수 없습니다.',
            'min' => ':attribute 필드는 최소 :min자 이상이어야 합니다.',
            'email' => ':attribute 필드는 유효한 이메일 주소여야 합니다.',
            'unique' => ':attribute 필드는 이미 사용 중입니다.',
            'exists' => '선택된 :attribute이(가) 유효하지 않습니다.',
            'image' => ':attribute 필드는 이미지 파일이어야 합니다.',
            'mimes' => ':attribute 필드는 :values 형식의 파일이어야 합니다.',
            'dimensions' => ':attribute 이미지 크기가 요구사항에 맞지 않습니다.',
            'url' => ':attribute 필드는 유효한 URL이어야 합니다.',
            'regex' => ':attribute 필드 형식이 올바르지 않습니다.',
            'in' => '선택된 :attribute이(가) 유효하지 않습니다.',
            'boolean' => ':attribute 필드는 true 또는 false여야 합니다.',
            'integer' => ':attribute 필드는 정수여야 합니다.',
            'date' => ':attribute 필드는 유효한 날짜여야 합니다.',
            'after_or_equal' => ':attribute 필드는 :date 이후 날짜여야 합니다.',
        ];
    }

    /**
     * 필드 속성명 (한국어)
     * 
     * @return array
     */
    protected function getValidationAttributes(): array
    {
        return [
            'title' => '제목',
            'content' => '내용',
            'excerpt' => '요약',
            'slug' => '슬러그',
            'status' => '상태',
            'category_id' => '카테고리',
            'tags' => '태그',
            'featured_image' => '대표 이미지',
            'meta_title' => '메타 제목',
            'meta_description' => '메타 설명',
            'og_title' => 'OG 제목',
            'og_description' => 'OG 설명', 
            'og_image' => 'OG 이미지',
            'published_at' => '발행일',
            'name' => '이름',
            'description' => '설명',
            'parent_id' => '상위 카테고리',
            'sort_order' => '정렬 순서',
            'author_name' => '작성자명',
            'author_email' => '작성자 이메일',
            'author_url' => '작성자 웹사이트',
            'email' => '이메일',
            'bio' => '소개',
            'website' => '웹사이트',
            'avatar' => '프로필 이미지',
            'image' => '이미지',
            'alt_text' => '대체 텍스트',
            'caption' => '캡션',
        ];
    }
}