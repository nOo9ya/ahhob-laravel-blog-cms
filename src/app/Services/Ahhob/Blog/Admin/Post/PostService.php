<?php

namespace App\Services\Ahhob\Blog\Admin\Post;

use App\Services\Ahhob\Blog\Shared\Post\PostService as SharedPostService;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class PostService extends SharedPostService
{
    /**
     * 관리자용 게시물 목록 조회
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPosts(array $filters = []): LengthAwarePaginator
    {
        $query = Post::with(['user', 'category', 'tags']);

        // 상태 필터
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 작성자 필터
        if (isset($filters['author_id'])) {
            $query->where('user_id', $filters['author_id']);
        }

        // 카테고리 필터
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // 검색
        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        // 기간 필터
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // 정렬
        $sortBy = $filters['sort'] ?? 'latest';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortDir);
                break;
            case 'views':
                $query->orderBy('views_count', $sortDir);
                break;
            case 'comments':
                $query->orderBy('comments_count', $sortDir);
                break;
            case 'status':
                $query->orderBy('status', $sortDir);
                break;
            default:
                $query->orderBy('created_at', $sortDir);
        }

        return $query->paginate(config('ahhob.pagination.admin_per_page'));
    }

    /**
     * 일괄 작업 처리
     * @param array $postIds
     * @param string $action
     * @param User|null $user
     * @return int[]
     */
    public function bulkAction(
        array $postIds,
        string $action,
        ?User $user = null
    ): array
    {
        $posts = Post::whereIn('id', $postIds);
        $result = ['success' => 0, 'failed' => 0];

        switch ($action) {
            case 'publish':
                $updated = $posts->update([
                    'status' => 'published',
                    'published_at' => now()
                ]);
                $result['success'] = $updated;
                break;

            case 'draft':
                $updated = $posts->update(['status' => 'draft']);
                $result['success'] = $updated;
                break;

            case 'archive':
                $updated = $posts->update(['status' => 'archived']);
                $result['success'] = $updated;
                break;

            case 'delete':
                foreach ($posts->get() as $post) {
                    if ($this->deletePost($post)) {
                        $result['success']++;
                    } else {
                        $result['failed']++;
                    }
                }
                break;

            case 'feature':
                $updated = $posts->update(['is_featured' => true]);
                $result['success'] = $updated;
                break;

            case 'unfeature':
                $updated = $posts->update(['is_featured' => false]);
                $result['success'] = $updated;
                break;
        }

        return $result;
    }

    /**
     * 대시보드 통계
     * @return array
     */
    public function getDashboardStats(): array
    {
        return [
            'total_posts' => Post::count(),
            'published_posts' => Post::where('status', 'published')->count(),
            'draft_posts' => Post::where('status', 'draft')->count(),
            'total_views' => Post::sum('views_count'),
            'total_comments' => Post::sum('comments_count'),
            'featured_posts' => Post::where('is_featured', true)->count(),
            'recent_posts' => Post::with(['user', 'category'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'popular_posts' => Post::with(['user', 'category'])
                ->where('status', 'published')
                ->orderBy('views_count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}
