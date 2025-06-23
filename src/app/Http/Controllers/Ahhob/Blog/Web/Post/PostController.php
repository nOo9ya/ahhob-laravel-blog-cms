<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Post;

use App\Http\Controllers\Controller;
use App\Models\Blog\Category;
use App\Models\Blog\Post;
use App\Models\Blog\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $query = Post::with(['user', 'category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        // 카테고리 필터
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // 검색 (index 페이지 내의 간단한 검색은 유지하거나, 별도 검색 페이지로 완전히 분리할 수 있습니다)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%");
            });
        }

        // 정렬
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'liked':
                $query->orderBy('likes_count', 'desc');
                break;
            case 'commented':
                $query->orderBy('comments_count', 'desc');
                break;
            default:
                $query->orderBy('published_at', 'desc');
        }

        $posts = $query->paginate(config('ahhob.pagination.per_page'));

        $categories = Category::active()
            ->withCount(['posts' => function ($query) {
                $query->where('status', 'published');
            }])
            ->orderBy('sort_order')
            ->get();

        $popularTags = Tag::popular(15)->get();

        return view('web.post.index', compact(
            'posts',
            'categories',
            'popularTags'
        ));
    }

    public function show(Post $post): View
    {
        // 비공개 포스트는 작성자와 관리자만 볼 수 있음
        if ($post->status !== 'published' || $post->published_at > now()) {
            if (!Auth::check() ||
                (Auth::id() !== $post->user_id && !in_array(Auth::user()->role, ['admin', 'writer']))) {
                abort(404);
            }
        }

        $post->load(['user', 'category', 'tags']);

        // 관련 포스트 (같은 카테고리의 최신 포스트)
        $relatedPosts = Post::with(['user', 'category'])
            ->where('status', 'published')
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get();

        // 댓글 (승인된 것만)
        $comments = $post->comments()
            ->with(['user', 'children.user'])
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('web.post.show', compact(
            'post',
            'relatedPosts',
            'comments'
        ));
    }
}
