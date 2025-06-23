<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Post;

use App\Http\Controllers\Controller;
use App\Models\Blog\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Search Post Controller
 */
class PostSearchController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function __invoke(Request $request): View
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100'
        ]);

        $searchTerm = $request->q;

        $posts = Post::with(['user', 'category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) use ($searchTerm) {
                $query->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%");
            })
            ->orderBy('published_at', 'desc')
            ->paginate(config('ahhob.pagination.per_page'));

        return view('web.post.search', compact('posts', 'searchTerm'));
    }
}
