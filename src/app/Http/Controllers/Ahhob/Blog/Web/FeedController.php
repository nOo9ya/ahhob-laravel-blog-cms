<?php

namespace App\Http\Controllers\Ahhob\Blog\Web;

use App\Http\Controllers\Controller;
use App\Models\Blog\Post;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function index(): Response
    {
        $posts = Post::with(['user', 'category'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(20)
            ->get();

        $xml = view('web.home.feed', compact('posts'))->render();

        return response($xml, 200, [
            'Content-type' => 'application/rss+xml; charset=utf-8'
        ]);
    }
}
