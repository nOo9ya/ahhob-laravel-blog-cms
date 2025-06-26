<?php

namespace App\Providers;

use App\Models\Blog\Post;
use App\Models\Blog\Category;
use App\Models\Blog\Tag;
use App\Models\Blog\Comment;
use App\Observers\Blog\PostObserver;
use App\Contracts\Blog\PostRepositoryInterface;
use App\Contracts\Blog\CategoryRepositoryInterface;
use App\Repositories\Blog\PostRepository;
use App\Repositories\Blog\CategoryRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository 패턴 등록
        $this->registerRepositories();
        
        // JWT 서비스 프로바이더 등록
        $this->app->register(\Tymon\JWTAuth\Providers\LaravelServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 블로그 모델 옵저버 등록
        $this->registerBlogObservers();
    }

    /**
     * Repository 인터페이스와 구현체 바인딩
     * 
     * 의존성 주입을 통해 Repository 패턴을 구현하고
     * 테스트 시 Mock 객체로 쉽게 교체할 수 있도록 합니다.
     */
    private function registerRepositories(): void
    {
        $this->app->bind(PostRepositoryInterface::class, function ($app) {
            return new PostRepository($app->make(Post::class));
        });

        $this->app->bind(CategoryRepositoryInterface::class, function ($app) {
            return new CategoryRepository($app->make(Category::class));
        });
    }

    /**
     * 블로그 관련 모델 옵저버 등록
     * 
     * Observer 패턴을 사용하여 모델 이벤트 처리를 분리하고
     * 비즈니스 로직을 테스트 가능하고 재사용 가능하게 만듭니다.
     */
    private function registerBlogObservers(): void
    {
        Post::observe(PostObserver::class);
        
        // 다른 모델 옵저버들도 필요시 여기에 추가
        // Category::observe(CategoryObserver::class);
        // Tag::observe(TagObserver::class);
        // Comment::observe(CommentObserver::class);
    }
}
