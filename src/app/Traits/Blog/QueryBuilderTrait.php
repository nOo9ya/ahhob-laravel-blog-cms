<?php

namespace App\Traits\Blog;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * 블로그 관련 쿼리 빌더 공통 로직 트레이트
 * 
 * 이 트레이트는 Admin, Web, API 서비스에서 공통적으로 사용되는
 * 쿼리 빌딩 로직을 중앙화하여 코드 중복을 방지합니다.
 * 
 * 주요 기능:
 * - 공통 필터링 로직 (검색, 상태, 날짜 범위 등)
 * - 정렬 및 페이지네이션 처리
 * - 관계 로딩 최적화
 * - 캐시 키 생성
 * 
 * 사용법:
 * class PostService {
 *     use QueryBuilderTrait;
 *     
 *     public function getPosts(array $filters) {
 *         $query = Post::query();
 *         return $this->applyCommonFilters($query, $filters);
 *     }
 * }
 */
trait QueryBuilderTrait
{
    /**
     * 공통 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param array $filters 필터 배열
     * @return Builder 필터가 적용된 쿼리 빌더
     */
    protected function applyCommonFilters(Builder $query, array $filters): Builder
    {
        // 검색어 필터
        if (!empty($filters['search'])) {
            $query = $this->applySearchFilter($query, $filters['search']);
        }

        // 상태 필터
        if (!empty($filters['status'])) {
            $query = $this->applyStatusFilter($query, $filters['status']);
        }

        // 작성자 필터
        if (!empty($filters['author_id'])) {
            $query = $this->applyAuthorFilter($query, $filters['author_id']);
        }

        // 카테고리 필터
        if (!empty($filters['category_id'])) {
            $query = $this->applyCategoryFilter($query, $filters['category_id']);
        }

        // 태그 필터
        if (!empty($filters['tag_ids'])) {
            $query = $this->applyTagFilter($query, $filters['tag_ids']);
        }

        // 날짜 범위 필터
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $query = $this->applyDateRangeFilter($query, $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        }

        // 추천 글 필터
        if (isset($filters['is_featured'])) {
            $query = $this->applyFeaturedFilter($query, $filters['is_featured']);
        }

        // 댓글 허용 필터
        if (isset($filters['allow_comments'])) {
            $query = $this->applyCommentsFilter($query, $filters['allow_comments']);
        }

        return $query;
    }

