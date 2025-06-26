<?php

namespace App\Events\Blog;

use App\Models\Blog\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 게시물 발행 이벤트
 * 
 * 게시물이 발행 상태로 변경되었을 때 발생하는 이벤트입니다.
 * 이 이벤트를 통해 다음과 같은 작업들을 트리거할 수 있습니다:
 * - 검색 인덱스 업데이트
 * - 알림 발송
 * - 소셜 미디어 자동 포스팅
 * - 분석 데이터 수집
 * - RSS 피드 갱신
 */
class PostPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 발행된 게시물
     * 
     * @var Post
     */
    public readonly Post $post;

    /**
     * 이벤트 생성
     * 
     * @param Post $post 발행된 게시물
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * 이벤트 메타데이터 반환
     * 
     * @return array
     */
    public function getEventData(): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'post_slug' => $this->post->slug,
            'author_id' => $this->post->user_id,
            'category_id' => $this->post->category_id,
            'published_at' => $this->post->published_at,
            'event_time' => now(),
        ];
    }
}