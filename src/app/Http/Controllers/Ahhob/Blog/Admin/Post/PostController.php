<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Admin\Post\PostRequest;
use App\Services\Ahhob\Blog\Admin\Post\PostService;
use App\Models\Blog\Post;
use App\Models\Blog\Category;
use App\Models\Blog\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'author_id', 'category_id', 'search', 'date_from', 'date_to', 'sort', 'sort_dir']);
        $posts = $this->postService->getPosts($filters);

        // 필터링 옵션들
        $categories = Category::active()->orderBy('name')->get();
        $authors = \App\Models\User::byRole('writer')->orWhere('role', 'admin')->get();

        return view('admin.post.index', compact('posts', 'categories', 'authors', 'filters'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $categories = Category::active()
            ->with('children')
            ->roots()
            ->orderBy('sort_order')
            ->get();

        $tags = Tag::orderBy('name')->get();

        return view('admin.post.create', compact('categories', 'tags'));
    }

    /**
     * @param PostRequest $request
     * @return RedirectResponse
     */
    public function store(PostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $featuredImage = $request->file('featured_image');
        $ogImage = $request->file('og_image');

        $post = $this->postService->createPost($data, $featuredImage, $ogImage);

        return redirect()->route('admin.posts.edit', $post)
            ->with('success', '게시물이 성공적으로 생성되었습니다.');
    }

    /**
     * @param Post $post
     * @return View
     */
    public function show(Post $post): View
    {
        $post->load(['user', 'category', 'tags', 'comments.user']);

        // 조회수 통계
        $viewStats = [
            'today' => $post->views()->today()->count(),
            'week' => $post->views()->thisWeek()->count(),
            'month' => $post->views()->thisMonth()->count(),
            'total' => $post->views_count,
        ];

        return view('admin.post.show', compact('post', 'viewStats'));
    }

    /**
     * @param Post $post
     * @return View
     */
    public function edit(Post $post): View
    {
        // 권한 확인
        if (!$post->canBeEditedBy(Auth::user())) {
            abort(403, '이 게시물을 수정할 권한이 없습니다.');
        }

        $post->load(['category', 'tags']);

        $categories = Category::active()
            ->with('children')
            ->roots()
            ->orderBy('sort_order')
            ->get();

        $allTags = Tag::orderBy('name')->get();

        return view('admin.post.edit', compact('post', 'categories', 'allTags'));
    }

    /**
     * @param PostRequest $request
     * @param Post $post
     * @return RedirectResponse
     */
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        $featuredImage = $request->file('featured_image');
        $ogImage = $request->file('og_image');

        $post = $this->postService->updatePost($post, $data, $featuredImage, $ogImage);

        return redirect()->route('admin.posts.edit', $post)
            ->with('success', '게시물이 성공적으로 업데이트되었습니다.');
    }

    /**
     * @param Post $post
     * @return RedirectResponse
     */
    public function destroy(Post $post): RedirectResponse
    {
        // 권한 확인
        if (!$post->canBeEditedBy(Auth::user())) {
            abort(403, '이 게시물을 삭제할 권한이 없습니다.');
        }

        $title = $post->title;

        if ($this->postService->deletePost($post)) {
            return redirect()->route('admin.posts.index')
                ->with('success', "게시물 '{$title}'이(가) 성공적으로 삭제되었습니다.");
        }

        return back()->with('error', '게시물 삭제 중 오류가 발생했습니다.');
    }

    /**
     * @param Post $post
     * @return RedirectResponse
     */
    public function restore(Post $post): RedirectResponse
    {
        $post->restore();

        return back()->with('success', '게시물이 성공적으로 복원되었습니다.');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:posts,id',
            'action' => 'required|in:publish,draft,archive,delete,feature,unfeature',
        ]);

        $result = $this->postService->bulkAction(
            $request->post_ids,
            $request->action,
            Auth::user()
        );

        $message = $this->getBulkActionMessage($request->action, $result);

        return response()->json([
            'success' => true,
            'message' => $message,
            'result' => $result,
        ]);
    }

    /**
     * @param string $action
     * @param array $result
     * @return string
     */
    private function getBulkActionMessage(string $action, array $result): string
    {
        $actionMessages = [
            'publish' => '발행',
            'draft' => '임시저장',
            'archive' => '보관',
            'delete' => '삭제',
            'feature' => '추천 설정',
            'unfeature' => '추천 해제',
        ];

        $actionName = $actionMessages[$action] ?? $action;
        $successCount = $result['success'];
        $failedCount = $result['failed'] ?? 0;

        if ($failedCount > 0) {
            return "{$successCount}개 게시물이 {$actionName}되었습니다. ({$failedCount}개 실패)";
        }

        return "{$successCount}개 게시물이 {$actionName}되었습니다.";
    }
}

