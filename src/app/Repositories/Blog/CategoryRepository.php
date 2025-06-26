<?php

namespace App\Repositories\Blog;

use App\Contracts\Blog\CategoryRepositoryInterface;
use App\Models\Blog\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 카테고리 저장소 구현체
 * 
 * Category 모델에 대한 데이터 액세스 로직을 담당합니다.
 * 계층 구조와 관련된 복잡한 쿼리를 처리합니다.
 */
class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    /**
     * 생성자
     * 
     * @param Category $model
     */
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * 슬러그로 카테고리 조회
     * 
     * @param string $slug
     * @return Category|null
     */
    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * 활성화된 카테고리만 조회
     * 
     * @return Collection
     */
    public function getActive(): Collection
    {
        return $this->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * 계층 구조로 카테고리 조회
     * 
     * @return Collection
     */
    public function getHierarchy(): Collection
    {
        $categories = $this->with('children')
                          ->whereNull('parent_id')
                          ->orderBy('sort_order')
                          ->orderBy('name')
                          ->get();

        return $this->buildHierarchy($categories);
    }

    /**
     * 루트 카테고리들 조회
     * 
     * @return Collection
     */
    public function getRootCategories(): Collection
    {
        return $this->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * 자식 카테고리들 조회
     * 
     * @param int $parentId
     * @return Collection
     */
    public function getChildren(int $parentId): Collection
    {
        return $this->where('parent_id', $parentId)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * 게시물 수와 함께 카테고리 조회
     * 
     * @return Collection
     */
    public function getWithPostCounts(): Collection
    {
        return $this->query
                    ->withCount(['posts' => function ($query) {
                        $query->where('status', 'published');
                    }])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * 인기 카테고리 조회 (게시물 수 기준)
     * 
     * @param int $limit
     * @return Collection
     */
    public function getPopular(int $limit = 10): Collection
    {
        return $this->query
                    ->withCount(['posts' => function ($query) {
                        $query->where('status', 'published');
                    }])
                    ->where('is_active', true)
                    ->orderBy('posts_count', 'desc')
                    ->orderBy('name')
                    ->limit($limit)
                    ->get();
    }

    /**
     * 카테고리 경로 조회 (breadcrumb용)
     * 
     * @param Category $category
     * @return Collection
     */
    public function getPath(Category $category): Collection
    {
        $path = collect([$category]);
        $current = $category;

        while ($current->parent_id) {
            $current = $this->find($current->parent_id);
            if ($current) {
                $path->prepend($current);
            } else {
                break;
            }
        }

        return $path;
    }

    /**
     * 슬러그 중복 확인
     * 
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    public function isSlugUnique(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }

    /**
     * 하위 카테고리 포함 삭제 가능 여부 확인
     * 
     * @param int $categoryId
     * @return bool
     */
    public function canDelete(int $categoryId): bool
    {
        // 하위 카테고리가 있는지 확인
        $hasChildren = $this->model->where('parent_id', $categoryId)->exists();
        
        // 게시물이 있는지 확인
        $hasPosts = $this->model->find($categoryId)?->posts()->exists() ?? false;
        
        return !$hasChildren && !$hasPosts;
    }

    /**
     * 카테고리 순서 변경
     * 
     * @param array $orders
     * @return bool
     */
    public function updateOrder(array $orders): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($orders as $id => $order) {
                $this->model->where('id', $id)->update(['sort_order' => $order]);
            }
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 계층 구조 빌드 (재귀적으로 자식들 로드)
     * 
     * @param Collection $categories
     * @return Collection
     */
    private function buildHierarchy(Collection $categories): Collection
    {
        return $categories->map(function ($category) {
            if ($category->children->isNotEmpty()) {
                $category->children = $this->buildHierarchy($category->children);
            }
            return $category;
        });
    }

    /**
     * 카테고리 트리를 플랫 배열로 변환
     * 
     * @param Collection $categories
     * @param int $depth
     * @return Collection
     */
    public function flattenHierarchy(Collection $categories, int $depth = 0): Collection
    {
        $result = collect();
        
        foreach ($categories as $category) {
            $category->depth = $depth;
            $result->push($category);
            
            if ($category->children && $category->children->isNotEmpty()) {
                $children = $this->flattenHierarchy($category->children, $depth + 1);
                $result = $result->concat($children);
            }
        }
        
        return $result;
    }
}