<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Category;

use App\Http\Controllers\Controller;
use App\Models\Blog\Category;
use App\Models\Blog\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()
            ->with(['children' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->withCount(['posts' => function ($query) {
                $query->where('status', 'published');
            }])
            ->roots()
            ->orderBy('sort_order')
            ->get();

        return view('web.category.index', compact('categories'));
    }

    public function show(Category $category, Request $request): View
    {
        if (!$category->is_active) {
            abort(404);
        }

        $query = $category->posts()
            ->with(['user', 'category', 'tags'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

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

        // 하위 카테고리들
        $subcategories = $category->children()
            ->active()
            ->withCount(['posts' => function ($query) {
                $query->where('status', 'published');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('web.category.show', compact(
            'category',
            'posts',
            'subcategories'
        ));
    }
}
