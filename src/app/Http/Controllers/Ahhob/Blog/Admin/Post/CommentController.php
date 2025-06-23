<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Post;

use App\Http\Controllers\Controller;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommentController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = Comment::with(['post', 'user', 'parent']);

        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 게시물 필터
        if ($request->filled('post_id')) {
            $query->where('post_id', $request->post_id);
        }

        // 검색
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('content', 'like', "%{$searchTerm}%")
                    ->orWhere('author_name', 'like', "%{$searchTerm}%")
                    ->orWhere('author_email', 'like', "%{$searchTerm}%");
            });
        }

        // 기간 필터
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 정렬
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'likes':
                $query->orderBy('likes_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $comments = $query->paginate(20);

        // 필터링 옵션들
        $posts = Post::where('status', 'published')
            ->orderBy('title')
            ->get(['id', 'title']);

        $filters = $request->only(['status', 'post_id', 'search', 'date_from', 'date_to', 'sort']);

        // 통계
        $stats = [
            'total' => Comment::count(),
            'pending' => Comment::where('status', 'pending')->count(),
            'approved' => Comment::where('status', 'approved')->count(),
            'rejected' => Comment::where('status', 'rejected')->count(),
            'spam' => Comment::where('status', 'spam')->count(),
        ];

        return view('admin.comment.index', compact('comments', 'posts', 'filters', 'stats'));
    }

    /**
     * @param Comment $comment
     * @return View
     */
    public function show(Comment $comment): View
    {
        $comment->load(['post', 'user', 'parent.user', 'children.user']);

        // 같은 IP나 이메일의 다른 댓글들
        $relatedComments = Comment::where('id', '!=', $comment->id)
            ->where(function ($query) use ($comment) {
                $query->where('ip_address', $comment->ip_address);
                if ($comment->author_email) {
                    $query->orWhere('author_email', $comment->author_email);
                }
                if ($comment->user_id) {
                    $query->orWhere('user_id', $comment->user_id);
                }
            })
            ->with(['post'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.comment.show', compact('comment', 'relatedComments'));
    }

    /**
     * @param Comment $comment
     * @return RedirectResponse
     */
    public function approve(Comment $comment): RedirectResponse
    {
        $comment->approve(Auth::user());

        return back()->with('success', '댓글이 승인되었습니다.');
    }

    /**
     * @param Comment $comment
     * @return RedirectResponse
     */
    public function reject(Comment $comment): RedirectResponse
    {
        $comment->reject();

        return back()->with('success', '댓글이 거부되었습니다.');
    }

    /**
     * @param Comment $comment
     * @return RedirectResponse
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return redirect()->route('admin.comments.index')
            ->with('success', '댓글이 삭제되었습니다.');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'exists:comments,id',
            'action' => 'required|in:approve,reject,spam,delete',
        ]);

        $comments = Comment::whereIn('id', $request->comment_ids);
        $result = ['success' => 0, 'failed' => 0];

        switch ($request->action) {
            case 'approve':
                foreach ($comments->get() as $comment) {
                    $comment->approve(Auth::user());
                    $result['success']++;
                }
                break;

            case 'reject':
                foreach ($comments->get() as $comment) {
                    $comment->reject();
                    $result['success']++;
                }
                break;

            case 'spam':
                foreach ($comments->get() as $comment) {
                    $comment->markAsSpam();
                    $result['success']++;
                }
                break;

            case 'delete':
                $result['success'] = $comments->count();
                $comments->delete();
                break;
        }

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
            'approve' => '승인',
            'reject' => '거부',
            'spam' => '스팸 처리',
            'delete' => '삭제',
        ];

        $actionName = $actionMessages[$action] ?? $action;
        $successCount = $result['success'];

        return "{$successCount}개 댓글이 {$actionName}되었습니다.";
    }
}

