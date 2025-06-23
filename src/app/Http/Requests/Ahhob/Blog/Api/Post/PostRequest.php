<?php

namespace App\Http\Requests\Ahhob\Blog\Api\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // Str 헬퍼 함수를 사용하기 위해 추가
use App\Models\Blog\Post; // Post 모델을 사용하기 위해 추가 (update 로직에서 필요)

class PostRequest extends FormRequest
{
    /**
     * [기존 로직]
     * 요청에 대한 권한을 확인합니다.
     * API 요청이므로 인증된 사용자(Auth::check())가 'writer' 권한을 가지고 있는지 확인합니다.
     * 이 로직은 생성(store)과 수정(update) 모두에 동일하게 적용됩니다.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isWriter();
    }

    /**
     * [기존 로직 및 개선]
     * 유효성 검사 규칙을 정의합니다.
     *
     * 1. 'slug' 규칙:
     *    - $postId를 사용하여 현재 라우트에 'post' 모델이 바인딩되어 있는지 확인합니다.
     *    - 수정(PUT/PATCH) 요청 시에는 해당 post의 ID를 `ignore()`하여 자기 자신의 슬러그는 중복 검사에서 제외합니다.
     *    - 생성(POST) 요청 시에는 $postId가 null 이므로 모든 슬러그에 대해 중복 검사를 수행합니다.
     *
     * 2. 'published_at' 규칙:
     *    - `isMethod('post')`를 사용하여 생성 요청일 때만 `after_or_equal:now` 규칙을 적용합니다.
     *    - 수정 요청일 때는 단순히 `nullable|date`로만 검사합니다.
     *
     * 3. 'featured_image' 규칙 개선:
     *    - 기존 'nullable|string|max:255'에서 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120'으로 변경합니다.
     *    - 이는 'og_image'와 일관성을 유지하고, 대표 이미지가 파일 업로드임을 명확히 하기 위함입니다.
     */
    public function rules(): array
    {
        $postId = $this->route('post')?->id;

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts','slug')->ignore($postId),
                'regex:/^[a-z0-9-]+$/'
            ],
            'content' => 'required|string|min:100',
            'excerpt' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:draft,published,archived',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 규칙 변경
            'is_featured' => 'nullable|boolean',
            'allow_comments' => 'nullable|boolean',
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

            // tag
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
        ];
    }

    /**
     * [기존 로직 및 개선]
     * 유효성 검사 메시지를 정의합니다.
     * 'featured_image' 규칙 변경에 따른 메시지를 추가합니다.
     */
    public function messages(): array
    {
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
            'featured_image.image' => '대표 이미지는 이미지 파일이어야 합니다.', // 메시지 추가
            'featured_image.mimes' => '대표 이미지는 jpeg, png, jpg, webp 형식만 가능합니다.', // 메시지 추가
            'featured_image.max' => '대표 이미지 크기는 5MB를 초과할 수 없습니다.', // 메시지 추가
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
     * [기존 로직]
     * 유효성 검사 속성 이름을 정의합니다.
     */
    public function attributes(): array
    {
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
     * [추가 로직]
     * 유효성 검사를 통과한 후 추가 처리를 합니다.
     * 이 메서드는 생성(store)과 수정(update) 요청에 따라 다른 로직을 적용합니다.
     */
    protected function passedValidation(): void
    {
        // [공통 로직]
        // Boolean 값들의 기본값을 설정합니다.
        // 요청에 해당 필드가 없거나 유효하지 않은 값이 넘어올 경우 기본값을 적용합니다.
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            // 기본값 true
            'allow_comments' => $this->boolean('allow_comments', true),
            // 기본값 true
            'index_follow' => $this->boolean('index_follow', true),
        ]);

        // [생성(store) 요청일 경우의 추가 처리]
        // HTTP POST 메서드로 요청이 들어왔을 때만 실행됩니다.
        if ($this->isMethod('post')) {
            // 슬러그가 요청에 포함되지 않았거나 비어있을 경우, 제목을 기반으로 자동 생성합니다.
            if (!$this->slug && $this->title) {
                $this->merge(['slug' => Str::slug($this->title)]);
            }
            // 발행 상태가 'published' 이고 발행일이 명시되지 않았을 경우, 현재 시간으로 설정합니다.
            if ($this->status === 'published' && !$this->published_at) {
                $this->merge(['published_at' => now()]);
            }
        }

        // [수정(update) 요청일 경우의 추가 처리]
        // HTTP PUT 또는 PATCH 메서드로 요청이 들어왔을 때만 실행됩니다.
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            // 라우트에서 바인딩된 Post 모델 인스턴스를 가져옵니다.
            // 이 인스턴스는 현재 수정하려는 게시물의 기존 상태를 나타냅니다.
            $post = $this->route('post');

            // 발행 상태가 'published' 로 변경되었고, 기존 상태가 'published' 가 아니었으며,
            // 발행일이 명시되지 않았을 경우, 현재 시간으로 설정합니다.
            // 이는 초안(draft)이나 보관(archived) 상태에서 발행(published) 상태로 변경될 때 발행일을 자동으로 기록하기 위함입니다.
            if ($this->status === 'published' && $post && $post->status !== 'published' && !$this->published_at) {
                $this->merge(['published_at' => now()]);
            }
        }
    }

    /**
     * [기존 로직]
     * 유효성 검사 실패 시 JSON 응답을 반환합니다.
     * API 요청에 적합한 형태로 오류 메시지를 반환합니다.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
