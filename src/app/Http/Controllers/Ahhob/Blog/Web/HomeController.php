<?php

namespace App\Http\Controllers\Ahhob\Blog\Web;

use App\Http\Controllers\Controller;
use App\Models\Blog\Category;
use App\Models\Blog\Post;
use App\Models\Blog\Tag;
use App\Services\Ahhob\Blog\Shared\CacheService;
use App\Services\Ahhob\Blog\Web\Post\PostService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class HomeController extends Controller
{
    protected CacheService $cacheService;
    protected PostService $postService;

    public function __construct(CacheService $cacheService, PostService $postService)
    {
        $this->cacheService = $cacheService;
        $this->postService = $postService;
    }

    public function index(): View|Factory|Application
    {
        // 추천 게시물 (캐시 적용)
        $featuredPosts = $this->postService->getFeaturedPosts(3);

        // 최신 게시물 (추천 게시물 제외, 캐시 적용)
        $recentPosts = $this->cacheService->rememberPosts(
            'home_recent',
            function () use ($featuredPosts) {
                return Post::with(['user', 'category', 'tags'])
                    ->published()
                    ->when($featuredPosts->isNotEmpty(), function ($query) use ($featuredPosts) {
                        return $query->whereNotIn('id', $featuredPosts->pluck('id'));
                    })
                    ->orderBy('published_at', 'desc')
                    ->limit(9)
                    ->get();
            },
            ['featured_ids' => $featuredPosts->pluck('id')->toArray()]
        );

        // 인기 태그 (캐시 적용)
        $popularTags = $this->cacheService->rememberTags(
            'popular_home',
            function () {
                return Tag::with('posts')
                    ->where('posts_count', '>', 0)
                    ->orderBy('posts_count', 'desc')
                    ->limit(10)
                    ->get();
            }
        );

        // 카테고리 목록 (캐시 적용)
        $categories = $this->cacheService->rememberCategories(
            'home_tree',
            function () {
                return Category::active()
                    ->roots()
                    ->withCount(['posts' => function ($query) {
                        $query->where('status', 'published');
                    }])
                    ->orderBy('sort_order')
                    ->get();
            }
        );

        return view('ahhob.blog.web.home.index', compact(
            'featuredPosts',
            'recentPosts',
            'popularTags',
            'categories'
        ));
    }
}
