<?php

namespace App\Events\Blog;

use App\Models\Blog\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 게시물 상태 변경 이벤트
 * 
 * 게시물의 상태(draft, published, archived)가 변경되었을 때 발생하는 이벤트입니다.
 * 상태 변경에 따른 다양한 후속 작업을 처리할 수 있습니다.
 */
class PostStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * 상태가 변경된 게시물
     * 
     * @var Post
     */
    public readonly Post $post;

    /**
     * 이전 상태
     * 
     * @var string
     */
    public readonly string $oldStatus;

    /**
     * 새로운 상태
     * 
     * @var string
     */
    public readonly string $newStatus;

    /**
     * 이벤트 생성
     * 
     * @param Post $post 상태가 변경된 게시물
     * @param string $oldStatus 이전 상태
     * @param string $newStatus 새로운 상태
     */
    public function __construct(Post $post, string $oldStatus, string $newStatus)
    {
        $this->post = $post;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * 상태 변경이 발행인지 확인
     * 
     * @return bool
     */
    public function isPublishing(): bool
    {
        return $this->oldStatus !== 'published' && $this->newStatus === 'published';
    }

    /**
     * 상태 변경이 발행 취소인지 확인
     * 
     * @return bool
     */
    public function isUnpublishing(): bool
    {
        return $this->oldStatus === 'published' && $this->newStatus !== 'published';
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'is_publishing' => $this->isPublishing(),
            'is_unpublishing' => $this->isUnpublishing(),
            'changed_at' => now(),
        ];
    }
}