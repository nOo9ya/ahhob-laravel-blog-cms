<?php

namespace App\Http\Controllers\Ahhob\Web\Blog\Post;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Like Post controller
 */
class PostLikeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Post $post): RedirectResponse
    {
        if (!Auth::check()) {
            return back()->with('error', '로그인이 필요합니다.');
        }

        $user = Auth::user();

        // 개선 제안: 실제 서비스에서는 사용자가 중복으로 '좋아요'를 누를 수 없도록
        // User와 Post 사이에 many-to-many 관계(예: post_user_likes 테이블)를 설정하고
        // toggle() 메소드를 사용하는 것이 좋습니다.
        // 예: $user->likedPosts()->toggle($post->id);

        // 현재 로직 유지: likes_count만 증가
        $post->increment('likes_count');

        return back()->with('success', '좋아요를 눌렀습니다.');
    }
}
