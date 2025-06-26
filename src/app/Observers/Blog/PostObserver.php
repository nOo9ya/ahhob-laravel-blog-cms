<?php

namespace App\Observers\Blog;

use App\Events\Blog\PostPublished;
use App\Events\Blog\PostStatusChanged;
use App\Models\Blog\Post;
use App\Services\Ahhob\Blog\Shared\CacheService;
use App\Services\Ahhob\Blog\Shared\MarkdownService;

/**
 * Post 모델 옵저버
 * 
 * Post 모델의 생명주기 이벤트를 처리합니다.
 * Observer 패턴을 사용하여 모델 로직과 비즈니스 로직을 분리하고,
 * 테스트 가능하고 재사용 가능한 구조를 만듭니다.
 * 
 * 주요 기능:
 * - 슬러그 자동 생성 및 중복 검사
 * - 마크다운 → HTML 변환
 * - 요약문 및 읽기 시간 자동 계산
 * - 캐시 무효화 처리
 * - 관련 데이터 정리 (이미지, 댓글, 태그)
 * - SEO 메타데이터 자동 설정
 */
class PostObserver
{
    public function __construct(
        private CacheService $cacheService,
        private MarkdownService $markdownService
    ) {}

    /**
     * 게시물 생성 전 처리
     * 
     * @param Post $post
     * @return void
     */
    public function creating(Post $post): void
    {
        $this->handleSlugGeneration($post);
        $this->handleMarkdownConversion($post);
        $this->handleSeoMetadata($post);
        $this->invalidateCache();
    }

    /**
     * 게시물 생성 후 처리
     * 
     * @param Post $post
     * @return void
     */
    public function created(Post $post): void
    {
        $this->handleContentProcessing($post);
        
        // 조용히 저장 (이벤트 재발생 방지)
        $post->saveQuietly();
        
        // 생성 이벤트 발생 (다른 서비스에서 활용 가능)
        event('post.created', $post);
    }

    /**
     * 게시물 수정 전 처리
     * 
     * @param Post $post
     * @return void
     */
    public function updating(Post $post): void
    {
        // 제목이 변경되었는데 슬러그가 없으면 재생성
        if ($post->isDirty('title') && empty($post->slug)) {
            $this->handleSlugGeneration($post);
        }

        // 콘텐츠가 변경되면 관련 처리
        if ($post->isDirty('content')) {
            $this->handleMarkdownConversion($post);
            $this->handleContentProcessing($post, false); // 저장하지 않음
        }

        // SEO 메타데이터 업데이트
        if ($post->isDirty(['title', 'content', 'excerpt'])) {
            $this->handleSeoMetadata($post);
        }

        $this->invalidateCache();
    }

    /**
     * 게시물 수정 후 처리
     * 
     * @param Post $post
     * @return void
     */
    public function updated(Post $post): void
    {
        // 상태가 변경된 경우 이벤트 발생
        if ($post->wasChanged('status')) {
            PostStatusChanged::dispatch($post, $post->getOriginal('status'), $post->status);
        }

        // 발행 상태로 변경된 경우
        if ($post->status === 'published' && $post->getOriginal('status') !== 'published') {
            PostPublished::dispatch($post);
        }
    }

    /**
     * 게시물 삭제 전 처리
     * 
     * @param Post $post
     * @return void
     */
    public function deleting(Post $post): void
    {
        $this->invalidateCache();
        
        // 관련 데이터 정리
        $this->cleanupRelatedData($post);
        
        // 삭제 이벤트 발생
        event('post.deleting', $post);
    }

    /**
     * 게시물 삭제 후 처리
     * 
     * @param Post $post
     * @return void
     */
    public function deleted(Post $post): void
    {
        event('post.deleted', $post);
    }

    /**
     * 게시물 복원 후 처리
     * 
     * @param Post $post
     * @return void
     */
    public function restored(Post $post): void
    {
        $this->invalidateCache();
        event('post.restored', $post);
    }

