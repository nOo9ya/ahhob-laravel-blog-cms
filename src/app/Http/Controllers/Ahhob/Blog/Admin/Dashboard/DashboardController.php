<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Ahhob\Blog\Admin\Post\PostService;
use App\Models\Blog\Post;
use App\Models\Blog\Comment;
use App\Models\User;
use App\Models\Blog\PostView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * @return View
     */
    public function index(): View
    {
        $stats = $this->postService->getDashboardStats();

        // 추가 통계
        $stats['total_users'] = User::count();
        $stats['pending_comments'] = Comment::where('status', 'pending')->count();
        $stats['total_views_today'] = PostView::today()->count();
        $stats['unique_visitors_today'] = PostView::uniqueVisitors(null, 'today');

        // 최근 활동
        $recentComments = Comment::with(['post', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 월별 게시물 통계 (차트용)
        $monthlyPosts = Post::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // 카테고리별 게시물 통계
        $categoryStats = Post::with('category')
            ->selectRaw('category_id, COUNT(*) as count')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->category->name,
                    'count' => $item->count,
                    'color' => $item->category->color,
                ];
            });

        return view('admin.dashboard.index', compact(
            'stats',
            'recentComments',
            'recentUsers',
            'monthlyPosts',
            'categoryStats'
        ));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week'); // today, week, month, year

        $stats = [];

        switch ($period) {
            case 'today':
                $stats = $this->getTodayStats();
                break;
            case 'week':
                $stats = $this->getWeekStats();
                break;
            case 'month':
                $stats = $this->getMonthStats();
                break;
            case 'year':
                $stats = $this->getYearStats();
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * @return array
     */
    private function getTodayStats(): array
    {
        return [
            'posts_created' => Post::whereDate('created_at', today())->count(),
            'posts_published' => Post::whereDate('published_at', today())->count(),
            'comments_received' => Comment::whereDate('created_at', today())->count(),
            'total_views' => PostView::today()->count(),
            'unique_visitors' => PostView::uniqueVisitors(null, 'today'),
            'hourly_views' => PostView::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->today()
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->pluck('count', 'hour')
                ->toArray(),
        ];
    }

    /**
     * @return array
     */
    private function getWeekStats(): array
    {
        return [
            'posts_created' => Post::thisWeek()->count(),
            'posts_published' => Post::where('status', 'published')
                ->thisWeek()
                ->count(),
            'comments_received' => Comment::thisWeek()->count(),
            'total_views' => PostView::thisWeek()->count(),
            'unique_visitors' => PostView::uniqueVisitors(null, 'week'),
            'daily_views' => PostView::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->thisWeek()
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }

    /**
     * @return array
     */
    private function getMonthStats(): array
    {
        return [
            'posts_created' => Post::thisMonth()->count(),
            'posts_published' => Post::where('status', 'published')
                ->thisMonth()
                ->count(),
            'comments_received' => Comment::thisMonth()->count(),
            'total_views' => PostView::thisMonth()->count(),
            'unique_visitors' => PostView::uniqueVisitors(null, 'month'),
            'daily_views' => PostView::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->thisMonth()
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }

    /**
     * @return array
     */
    private function getYearStats(): array
    {
        return [
            'posts_created' => Post::whereYear('created_at', now()->year)->count(),
            'posts_published' => Post::where('status', 'published')
                ->whereYear('published_at', now()->year)
                ->count(),
            'comments_received' => Comment::whereYear('created_at', now()->year)->count(),
            'total_views' => PostView::whereYear('created_at', now()->year)->count(),
            'monthly_views' => PostView::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('count', 'month')
                ->toArray(),
        ];
    }
}
