<?php

namespace App\Http\Controllers\Ahhob\Web\Blog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $posts = Post::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('updated_at', 'desc')
            ->get(['slug', 'updated_at']);

        $categories = Category::active()->get(['slug', 'updated_at']);

        $xml = view('web.home.sitemap', compact('posts', 'categories'))->render();

        return response($xml. 200, [
            'Content-type' => 'application/xml; charset=utf-8'
        ]);
    }
}