    /**
     * 슬러그 생성 처리
     * 
     * @param Post $post
     * @return void
     */
    private function handleSlugGeneration(Post $post): void
    {
        if (empty($post->slug) && !empty($post->title)) {
            $baseSlug = \Illuminate\Support\Str::slug($post->title);
            $slug = $baseSlug;
            $counter = 1;

            // 중복 검사 및 고유 슬러그 생성
            while (Post::where('slug', $slug)->where('id', '!=', $post->id ?? 0)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $post->slug = $slug;
        }
    }

    /**
     * 마크다운 변환 처리
     * 
     * @param Post $post
     * @return void
     */
    private function handleMarkdownConversion(Post $post): void
    {
        if (!empty($post->content)) {
            $post->content_html = $this->markdownService->toHtml($post->content);
        }
    }

    /**
     * 콘텐츠 후처리 (요약문, 읽기 시간)
     * 
     * @param Post $post
     * @param bool $save 저장 여부
     * @return void
     */
    private function handleContentProcessing(Post $post, bool $save = true): void
    {
        // 요약문 자동 생성
        if (empty($post->excerpt) && !empty($post->content)) {
            $post->excerpt = $this->markdownService->extractExcerpt($post->content, 200);
        }

        // 읽기 시간 계산
        if (!empty($post->content)) {
            $wordCount = str_word_count(strip_tags($post->content));
            $post->reading_time = max(1, ceil($wordCount / 200)); // 분당 200단어
        }

        if ($save) {
            $post->saveQuietly();
        }
    }

    /**
     * SEO 메타데이터 자동 설정
     * 
     * @param Post $post
     * @return void
     */
    private function handleSeoMetadata(Post $post): void
    {
        // 메타 제목이 없으면 게시물 제목 사용
        if (empty($post->meta_title) && !empty($post->title)) {
            $post->meta_title = \Illuminate\Support\Str::limit($post->title, 60);
        }

        // 메타 설명이 없으면 요약문 사용
        if (empty($post->meta_description)) {
            if (!empty($post->excerpt)) {
                $post->meta_description = \Illuminate\Support\Str::limit($post->excerpt, 160);
            } elseif (!empty($post->content)) {
                $cleanContent = strip_tags($post->content);
                $post->meta_description = \Illuminate\Support\Str::limit($cleanContent, 160);
            }
        }

        // OG 메타데이터 설정
        if (empty($post->og_title) && !empty($post->meta_title)) {
            $post->og_title = $post->meta_title;
        }

        if (empty($post->og_description) && !empty($post->meta_description)) {
            $post->og_description = $post->meta_description;
        }

        // OG 타입 기본값 설정
        if (empty($post->og_type)) {
            $post->og_type = 'article';
        }
    }

    /**
     * 관련 데이터 정리
     * 
     * @param Post $post
     * @return void
     */
    private function cleanupRelatedData(Post $post): void
    {
        try {
            // 관련 이미지들 정리 (물리적 파일 삭제 포함)
            $post->images()->each(function ($image) {
                try {
                    $image->deletePhysicalFiles();
                    $image->delete();
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete image during post cleanup', [
                        'image_id' => $image->id,
                        'post_id' => $image->imageable_id,
                        'error' => $e->getMessage()
                    ]);
                }
            });

            // 댓글들 소프트 삭제
            $post->comments()->delete();

            // 조회 기록 삭제
            $post->views()->delete();

            // 태그 관계 해제
            $post->tags()->detach();

            // 좋아요 관계 해제
            $post->likers()->detach();

        } catch (\Exception $e) {
            \Log::error('Failed to cleanup related data for post', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 캐시 무효화
     * 
     * @return void
     */
    private function invalidateCache(): void
    {
        if (config('ahhob_blog.cache.auto_invalidate.on_post_save', true)) {
            $this->cacheService->invalidatePosts();
            $this->cacheService->invalidateByTags(['static', 'sitemap', 'feed']);
        }
    }
}