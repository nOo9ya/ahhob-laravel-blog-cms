<?php

namespace App\Traits\Blog;

use Illuminate\Support\Str;

/**
 * 요청 유효성 검사 트레이트
 * 
 * 이 트레이트는 Form Request 클래스들에서 공통적으로 사용되는
 * 유효성 검사 로직을 중앙화합니다.
 * 
 * 주요 기능:
 * - 공통 유효성 검사 규칙
 * - 슬러그 자동 생성
 * - 파일 업로드 검증
 * - 한국어 에러 메시지
 * 
 * 사용법:
 * class PostRequest extends FormRequest {
 *     use RequestValidationTrait;
 *     
 *     public function rules() {
 *         return array_merge($this->getCommonRules(), [
 *             'title' => 'required|string|max:255',
 *         ]);
 *     }
 * }
 */
trait RequestValidationTrait
{
    /**
     * 공통 유효성 검사 규칙
     * 
     * @return array 공통 규칙 배열
     */
    protected function getCommonRules(): array
    {
        return [
            // 기본 메타데이터
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'canonical_url' => 'nullable|url|max:255',
            'index_follow' => 'nullable|boolean',
            
            // Open Graph
            'og_title' => 'nullable|string|max:60',
            'og_description' => 'nullable|string|max:200',
            'og_type' => 'nullable|string|in:article,website',
            
            // 공통 불린 필드
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'allow_comments' => 'nullable|boolean',
        ];
    }

    /**
     * 이미지 업로드 유효성 검사 규칙
     * 
     * @param int $maxSize 최대 크기 (KB)
     * @param array $allowedTypes 허용된 파일 형식
     * @return array 이미지 검증 규칙
     */
    protected function getImageRules(int $maxSize = 5120, array $allowedTypes = ['jpg', 'jpeg', 'png', 'webp']): array
    {
        return [
            'required',
            'file',
            'image',
            "max:{$maxSize}",
            'mimes:' . implode(',', $allowedTypes)
        ];
    }

    /**
     * 선택적 이미지 업로드 유효성 검사 규칙
     * 
     * @param int $maxSize 최대 크기 (KB)
     * @param array $allowedTypes 허용된 파일 형식
     * @return array 선택적 이미지 검증 규칙
     */
    protected function getOptionalImageRules(int $maxSize = 5120, array $allowedTypes = ['jpg', 'jpeg', 'png', 'webp']): array
    {
        return [
            'nullable',
            'file',
            'image',
            "max:{$maxSize}",
            'mimes:' . implode(',', $allowedTypes)
        ];
    }

    /**
     * 슬러그 유효성 검사 규칙
     * 
     * @param string $table 테이블명
     * @param int|null $excludeId 제외할 ID (수정 시)
     * @return array 슬러그 검증 규칙
     */
    protected function getSlugRules(string $table, ?int $excludeId = null): array
    {
        $rules = [
            'nullable',
            'string',
            'max:255',
            'regex:/^[a-z0-9-]+$/',
        ];

        if ($excludeId) {
            $rules[] = "unique:{$table},slug,{$excludeId}";
        } else {
            $rules[] = "unique:{$table},slug";
        }

        return $rules;
    }

    /**
     * 태그 유효성 검사 규칙
     * 
     * @param int $maxTags 최대 태그 수
     * @param int $maxTagLength 최대 태그 길이
     * @return array 태그 검증 규칙
     */
    protected function getTagRules(int $maxTags = 20, int $maxTagLength = 50): array
    {
        return [
            'tags' => "nullable|array|max:{$maxTags}",
            'tags.*' => "string|max:{$maxTagLength}",
        ];
    }

