<?php

namespace App\Repositories\Blog;

use App\Contracts\Blog\PostRepositoryInterface;
use App\Models\Blog\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 게시물 저장소 구현체
 * 
 * Post 모델에 대한 데이터 액세스 로직을 담당합니다.
 * 복잡한 쿼리 로직을 서비스 레이어에서 분리하여 재사용성과 테스트 가능성을 높입니다.
 */
class PostRepository extends BaseRepository implements PostRepositoryInterface
{
    /**
     * 생성자
     * 
     * @param Post $model
     */
    public function __construct(Post $model)
    {
        parent::__construct($model);
    }

    /**
     * 슬러그로 게시물 조회
     * 
     * @param string $slug
     * @return Post|null
     */
    public function findBySlug(string $slug): ?Post
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * 발행된 게시물만 조회
     * 
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublished(int $perPage = 15): LengthAwarePaginator
    {
        return $this->where('status', 'published')
                    ->with(['user', 'category', 'tags'])
                    ->orderBy('published_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * 카테고리별 게시물 조회
     * 
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->where('category_id', $categoryId)
                    ->where('status', 'published')
                    ->with(['user', 'category', 'tags'])
                    ->orderBy('published_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * 태그별 게시물 조회
     * 
     * @param string $tagSlug
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByTag(string $tagSlug, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query
                    ->whereHas('tags', function ($query) use ($tagSlug) {
                        $query->where('slug', $tagSlug);
                    })
                    ->where('status', 'published')
                    ->with(['user', 'category', 'tags'])
                    ->orderBy('published_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * 인기 게시물 조회 (조회수 기준)
     * 
     * @param int $limit
     * @param int $days 기간 (일)
     * @return Collection
     */
    public function getPopular(int $limit = 10, int $days = 30): Collection
    {
        $startDate = now()->subDays($days);

        return $this->query
                    ->select('posts.*')
                    ->leftJoin('post_views', 'posts.id', '=', 'post_views.post_id')
                    ->where('posts.status', 'published')
                    ->where(function ($query) use ($startDate) {
                        $query->where('post_views.created_at', '>=', $startDate)
                              ->orWhereNull('post_views.created_at');
                    })
                    ->groupBy('posts.id')
                    ->orderByRaw('COUNT(post_views.id) DESC')
                    ->orderBy('posts.published_at', 'desc')
                    ->with(['user', 'category'])
                    ->limit($limit)
                    ->get();
    }

    /**
     * 관련 게시물 조회
     * 
     * @param Post $post
     * @param int $limit
     * @return Collection
     */
    public function getRelated(Post $post, int $limit = 5): Collection
    {
        $query = $this->query
                      ->where('id', '!=', $post->id)
                      ->where('status', 'published')
                      ->with(['user', 'category']);

        // 같은 카테고리의 게시물 우선
        if ($post->category_id) {
            $query->where('category_id', $post->category_id);
        }

        $related = $query->orderBy('published_at', 'desc')
                        ->limit($limit)
                        ->get();

        // 관련 게시물이 부족하면 다른 카테고리에서 가져오기
        if ($related->count() < $limit) {
            $needed = $limit - $related->count();
            $excludeIds = $related->pluck('id')->push($post->id)->toArray();

            $additional = $this->query
                              ->whereNotIn('id', $excludeIds)
                              ->where('status', 'published')
                              ->with(['user', 'category'])
                              ->orderBy('published_at', 'desc')
                              ->limit($needed)
                              ->get();

            $related = $related->concat($additional);
        }

        $this->resetQuery();
        return $related;
    }

    /**
     * 최근 게시물 조회
     * 
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->where('status', 'published')
                    ->with(['user', 'category'])
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * 검색
     * 
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query
                    ->where('status', 'published')
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'LIKE', "%{$query}%")
                          ->orWhere('content', 'LIKE', "%{$query}%")
                          ->orWhere('excerpt', 'LIKE', "%{$query}%");
                    })
                    ->with(['user', 'category', 'tags'])
                    ->orderBy('published_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * 작성자별 게시물 조회
     * 
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAuthor(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->where('user_id', $userId)
                    ->where('status', 'published')
                    ->with(['category', 'tags'])
                    ->orderBy('published_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * 초안 게시물 조회
     * 
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDrafts(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->where('user_id', $userId)
                    ->where('status', 'draft')
                    ->with(['category', 'tags'])
                    ->orderBy('updated_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * 월별 게시물 수 통계
     * 
     * @param int $months
     * @return Collection
     */
    public function getMonthlyStats(int $months = 12): Collection
    {
        return DB::table('posts')
                 ->select(
                     DB::raw('YEAR(published_at) as year'),
                     DB::raw('MONTH(published_at) as month'),
                     DB::raw('COUNT(*) as count')
                 )
                 ->where('status', 'published')
                 ->where('published_at', '>=', now()->subMonths($months))
                 ->groupBy('year', 'month')
                 ->orderBy('year', 'desc')
                 ->orderBy('month', 'desc')
                 ->get();
    }

    /**
     * 게시물 통계 정보
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $published = $this->model->where('status', 'published')->count();
        $drafts = $this->model->where('status', 'draft')->count();
        $archived = $this->model->where('status', 'archived')->count();

        $today = $this->model
                     ->where('status', 'published')
                     ->whereDate('published_at', today())
                     ->count();

        $thisWeek = $this->model
                        ->where('status', 'published')
                        ->whereBetween('published_at', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                        ->count();

        $thisMonth = $this->model
                         ->where('status', 'published')
                         ->whereMonth('published_at', now()->month)
                         ->whereYear('published_at', now()->year)
                         ->count();

        return [
            'total' => $total,
            'published' => $published,
            'drafts' => $drafts,
            'archived' => $archived,
            'published_today' => $today,
            'published_this_week' => $thisWeek,
            'published_this_month' => $thisMonth,
        ];
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
}