<?php

namespace App\Http\Requests\Ahhob\Blog\Admin\Page;

use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest
{
    /**
     * 사용자가 이 요청을 할 권한이 있는지 결정합니다.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * 요청에 적용되는 유효성 검사 규칙을 가져옵니다.
     */
    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:pages,slug,' . $pageId,
            ],
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'keywords' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:500',
        ];
    }

    /**
     * 유효성 검사 규칙에 대한 사용자 정의 메시지를 가져옵니다.
     */
    public function messages(): array
    {
        return [
            'title.required' => '제목은 필수 항목입니다.',
            'title.max' => '제목은 255자를 초과할 수 없습니다.',
            'slug.regex' => '슬러그는 영문 소문자, 숫자, 하이픈(-)만 사용할 수 있습니다.',
            'slug.unique' => '이미 사용 중인 슬러그입니다.',
            'content.required' => '내용은 필수 항목입니다.',
            'status.required' => '상태를 선택해주세요.',
            'status.in' => '올바른 상태를 선택해주세요.',
            'published_at.date' => '올바른 날짜 형식을 입력해주세요.',
            'meta_title.max' => '메타 제목은 255자를 초과할 수 없습니다.',
            'meta_description.max' => '메타 설명은 500자를 초과할 수 없습니다.',
            'keywords.max' => '키워드는 500자를 초과할 수 없습니다.',
            'og_title.max' => 'OG 제목은 255자를 초과할 수 없습니다.',
            'og_description.max' => 'OG 설명은 500자를 초과할 수 없습니다.',
            'og_image.max' => 'OG 이미지 URL은 500자를 초과할 수 없습니다.',
            'canonical_url.url' => '올바른 URL 형식을 입력해주세요.',
            'canonical_url.max' => 'Canonical URL은 500자를 초과할 수 없습니다.',
        ];
    }

    /**
     * 유효성 검사를 위해 데이터를 준비합니다.
     */
    protected function prepareForValidation(): void
    {
        // 슬러그가 비어있으면 제목에서 자동 생성
        if (empty($this->slug) && $this->title) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title)
            ]);
        }

        // 발행 상태이지만 발행일이 없으면 현재 시간 설정
        if ($this->status === 'published' && empty($this->published_at)) {
            $this->merge([
                'published_at' => now()->format('Y-m-d\TH:i')
            ]);
        }

        // 임시저장 상태면 발행일 제거
        if ($this->status === 'draft') {
            $this->merge([
                'published_at' => null
            ]);
        }
    }

    /**
     * 유효성 검사 후 데이터를 가공합니다.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // 빈 값들을 null로 변환
        $nullableFields = ['meta_title', 'meta_description', 'keywords', 'og_title', 'og_description', 'og_image', 'canonical_url'];
        
        foreach ($nullableFields as $field) {
            if (isset($validated[$field]) && empty($validated[$field])) {
                $validated[$field] = null;
            }
        }

        return $validated;
    }
}