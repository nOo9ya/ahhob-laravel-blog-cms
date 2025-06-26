<?php

namespace App\Services\Ahhob\Blog\Web\Post;

use App\Services\Ahhob\Blog\Shared\Post\PostService as SharedPostService;
use App\Services\Ahhob\Blog\Shared\CacheService;
use App\Traits\Blog\QueryBuilderTrait;
use App\Models\Blog\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 웹 사용자용 게시물 서비스
 * 
 * 이 서비스는 공개 블로그에서 게시물을 조회하는 기능을 제공합니다.
 * 공통 쿼리 빌더 트레이트와 캐시 서비스를 사용하여
 * 성능 최적화된 데이터 조회를 제공합니다.
 * 
 * 주요 기능:
 * - 공개된 게시물만 조회
 * - 캐시 기반 성능 최적화
 * - 다양한 정렬 및 필터링 옵션
 * - 관련 게시물 추천
 */
class PostService extends SharedPostService
{
    use QueryBuilderTrait;
    
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 공개된 게시물 목록 조회 (캐시 및 공통 필터 적용)
     * 
     * @param array $filters 필터 옵션
     * @return LengthAwarePaginator 페이지네이션된 게시물 목록
     */
    public function getPublishedPosts(array $filters = []): LengthAwarePaginator
    {
        // 검색어가 있으면 별도 캐시 처리
        if (isset($filters['search'])) {
            return $this->cacheService->rememberSearch(
                'posts_search',
                function () use ($filters) {
                    return $this->buildPublishedPostsQuery($filters);
                },
                $filters
            );
        }

        // 일반 목록은 표준 캐시 적용
        return $this->cacheService->rememberPosts(
            'published_list',
            function () use ($filters) {
                return $this->buildPublishedPostsQuery($filters);
            },
            $filters
        );
    }

    /**
     * 공개된 게시물 쿼리 빌더 (공통 트레이트 사용)
     * 
     * @param array $filters 필터 옵션
     * @return LengthAwarePaginator 페이지네이션된 결과
     */
    protected function buildPublishedPostsQuery(array $filters): LengthAwarePaginator
    {
        $query = Post::query();
        
        // 공개된 게시물만 조회
        $query = $this->applyPublishedScope($query);
        
        // 공통 필터 적용 (검색, 카테고리, 태그 등)
        $query = $this->applyCommonFilters($query, $filters);
        
        // 웹용 특별 필터 적용
        $query = $this->applyWebSpecificFilters($query, $filters);
        
        // 관계 즉시 로딩
        $query = $this->applyEagerLoading($query, ['tags'], true);
        
        // 웹용 정렬 적용
        $allowedSortFields = ['published_at', 'title', 'views_count', 'likes_count', 'comments_count'];
        $query = $this->applySorting($query, $filters, $allowedSortFields, 'published_at', 'desc');
        
        // 페이지네이션 적용
        return $this->applyPagination(
            $query,
            $filters,
            config('ahhob_blog.pagination.per_page', 15),
            50 // 웹에서는 최대 50개까지만 허용
        );
    }
    
    /**
     * 웹용 특별 필터 적용
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query 쿼리 빌더
     * @param array $filters 필터 배열
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyWebSpecificFilters($query, array $filters)
    {
        // 카테고리 슬러그로 필터링 (웹에서는 ID 대신 슬러그 사용)
        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($categoryQuery) use ($filters) {
                $categoryQuery->where('slug', $filters['category']);
            });
        }
        
        // 태그 슬러그로 필터링 (웹에서는 ID 대신 슬러그 사용)
        if (!empty($filters['tag'])) {
            $query->whereHas('tags', function ($tagQuery) use ($filters) {
                $tagQuery->where('slug', $filters['tag']);
            });
        }
        
        return $query;
    }

    /**
     * 추천 게시물 조회 (캐시 적용)
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedPosts(int $limit = 5): Collection
    {
        return $this->cacheService->rememberPosts(
            'featured',
            function () use ($limit) {
                return Post::with(['user', 'category', 'tags'])
                    ->published()
                    ->featured()
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get();
            },
            ['limit' => $limit]
        );
    }

    /**
     * 관련 게시물 조회 (캐시 적용)
     * @param Post $post
     * @param int $limit
     * @return Collection
     */
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        return $this->cacheService->rememberPosts(
            'related',
            function () use ($post, $limit) {
                return Post::with(['user', 'category'])
                    ->published()
                    ->where('category_id', $post->category_id)
                    ->where('id', '!=', $post->id)
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get();
            },
            ['post_id' => $post->id, 'category_id' => $post->category_id, 'limit' => $limit]
        );
    }

    /**
     * 최신 게시물 조회 (캐시 적용)
     * @param int $limit
     * @return Collection
     */
    public function getRecentPosts(int $limit = 10): Collection
    {
        return $this->cacheService->rememberPosts(
            'recent',
            function () use ($limit) {
                return Post::with(['user', 'category', 'tags'])
                    ->published()
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get();
            },
            ['limit' => $limit]
        );
    }

    /**
     * 인기 게시물 조회 (캐시 적용)
     * @param int $limit
     * @param string $period 기간 (week, month, all)
     * @return Collection
     */
    public function getPopularPosts(int $limit = 10, string $period = 'month'): Collection
    {
        return $this->cacheService->rememberPosts(
            'popular',
            function () use ($limit, $period) {
                $query = Post::with(['user', 'category', 'tags'])
                    ->published()
                    ->orderBy('views_count', 'desc');

                // 기간 필터
                if ($period !== 'all') {
                    $date = match ($period) {
                        'week' => now()->subWeek(),
                        'month' => now()->subMonth(),
                        default => now()->subMonth()
                    };
                    $query->where('published_at', '>=', $date);
                }

                return $query->limit($limit)->get();
            },
            ['limit' => $limit, 'period' => $period]
        );
    }

    /**
     * 게시물 단일 조회 (캐시 적용)
     * @param string $slug
     * @return Post|null
     */
    public function getPostBySlug(string $slug): ?Post
    {
        return $this->cacheService->rememberPosts(
            'by_slug',
            function () use ($slug) {
                return Post::with(['user', 'category', 'tags', 'approvedComments.user'])
                    ->published()
                    ->where('slug', $slug)
                    ->first();
            },
            ['slug' => $slug]
        );
    }
}
