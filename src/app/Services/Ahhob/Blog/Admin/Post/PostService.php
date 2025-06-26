<?php

namespace App\Services\Ahhob\Blog\Admin\Post;

use App\Services\Ahhob\Blog\Shared\Post\PostService as SharedPostService;
use App\Traits\Blog\QueryBuilderTrait;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * 관리자용 게시물 서비스
 * 
 * 이 서비스는 관리자 패널에서 게시물을 관리하는 기능을 제공합니다.
 * 공통 쿼리 빌더 트레이트를 사용하여 코드 중복을 방지하고
 * 일관된 쿼리 로직을 유지합니다.
 * 
 * 주요 기능:
 * - 게시물 목록 조회 (모든 상태 포함)
 * - 고급 필터링 및 정렬
 * - 일괄 작업 처리
 * - 통계 데이터 제공
 */
class PostService extends SharedPostService
{
    use QueryBuilderTrait;

    /**
     * 관리자용 게시물 목록 조회
     * 
     * 모든 상태의 게시물을 조회할 수 있으며, 다양한 필터링 옵션을 제공합니다.
     * 
     * @param array $filters 필터 옵션
     * @return LengthAwarePaginator 페이지네이션된 게시물 목록
     */
    public function getPosts(array $filters = []): LengthAwarePaginator
    {
        $query = Post::query();

        // 공통 필터 적용
        $query = $this->applyCommonFilters($query, $filters);

        // 삭제된 항목 포함 여부 (관리자는 삭제된 항목도 볼 수 있음)
        $query = $this->applyTrashedScope($query, $filters['include_trashed'] ?? false);

        // 관계 즉시 로딩
        $query = $this->applyEagerLoading($query, ['tags'], true);

        // 정렬 적용 (관리자용 확장된 정렬 옵션)
        $allowedSortFields = [
            'created_at', 'updated_at', 'published_at', 'title', 
            'views_count', 'likes_count', 'comments_count', 
            'author', 'category', 'status'
        ];
        $query = $this->applySorting($query, $filters, $allowedSortFields, 'updated_at', 'desc');

        // 페이지네이션 적용
        return $this->applyPagination(
            $query, 
            $filters, 
            config('ahhob_blog.pagination.admin_per_page', 20),
            100
        );
    }

    /**
     * Request 객체로부터 게시물 목록 조회
     * 
     * @param Request $request HTTP 요청
     * @return LengthAwarePaginator 페이지네이션된 게시물 목록
     */
    public function getPostsFromRequest(Request $request): LengthAwarePaginator
    {
        $allowedFilters = [
            'search', 'status', 'author_id', 'category_id', 'tag_ids',
            'date_from', 'date_to', 'is_featured', 'allow_comments',
            'sort', 'sort_dir', 'per_page', 'include_trashed'
        ];

        $filters = $this->extractFiltersFromRequest($request, $allowedFilters);
        $filters = $this->cleanFilters($filters);

        return $this->getPosts($filters);
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
     * 본문 이미지 업로드
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    public function uploadContentImage($file): string
    {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = 'uploads/posts/content/' . date('Y/m');
        
        $file->storeAs($path, $filename, 'public');
        
        return $path . '/' . $filename;
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
