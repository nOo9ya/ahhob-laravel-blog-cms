<?php

namespace App\Listeners\Blog;

use App\Events\Blog\PostPublished;
use App\Events\Blog\PostStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * 검색 인덱스 업데이트 리스너
 * 
 * 게시물 상태 변경 시 검색 인덱스를 업데이트합니다.
 * 큐를 사용하여 비동기로 처리되므로 사용자 경험에 영향을 주지 않습니다.
 */
class UpdateSearchIndex implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * 큐 연결
     * 
     * @var string
     */
    public $connection = 'database';

    /**
     * 큐 이름
     * 
     * @var string
     */
    public $queue = 'search-index';

    /**
     * 재시도 횟수
     * 
     * @var int
     */
    public $tries = 3;

    /**
     * 게시물 발행 이벤트 처리
     * 
     * @param PostPublished $event
     * @return void
     */
    public function handle(PostPublished $event): void
    {
        try {
            $post = $event->post;
            
            // 검색 인덱스에 게시물 추가/업데이트
            $this->addToSearchIndex($post);
            
            Log::info('Search index updated for published post', [
                'post_id' => $post->id,
                'post_title' => $post->title,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update search index for published post', [
                'post_id' => $event->post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // 재시도를 위해 예외 재발생
        }
    }

    /**
     * 게시물 상태 변경 이벤트 처리
     * 
     * @param PostStatusChanged $event
     * @return void
     */
    public function handleStatusChange(PostStatusChanged $event): void
    {
        try {
            $post = $event->post;
            
            if ($event->isPublishing()) {
                // 발행: 검색 인덱스에 추가
                $this->addToSearchIndex($post);
                
            } elseif ($event->isUnpublishing()) {
                // 발행 취소: 검색 인덱스에서 제거
                $this->removeFromSearchIndex($post);
            }
            
            Log::info('Search index updated for status change', [
                'post_id' => $post->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update search index for status change', [
                'post_id' => $event->post->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 검색 인덱스에 게시물 추가/업데이트
     * 
     * @param \App\Models\Blog\Post $post
     * @return void
     */
    private function addToSearchIndex($post): void
    {
        // 실제 검색 엔진 구현에 따라 달라집니다
        // 예: Elasticsearch, Algolia, MySQL Full-Text Search 등
        
        $searchData = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => strip_tags($post->content_html ?? $post->content),
            'excerpt' => $post->excerpt,
            'author' => $post->user->name ?? '',
            'category' => $post->category->name ?? '',
            'tags' => $post->tags->pluck('name')->toArray(),
            'published_at' => $post->published_at,
            'slug' => $post->slug,
            'url' => route('web.posts.show', $post->slug),
        ];
        
        // TODO: 실제 검색 엔진에 데이터 전송
        // 예시: Elasticsearch
        // ElasticsearchService::indexDocument('posts', $post->id, $searchData);
        
        // 임시로 로그에 기록
        Log::debug('Search index data prepared', [
            'post_id' => $post->id,
            'search_data' => $searchData,
        ]);
    }

    /**
     * 검색 인덱스에서 게시물 제거
     * 
     * @param \App\Models\Blog\Post $post
     * @return void
     */
    private function removeFromSearchIndex($post): void
    {
        // TODO: 실제 검색 엔진에서 문서 삭제
        // 예시: Elasticsearch
        // ElasticsearchService::deleteDocument('posts', $post->id);
        
        // 임시로 로그에 기록
        Log::debug('Post removed from search index', [
            'post_id' => $post->id,
        ]);
    }
}