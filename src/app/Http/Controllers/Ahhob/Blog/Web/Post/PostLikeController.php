<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Post;

use App\Http\Controllers\Controller;
use App\Models\Blog\Post;
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
     * @param Post $post
     * @return RedirectResponse
     */
    public function __invoke(Post $post): RedirectResponse
    {
        // 미들웨어로 처리하는 것이 더 좋지만, 컨트롤러 레벨에서도 방어 코드를 유지합니다.
        if (!Auth::check()) {
            return back()->with('error', '로그인이 필요합니다.');
        }

        $user = Auth::user();

        // toggle 메소드는 관계를 추가하거나(attach) 제거(detach)합니다.
        // 결과로 ['attached' => [...], 'detached' => [...]] 배열을 반환합니다.
        $result = $user->likedPosts()->toggle($post->id);

        // 'likes_count' 컬럼을 동기화합니다.
        if (!empty($result['attached'])) {
            // 좋아요를 눌렀을 때
            $post->increment('likes_count');
            $message = '좋아요를 눌렀습니다.';
        } else {
            // 좋아요를 취소했을 때
            $post->decrement('likes_count');
            $message = '좋아요를 취소했습니다.';
        }

        return back()->with('success', $message);
    }
}
