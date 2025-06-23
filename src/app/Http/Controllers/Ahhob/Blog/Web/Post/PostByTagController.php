<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Post;

use App\Http\Controllers\Controller;
use App\Models\Blog\Tag;
use Illuminate\View\View;

/**
 * Post by Tag List controller
 */
class PostByTagController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Tag $tag
     * @return View
     */
    public function __invoke(Tag $tag): View
    {
        $posts = $tag->publishedPosts()
            ->with(['user', 'category', 'tags'])
            ->orderBy('published_at', 'desc')
            ->paginate(config('ahhob.pagination.per_page'));

        return view('web.post.by-tag', compact(
            'posts',
            'tag'
        ));
    }
}
