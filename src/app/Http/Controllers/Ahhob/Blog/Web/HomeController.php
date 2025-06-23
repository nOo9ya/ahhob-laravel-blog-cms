<?php

namespace App\Http\Controllers\Ahhob\Blog\Web;

use App\Http\Controllers\Controller;
use App\Models\Blog\Category;
use App\Models\Blog\Post;
use App\Models\Blog\Tag;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class HomeController extends Controller
{
    public function index(): View|Factory|Application
    {
        $featuredPosts = Post::with(['user', 'category', 'tags'])
            ->where('status', 'published')
            ->where('is_featured', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        $recentPosts = Post::with(['user', 'category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($featuredPosts->isNotEmpty(), function ($query) use ($featuredPosts) {
                return $query->whereNotIn('id', $featuredPosts->pluck('id'));
            })
            ->orderBy('published_at', 'desc')
            ->limit(9)
            ->get();

        $popularTags = Tag::with('posts')
            ->where('posts_count', '>', 0)
            ->orderBy('posts_count', 'desc')
            ->limit(10)
            ->get();

        $categories = Category::active()
            ->roots()
            ->withCount(['posts' => function ($query) {
                $query->where('status', 'published');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('web.home.index', compact(
            'featuredPosts',
            'recentPosts',
            'popularTags',
            'categories'
        ));
    }
}
