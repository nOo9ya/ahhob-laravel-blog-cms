<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Admin\Category\CategoryStoreRequest;
use App\Http\Requests\Admin\Blog\Admin\Category\CategoryUpdateRequest;
use App\Models\Blog\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $categories = Category::with(['parent', 'children'])
            ->withCount('posts')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // 트리 구조로 정렬
        $categoriesTree = $this->buildCategoryTree($categories);

        return view('admin.category.index', compact('categoriesTree'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $parentCategories = Category::active()
            ->roots()
            ->orderBy('sort_order')
            ->get();

        return view('admin.category.create', compact('parentCategories'));
    }

    /**
     * @param CategoryStoreRequest $request
     * @return RedirectResponse
     */
    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', '카테고리가 성공적으로 생성되었습니다.');
    }

    /**
     * @param Category $category
     * @return View
     */
    public function edit(Category $category): View
    {
        $parentCategories = Category::active()
            ->where('id', '!=', $category->id)
            ->roots()
            ->orderBy('sort_order')
            ->get();

        return view('admin.category.edit', compact('category', 'parentCategories'));
    }

    /**
     * @param CategoryUpdateRequest $request
     * @param Category $category
     * @return RedirectResponse
     */
    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        // 부모 카테고리 변경 시 순환 참조 확인
        if (isset($data['parent_id']) && $data['parent_id']) {
            if ($this->wouldCreateCircularReference($category, $data['parent_id'])) {
                return back()->withErrors([
                    'parent_id' => '순환 참조가 발생할 수 있는 부모 카테고리입니다.'
                ]);
            }
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', '카테고리가 성공적으로 업데이트되었습니다.');
    }

    /**
     * @param Category $category
     * @return RedirectResponse
     */
    public function destroy(Category $category): RedirectResponse
    {
        // 하위 카테고리가 있는지 확인
        if ($category->children()->count() > 0) {
            return back()->with('error', '하위 카테고리가 있는 카테고리는 삭제할 수 없습니다.');
        }

        // 게시물이 있는지 확인
        if ($category->posts()->count() > 0) {
            return back()->with('error', '게시물이 있는 카테고리는 삭제할 수 없습니다.');
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "카테고리 '{$name}'이(가) 성공적으로 삭제되었습니다.");
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
            'categories.*.parent_id' => 'nullable|exists:categories,id',
        ]);

        foreach ($request->categories as $categoryData) {
            Category::where('id', $categoryData['id'])->update([
                'sort_order' => $categoryData['sort_order'],
                'parent_id' => $categoryData['parent_id'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '카테고리 순서가 성공적으로 변경되었습니다.',
        ]);
    }

    /**
     * 카테고리 트리 구조 생성
     * @param $categories
     * @param $parentId
     * @return array
     */
    private function buildCategoryTree($categories, $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $category->children_tree = $this->buildCategoryTree($categories, $category->id);
                $tree[] = $category;
            }
        }

        return $tree;
    }

    /**
     * 순환 참조 확인
     * @param Category $category
     * @param int $newParentId
     * @return bool
     */
    private function wouldCreateCircularReference(Category $category, int $newParentId): bool
    {
        $parentCategory = Category::find($newParentId);

        if (!$parentCategory) {
            return false;
        }

        // 새 부모가 현재 카테고리의 하위 카테고리인지 확인
        return $parentCategory->isChildOf($category);
    }
}

