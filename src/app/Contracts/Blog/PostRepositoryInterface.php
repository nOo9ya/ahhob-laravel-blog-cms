<?php

namespace App\Contracts\Blog;

use App\Models\Blog\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 게시물 저장소 인터페이스
 * 
 * Post 모델에 특화된 데이터 접근 메서드를 정의합니다.
 * 기본 Repository 인터페이스를 확장하여 게시물 관련 특수 기능을 추가합니다.
 */
interface PostRepositoryInterface extends RepositoryInterface
{
    /**
     * 슬러그로 게시물 조회
     * 
     * @param string $slug
     * @return Post|null
     */
    public function findBySlug(string $slug): ?Post;

    /**
     * 발행된 게시물만 조회
     * 
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublished(int $perPage = 15): LengthAwarePaginator;

    /**
     * 카테고리별 게시물 조회
     * 
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;

    /**
     * 태그별 게시물 조회
     * 
     * @param string $tagSlug
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByTag(string $tagSlug, int $perPage = 15): LengthAwarePaginator;

    /**
     * 인기 게시물 조회 (조회수 기준)
     * 
     * @param int $limit
     * @param int $days 기간 (일)
     * @return Collection
     */
    public function getPopular(int $limit = 10, int $days = 30): Collection;

    /**
     * 관련 게시물 조회
     * 
     * @param Post $post
     * @param int $limit
     * @return Collection
     */
    public function getRelated(Post $post, int $limit = 5): Collection;

    /**
     * 최근 게시물 조회
     * 
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * 검색
     * 
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * 작성자별 게시물 조회
     * 
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAuthor(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * 초안 게시물 조회
     * 
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDrafts(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * 월별 게시물 수 통계
     * 
     * @param int $months
     * @return Collection
     */
    public function getMonthlyStats(int $months = 12): Collection;

    /**
     * 게시물 통계 정보
     * 
     * @return array
     */
    public function getStatistics(): array;

    /**
     * 슬러그 중복 확인
     * 
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    public function isSlugUnique(string $slug, ?int $excludeId = null): bool;
}