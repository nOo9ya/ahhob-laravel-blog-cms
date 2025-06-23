<?php

namespace App\Http\Requests\Ahhob\Blog\Api\Post;

use App\Models\Blog\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CategoryStoreRequest와 CategoryUpdateRequest를 병합한 통합 Form Request 클래스입니다.
 * 생성(store)과 수정(update) 요청을 모두 처리합니다.
 */
class CategoryRequest extends FormRequest
{
    /**
     * [병합 로직]
     * 생성/수정 요청에 대한 권한 부여 로직은 두 파일에서 동일했습니다.
     * 따라서, 기존 로직을 그대로 사용합니다.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isWriter();
    }

    /**
     * [병합 로직]
     * 생성과 수정 시나리오에 따라 동적으로 유효성 검사 규칙을 적용합니다.
     *
     * 1. $categoryId 변수:
     *    - 라우트 모델 바인딩을 통해 'category' 객체가 있는지 확인합니다.
     *    - 수정 요청(`update`)일 경우: 해당 카테고리의 ID가 할당됩니다.
     *    - 생성 요청(`store`)일 경우: null이 할당됩니다.
     *
     * 2. 'slug' 규칙:
     *    - Rule::unique('categories', 'slug')->ignore($categoryId)
     *    - 수정 시에는 $categoryId에 값이 있으므로, 자기 자신의 slug는 중복 검사에서 제외됩니다.
     *    - 생성 시에는 $categoryId가 null이므로, 모든 slug에 대해 중복 검사를 수행합니다.
     *
     * 3. 'parent_id' 규칙:
     *    - Rule::notIn([$categoryId])
     *    - 수정 시에만 자기 자신을 부모 카테고리로 지정하는 것을 방지하는 규칙이 적용됩니다.
     *    - 생성 시에는 이 규칙이 적용되지 않습니다.
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => 'required|string|max:100',
            'slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('categories', 'slug')->ignore($categoryId),
                'regex:/^[a-z0-9-]+$/',
            ],
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            'icon' => 'nullable|string|max:50',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                Rule::notIn([$categoryId]), // 자기 자신을 부모로 설정 불가 (수정 시에만 유효)
            ],
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ];
    }

    /**
     * [병합 로직]
     * 두 파일의 모든 유효성 검사 메시지를 하나로 합칩니다.
     * CategoryUpdateRequest 에만 있던 'parent_id.not_in' 메시지를 포함시킵니다.
     */
    public function messages(): array
    {
        return [
            'name.required' => '카테고리명을 입력해주세요.',
            'name.max' => '카테고리명은 100자를 초과할 수 없습니다.',
            'slug.unique' => '이미 사용중인 슬러그입니다.',
            'slug.regex' => '슬러그는 소문자, 숫자, 하이픈(-)만 사용할 수 있습니다.',
            'description.max' => '설명은 500자를 초과할 수 없습니다.',
            'color.regex' => '올바른 HEX 색상 코드를 입력해주세요. (예: #FF0000)',
            'icon.max' => '아이콘명은 50자를 초과할 수 없습니다.',
            'parent_id.exists' => '존재하지 않는 부모 카테고리입니다.',
            'parent_id.not_in' => '자기 자신을 부모 카테고리로 설정할 수 없습니다.',
            'sort_order.integer' => '정렬 순서는 숫자여야 합니다.',
            'sort_order.min' => '정렬 순서는 0 이상이어야 합니다.',
            'sort_order.max' => '정렬 순서는 999 이하여야 합니다.',
        ];
    }

    /**
     * [병합 로직]
     * CategoryStoreRequest 에만 정의되어 있던 attributes() 메소드를 그대로 사용합니다.
     * 이 메소드는 모든 필드에 대한 한글 이름을 제공하므로 병합 후에도 유용합니다.
     */
    public function attributes(): array
    {
        return [
            'name' => '카테고리명',
            'slug' => '슬러그',
            'description' => '설명',
            'color' => '색상',
            'icon' => '아이콘',
            'parent_id' => '부모 카테고리',
            'is_active' => '활성화',
            'sort_order' => '정렬 순서',
        ];
    }

    /**
     * [병합 로직]
     * 유효성 검사 통과 후 데이터를 가공하는 로직을 병합하고 개선합니다.
     *
     * 1. 생성(POST) 요청 전용 로직:
     *    - $this->isMethod('post')를 사용하여 생성 요청일 때만 실행되도록 분기합니다.
     *    - 슬러그가 비어있을 경우 이름(name)을 기반으로 자동 생성합니다.
     *    - is_active, sort_order, color 필드의 기본값을 설정합니다. (수정 시에는 기존 값을 유지해야 하므로 실행되지 않음)
     *
     * 2. 공통 로직 (카테고리 깊이 제한):
     *    - parent_id가 요청에 포함된 경우, 부모 카테고리의 깊이를 확인합니다.
     *    - 이 로직은 생성과 수정 모두에 중요하므로 공통으로 실행됩니다.
     *    - 부모의 깊이가 2 이상(즉, 3레벨 카테고리)이면, 4레벨 카테고리 생성을 막기 위해 ValidationException을 발생시켜 사용자에게 오류 메시지를 전달합니다.
     */
    protected function passedValidation(): void
    {
        // --- 생성(store) 요청에만 적용되는 로직 ---
        if ($this->isMethod('post')) {
            // 슬러그가 없으면 이름에서 자동 생성
            if (!$this->slug && $this->name) {
                $this->merge(['slug' => Str::slug($this->name)]);
            }

            // 생성 시에만 기본값 설정
            $this->merge([
                'is_active' => $this->boolean('is_active', true),
                'sort_order' => $this->input('sort_order', 0),
                'color' => $this->input('color', '#6366f1'),
            ]);
        }

        // --- 생성 및 수정 모두에 적용되는 로직 ---
        if ($this->parent_id) {
            $parent = Category::find($this->parent_id);
            // 부모가 존재하고, 부모의 깊이가 2 이상이면(0-based depth) 에러 발생
            if ($parent && $parent->depth >= 2) {
                throw ValidationException::withMessages([
                    'parent_id' => '3단계 이상의 하위 카테고리는 생성할 수 없습니다.',
                ]);
            }
        }
    }
}
