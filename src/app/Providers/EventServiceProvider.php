<?php

namespace App\Providers;

use App\Events\Blog\PostPublished;
use App\Events\Blog\PostStatusChanged;
use App\Listeners\Blog\SendNotifications;
use App\Listeners\Blog\UpdateSearchIndex;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * 이벤트 서비스 프로바이더
 * 
 * 애플리케이션의 이벤트와 리스너를 등록합니다.
 * Observer 패턴과 Event/Listener 패턴을 조합하여
 * 확장 가능하고 테스트 가능한 이벤트 시스템을 구축합니다.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * 이벤트 리스너 매핑
     * 
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // 게시물 발행 이벤트
        PostPublished::class => [
            UpdateSearchIndex::class,
            SendNotifications::class,
        ],

        // 게시물 상태 변경 이벤트
        PostStatusChanged::class => [
            UpdateSearchIndex::class . '@handleStatusChange',
        ],

        // Laravel 기본 이벤트들
        \Illuminate\Auth\Events\Registered::class => [
            \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * 이벤트 디스커버리를 위한 디렉토리
     * 
     * @return array<int, string>
     */
    public function discoverEventsWithin(): array
    {
        return [
            $this->app->path('Listeners'),
        ];
    }

    /**
     * 애플리케이션을 위한 추가적인 이벤트 등록
     * 
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        // 커스텀 이벤트 등록
        $this->registerCustomEvents();
    }

    /**
     * 커스텀 이벤트 등록
     * 
     * 클로저 기반의 이벤트 리스너를 등록합니다.
     * 간단한 로직의 경우 별도 클래스 없이 바로 처리할 수 있습니다.
     * 
     * @return void
     */
    protected function registerCustomEvents(): void
    {
        // 게시물 생성 이벤트 (문자열 기반)
        \Event::listen('post.created', function ($post) {
            \Log::info('Post created', [
                'post_id' => $post->id,
                'title' => $post->title,
                'author_id' => $post->user_id,
            ]);
        });

        // 게시물 삭제 이벤트
        \Event::listen('post.deleted', function ($post) {
            \Log::info('Post deleted', [
                'post_id' => $post->id,
                'title' => $post->title,
            ]);
        });

        // 게시물 복원 이벤트
        \Event::listen('post.restored', function ($post) {
            \Log::info('Post restored', [
                'post_id' => $post->id,
                'title' => $post->title,
            ]);
        });
    }

    /**
     * 큐를 사용해야 하는 이벤트 판단
     * 
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // 명시적으로 등록된 이벤트만 사용
    }
}