    /**
     * 검색어 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param string $searchTerm 검색어
     * @return Builder
     */
    protected function applySearchFilter(Builder $query, string $searchTerm): Builder
    {
        $searchTerm = '%' . trim($searchTerm) . '%';

        return $query->where(function ($subQuery) use ($searchTerm) {
            $subQuery->where('title', 'LIKE', $searchTerm)
                     ->orWhere('excerpt', 'LIKE', $searchTerm)
                     ->orWhere('content', 'LIKE', $searchTerm)
                     ->orWhere('meta_keywords', 'LIKE', $searchTerm);

            // 작성자 이름으로도 검색 (관계 테이블 조인)
            $subQuery->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                $userQuery->where('name', 'LIKE', $searchTerm)
                         ->orWhere('email', 'LIKE', $searchTerm);
            });

            // 카테고리 이름으로도 검색
            $subQuery->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                $categoryQuery->where('name', 'LIKE', $searchTerm);
            });

            // 태그로도 검색
            $subQuery->orWhereHas('tags', function ($tagQuery) use ($searchTerm) {
                $tagQuery->where('name', 'LIKE', $searchTerm);
            });
        });
    }

    /**
     * 상태 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param string|array $status 상태 값
     * @return Builder
     */
    protected function applyStatusFilter(Builder $query, $status): Builder
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    /**
     * 작성자 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param int|array $authorId 작성자 ID
     * @return Builder
     */
    protected function applyAuthorFilter(Builder $query, $authorId): Builder
    {
        if (is_array($authorId)) {
            return $query->whereIn('user_id', $authorId);
        }

        return $query->where('user_id', $authorId);
    }

    /**
     * 카테고리 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param int|array $categoryId 카테고리 ID
     * @return Builder
     */
    protected function applyCategoryFilter(Builder $query, $categoryId): Builder
    {
        if (is_array($categoryId)) {
            return $query->whereIn('category_id', $categoryId);
        }

        return $query->where('category_id', $categoryId);
    }

    /**
     * 태그 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param array $tagIds 태그 ID 배열
     * @return Builder
     */
    protected function applyTagFilter(Builder $query, array $tagIds): Builder
    {
        return $query->whereHas('tags', function ($tagQuery) use ($tagIds) {
            $tagQuery->whereIn('tags.id', $tagIds);
        });
    }

    /**
     * 날짜 범위 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param string|null $dateFrom 시작 날짜
     * @param string|null $dateTo 종료 날짜
     * @param string $dateField 날짜 필드명 (기본: published_at)
     * @return Builder
     */
    protected function applyDateRangeFilter(Builder $query, ?string $dateFrom, ?string $dateTo, string $dateField = 'published_at'): Builder
    {
        if ($dateFrom) {
            $query->whereDate($dateField, '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate($dateField, '<=', $dateTo);
        }

        return $query;
    }

    /**
     * 추천 글 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param bool $isFeatured 추천 여부
     * @return Builder
     */
    protected function applyFeaturedFilter(Builder $query, bool $isFeatured): Builder
    {
        return $query->where('is_featured', $isFeatured);
    }

    /**
     * 댓글 허용 필터 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param bool $allowComments 댓글 허용 여부
     * @return Builder
     */
    protected function applyCommentsFilter(Builder $query, bool $allowComments): Builder
    {
        return $query->where('allow_comments', $allowComments);
    }

    /**
     * 정렬 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param array $filters 필터 배열 (sort, sort_dir 포함)
     * @param array $allowedSortFields 허용되는 정렬 필드 목록
     * @param string $defaultSort 기본 정렬 필드
     * @param string $defaultDirection 기본 정렬 방향
     * @return Builder
     */
    protected function applySorting(
        Builder $query, 
        array $filters, 
        array $allowedSortFields = ['created_at', 'updated_at', 'title', 'views_count'], 
        string $defaultSort = 'created_at', 
        string $defaultDirection = 'desc'
    ): Builder {
        $sortField = $filters['sort'] ?? $defaultSort;
        $sortDirection = $filters['sort_dir'] ?? $defaultDirection;

        // 보안: 허용된 필드만 정렬 가능
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = $defaultSort;
        }

        // 보안: 허용된 방향만 사용
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = $defaultDirection;
        }

        // 특별한 정렬 로직 처리
        switch ($sortField) {
            case 'author':
                return $query->join('users', 'posts.user_id', '=', 'users.id')
                           ->orderBy('users.name', $sortDirection)
                           ->select('posts.*');

            case 'category':
                return $query->join('categories', 'posts.category_id', '=', 'categories.id')
                           ->orderBy('categories.name', $sortDirection)
                           ->select('posts.*');

            case 'comments_count':
                return $query->withCount('comments')
                           ->orderBy('comments_count', $sortDirection);

            default:
                return $query->orderBy($sortField, $sortDirection);
        }
    }

    /**
     * 관계 즉시 로딩 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param array $relationships 로딩할 관계 배열
     * @param bool $withCounts 카운트 포함 여부
     * @return Builder
     */
    protected function applyEagerLoading(Builder $query, array $relationships = [], bool $withCounts = true): Builder
    {
        // 기본 관계 로딩
        $defaultRelationships = ['user', 'category'];
        $relationships = array_merge($defaultRelationships, $relationships);

        $query->with($relationships);

        // 카운트 로딩 (성능 최적화)
        if ($withCounts) {
            $query->withCount(['comments', 'views', 'likers']);
        }

        return $query;
    }

    /**
     * 페이지네이션 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param array $filters 필터 배열 (per_page 포함)
     * @param int $defaultPerPage 기본 페이지당 항목 수
     * @param int $maxPerPage 최대 페이지당 항목 수
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function applyPagination(Builder $query, array $filters, int $defaultPerPage = 15, int $maxPerPage = 100)
    {
        $perPage = (int) ($filters['per_page'] ?? $defaultPerPage);
        
        // 보안: 최대 항목 수 제한
        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * 캐시 키 생성
     * 
     * @param string $prefix 키 접두사
     * @param array $filters 필터 배열
     * @param array $additionalParams 추가 매개변수
     * @return string 생성된 캐시 키
     */
    protected function generateCacheKey(string $prefix, array $filters, array $additionalParams = []): string
    {
        // 필터 정렬 (일관된 키 생성을 위해)
        ksort($filters);
        ksort($additionalParams);

        $keyParts = [
            $prefix,
            md5(serialize($filters)),
            md5(serialize($additionalParams))
        ];

        return implode(':', $keyParts);
    }

    /**
     * 게시 상태 조건 적용 (웹 서비스용)
     * 
     * @param Builder $query 쿼리 빌더
     * @return Builder
     */
    protected function applyPublishedScope(Builder $query): Builder
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * 활성 상태 조건 적용 (카테고리, 태그 등)
     * 
     * @param Builder $query 쿼리 빌더
     * @return Builder
     */
    protected function applyActiveScope(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 소프트 삭제된 항목 포함 여부 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param bool $includeTrashed 삭제된 항목 포함 여부
     * @return Builder
     */
    protected function applyTrashedScope(Builder $query, bool $includeTrashed = false): Builder
    {
        if ($includeTrashed) {
            return $query->withTrashed();
        }

        return $query;
    }

    /**
     * 통계 데이터 조회를 위한 집계 쿼리 적용
     * 
     * @param Builder $query 쿼리 빌더
     * @param array $aggregations 집계 필드 배열
     * @return Builder
     */
    protected function applyAggregations(Builder $query, array $aggregations = []): Builder
    {
        $defaultAggregations = [
            'total_views' => 'views_count',
            'total_likes' => 'likes_count',
            'total_comments' => 'comments_count'
        ];

        $aggregations = array_merge($defaultAggregations, $aggregations);

        foreach ($aggregations as $alias => $field) {
            $query->addSelect(\DB::raw("SUM($field) as $alias"));
        }

        return $query;
    }

    /**
     * Request 객체에서 필터 추출
     * 
     * @param Request $request HTTP 요청
     * @param array $allowedFilters 허용된 필터 목록
     * @return array 추출된 필터
     */
    protected function extractFiltersFromRequest(Request $request, array $allowedFilters = []): array
    {
        $defaultFilters = [
            'search', 'status', 'author_id', 'category_id', 'tag_ids',
            'date_from', 'date_to', 'is_featured', 'allow_comments',
            'sort', 'sort_dir', 'per_page'
        ];

        $allowedFilters = empty($allowedFilters) ? $defaultFilters : $allowedFilters;

        return $request->only($allowedFilters);
    }

    /**
     * 빈 필터 값 제거
     * 
     * @param array $filters 필터 배열
     * @return array 정리된 필터 배열
     */
    protected function cleanFilters(array $filters): array
    {
        return array_filter($filters, function ($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
    }
}