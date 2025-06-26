<?php

namespace App\Contracts\Blog;

use App\Models\Blog\Category;
use Illuminate\Support\Collection;

/**
 * 카테고리 저장소 인터페이스
 * 
 * Category 모델에 특화된 데이터 접근 메서드를 정의합니다.
 * 계층 구조와 관련된 특수 기능을 포함합니다.
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * 슬러그로 카테고리 조회
     * 
     * @param string $slug
     * @return Category|null
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * 활성화된 카테고리만 조회
     * 
     * @return Collection
     */
    public function getActive(): Collection;

    /**
     * 계층 구조로 카테고리 조회
     * 
     * @return Collection
     */
    public function getHierarchy(): Collection;

    /**
     * 루트 카테고리들 조회
     * 
     * @return Collection
     */
    public function getRootCategories(): Collection;

    /**
     * 자식 카테고리들 조회
     * 
     * @param int $parentId
     * @return Collection
     */
    public function getChildren(int $parentId): Collection;

    /**
     * 게시물 수와 함께 카테고리 조회
     * 
     * @return Collection
     */
    public function getWithPostCounts(): Collection;

    /**
     * 인기 카테고리 조회 (게시물 수 기준)
     * 
     * @param int $limit
     * @return Collection
     */
    public function getPopular(int $limit = 10): Collection;

    /**
     * 카테고리 경로 조회 (breadcrumb용)
     * 
     * @param Category $category
     * @return Collection
     */
    public function getPath(Category $category): Collection;

    /**
     * 슬러그 중복 확인
     * 
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    public function isSlugUnique(string $slug, ?int $excludeId = null): bool;

    /**
     * 하위 카테고리 포함 삭제 가능 여부 확인
     * 
     * @param int $categoryId
     * @return bool
     */
    public function canDelete(int $categoryId): bool;

    /**
     * 카테고리 순서 변경
     * 
     * @param array $orders
     * @return bool
     */
    public function updateOrder(array $orders): bool;
}