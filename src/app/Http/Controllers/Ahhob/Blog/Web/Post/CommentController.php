<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Web\Post\CommentStoreRequest;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(CommentStoreRequest $request): RedirectResponse
    {
        $post = Post::findOrFail($request->post_id);

        // 댓글이 허용되지 않는 포스트인지 확인
        if (!$post->allow_comments) {
            return back()->with('error', '이 게시물은 댓글을 허용하지 않습니다.');
        }

        $data = $request->validated();
        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        // 회원인 경우 사용자 정보 자동 설정
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
            $data['author_name'] = Auth::user()->name;
            $data['author_email'] = Auth::user()->email;
            $data['status'] = 'approved'; // 회원 댓글은 자동 승인
        } else {
            $data['status'] = 'pending'; // 비회원 댓글은 승인 대기
        }

        // 대댓글인 경우 경로 설정
        if ($request->filled('parent_id')) {
            $parentComment = Comment::find($request->parent_id);
            if ($parentComment) {
                $data['depth'] = $parentComment->depth + 1;
                $data['path'] = $parentComment->path . '/' . $parentComment->id;

                // 대댓글 수 증가
                $parentComment->increment('replies_count');
            }
        }

        $comment = Comment::create($data);

        // 게시물 댓글 수 증가 (승인된 댓글만)
        if ($comment->status === 'approved') {
            $post->increment('comments_count');
        }

        $message = Auth::check()
            ? '댓글이 등록되었습니다.'
            : '댓글이 등록되었습니다. 관리자 승인 후 표시됩니다.';

        return back()->with('success', $message);
    }

    public function like(Comment $comment): RedirectResponse
    {
        if (!Auth::check()) {
            return back()->with('error', '로그인이 필요합니다.');
        }

        $comment->increment('likes_count');

        return back()->with('success', '댓글에 좋아요를 눌렀습니다.');
    }
}
