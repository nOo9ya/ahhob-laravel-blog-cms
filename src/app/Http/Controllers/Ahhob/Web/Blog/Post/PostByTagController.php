<?php

namespace App\Http\Controllers\Ahhob\Web\Blog\Post;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\View\View;

/**
 * Post by Tag List controller
 */
class PostByTagController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\View\View
     */
    public function __invoke(Tag $tag): View
    {
        $posts = $tag->publishedPosts()
            ->with(['user', 'category', 'tags'])
            ->orderBy('published_at', 'desc')
            ->paginate(config('ahhob.pagination.per_page'));

        return view('web.post.by-tag', compact('posts', 'tag'));
    }
}