    /**
     * 공통 한국어 에러 메시지
     * 
     * @return array 에러 메시지 배열
     */
    protected function getCommonMessages(): array
    {
        return [
            'required' => ':attribute을(를) 입력해주세요.',
            'string' => ':attribute은(는) 문자열이어야 합니다.',
            'max' => ':attribute은(는) :max자를 초과할 수 없습니다.',
            'min' => ':attribute은(는) 최소 :min자 이상이어야 합니다.',
            'email' => '올바른 이메일 주소를 입력해주세요.',
            'url' => '올바른 URL을 입력해주세요.',
            'unique' => '이미 사용중인 :attribute입니다.',
            'exists' => '존재하지 않는 :attribute입니다.',
            'in' => '올바른 :attribute을(를) 선택해주세요.',
            'boolean' => ':attribute은(는) true 또는 false여야 합니다.',
            'integer' => ':attribute은(는) 정수여야 합니다.',
            'numeric' => ':attribute은(는) 숫자여야 합니다.',
            'array' => ':attribute은(는) 배열이어야 합니다.',
            'file' => ':attribute은(는) 파일이어야 합니다.',
            'image' => ':attribute은(는) 이미지 파일이어야 합니다.',
            'mimes' => ':attribute은(는) :values 형식만 가능합니다.',
            'regex' => ':attribute 형식이 올바르지 않습니다.',
            'after_or_equal' => ':attribute은(는) :date 이후여야 합니다.',
            'date' => ':attribute은(는) 올바른 날짜여야 합니다.',
        ];
    }

    /**
     * 공통 필드 속성 이름
     * 
     * @return array 속성 이름 배열
     */
    protected function getCommonAttributes(): array
    {
        return [
            'title' => '제목',
            'slug' => '슬러그',
            'content' => '내용',
            'excerpt' => '발췌문',
            'description' => '설명',
            'name' => '이름',
            'email' => '이메일',
            'password' => '비밀번호',
            'password_confirmation' => '비밀번호 확인',
            'status' => '상태',
            'is_active' => '활성 상태',
            'is_featured' => '추천',
            'allow_comments' => '댓글 허용',
            'category_id' => '카테고리',
            'tags' => '태그',
            'featured_image' => '대표 이미지',
            'published_at' => '발행일',
            'created_at' => '생성일',
            'updated_at' => '수정일',
            'meta_title' => 'SEO 제목',
            'meta_description' => 'SEO 설명',
            'meta_keywords' => 'SEO 키워드',
            'canonical_url' => '정규 URL',
            'index_follow' => '검색엔진 색인',
            'og_title' => 'OG 제목',
            'og_description' => 'OG 설명',
            'og_image' => 'OG 이미지',
            'og_type' => 'OG 타입',
        ];
    }

    /**
     * 입력 데이터 전처리 (유효성 검사 통과 후)
     * 
     * @param array $data 입력 데이터
     * @return array 전처리된 데이터
     */
    protected function preprocessValidatedData(array $data): array
    {
        // 불린 값 정규화
        $booleanFields = ['is_active', 'is_featured', 'allow_comments', 'index_follow'];
        foreach ($booleanFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = (bool) $data[$field];
            }
        }

        // 빈 문자열을 null로 변환
        $nullableFields = ['meta_title', 'meta_description', 'canonical_url', 'og_title', 'og_description', 'excerpt'];
        foreach ($nullableFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        // 슬러그 자동 생성 (제목이 있고 슬러그가 없는 경우)
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // 태그 정리 (문자열인 경우 배열로 변환)
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_filter(array_map('trim', explode(',', $data['tags'])));
        }

        return $data;
    }

    /**
     * 파일 크기 제한 메시지 생성
     * 
     * @param int $maxSizeKB 최대 크기 (KB)
     * @return string 크기 제한 메시지
     */
    protected function getFileSizeMessage(int $maxSizeKB): string
    {
        if ($maxSizeKB >= 1024) {
            $sizeMB = round($maxSizeKB / 1024, 1);
            return "파일 크기는 {$sizeMB}MB를 초과할 수 없습니다.";
        }

        return "파일 크기는 {$maxSizeKB}KB를 초과할 수 없습니다.";
    }

    /**
     * 현재 요청이 생성 요청인지 확인
     * 
     * @return bool
     */
    protected function isCreateRequest(): bool
    {
        return $this->isMethod('post') && !$this->route()->hasParameter('id');
    }

    /**
     * 현재 요청이 수정 요청인지 확인
     * 
     * @return bool
     */
    protected function isUpdateRequest(): bool
    {
        return in_array($this->method(), ['PUT', 'PATCH']) || 
               ($this->isMethod('post') && $this->route()->hasParameter('id'));
    }
}