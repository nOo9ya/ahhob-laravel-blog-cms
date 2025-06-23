<?php

namespace App\Services\Ahhob\Blog\Web\Post;

use App\Services\Ahhob\Blog\Shared\Post\PostService as SharedPostService;
use App\Models\Blog\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PostService extends SharedPostService
{
    /**
     * 공개된 게시물 목록 조회
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPublishedPosts(array $filters = []): LengthAwarePaginator
    {
        $query = Post::with(['user', 'category', 'tags'])
            ->published();

        // 카테고리 필터
        if (isset($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        // 태그 필터
        if (isset($filters['tag'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('slug', $filters['tag']);
            });
        }

        // 검색
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%");
            });
        }

        // 정렬
        $sortBy = $filters['sort'] ?? 'latest';
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'liked':
                $query->orderBy('likes_count', 'desc');
                break;
            case 'commented':
                $query->orderBy('comments_count', 'desc');
                break;
            default:
                $query->orderBy('published_at', 'desc');
        }

        return $query->paginate(config('ahhob.pagination.per_page'));
    }

    /**
     * 추천 게시물 조회
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedPosts(int $limit = 5): Collection
    {
        return Post::with(['user', 'category', 'tags'])
            ->published()
            ->featured()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 관련 게시물 조회
     * @param Post $post
     * @param int $limit
     * @return Collection
     */
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        return Post::with(['user', 'category'])
            ->published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
