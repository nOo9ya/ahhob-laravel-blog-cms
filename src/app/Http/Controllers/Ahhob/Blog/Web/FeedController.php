<?php

namespace App\Http\Controllers\Ahhob\Blog\Web;

use App\Http\Controllers\Controller;
use App\Models\Blog\Post;
use App\Services\Ahhob\Blog\Shared\CacheService;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(): Response
    {
        // RSS 피드 전체를 캐시
        $xml = $this->cacheService->rememberStatic(
            'rss_feed',
            function () {
                $posts = Post::with(['user', 'category'])
                    ->published()
                    ->orderBy('published_at', 'desc')
                    ->limit(20)
                    ->get();

                return view('ahhob.blog.web.feed.rss', compact('posts'))->render();
            }
        );

        return response($xml, 200, [
            'Content-type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600', // 1시간 브라우저 캐시
        ]);
    }
}
