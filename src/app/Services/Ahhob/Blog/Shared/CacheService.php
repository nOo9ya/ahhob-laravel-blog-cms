<?php

namespace App\Services\Ahhob\Blog\Shared;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Closure;

class CacheService
{
    protected string $prefix = 'ahhob_blog';
    protected array $defaultTags = ['blog'];

    /**
     * 캐시 키 생성
     */
    public function makeKey(string $key, array $params = []): string
    {
        $keyParts = [$this->prefix, $key];
        
        if (!empty($params)) {
            $keyParts[] = md5(serialize($params));
        }
        
        return implode(':', $keyParts);
    }

    /**
     * 기본 캐싱 메서드
     */
    public function remember(string $key, int $ttl, Closure $callback, array $tags = [])
    {
        $cacheKey = $this->makeKey($key);
        
        if (config('ahhob_blog.cache.enabled', true)) {
            return Cache::tags(array_merge($this->defaultTags, $tags))
                ->remember($cacheKey, $ttl, $callback);
        }
        
        return $callback();
    }

    /**
     * 영구 캐싱
     */
    public function rememberForever(string $key, Closure $callback, array $tags = [])
    {
        $cacheKey = $this->makeKey($key);
        
        if (config('ahhob_blog.cache.enabled', true)) {
            return Cache::tags(array_merge($this->defaultTags, $tags))
                ->rememberForever($cacheKey, $callback);
        }
        
        return $callback();
    }

    /**
     * 게시물 관련 캐싱
     */
    public function rememberPosts(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("posts:{$key}", $params);
        $ttl = config('ahhob_blog.cache.posts_ttl', 3600);
        
        return $this->remember($fullKey, $ttl, $callback, ['posts']);
    }

    /**
     * 카테고리 관련 캐싱
     */
    public function rememberCategories(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("categories:{$key}", $params);
        $ttl = config('ahhob_blog.cache.categories_ttl', 7200);
        
        return $this->remember($fullKey, $ttl, $callback, ['categories']);
    }

    /**
     * 태그 관련 캐싱
     */
    public function rememberTags(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("tags:{$key}", $params);
        $ttl = config('ahhob_blog.cache.tags_ttl', 3600);
        
        return $this->remember($fullKey, $ttl, $callback, ['tags']);
    }

    /**
     * 페이지 관련 캐싱
     */
    public function rememberPages(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("pages:{$key}", $params);
        $ttl = config('ahhob_blog.cache.pages_ttl', 7200);
        
        return $this->remember($fullKey, $ttl, $callback, ['pages']);
    }

    /**
     * 댓글 관련 캐싱
     */
    public function rememberComments(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("comments:{$key}", $params);
        $ttl = config('ahhob_blog.cache.comments_ttl', 1800);
        
        return $this->remember($fullKey, $ttl, $callback, ['comments']);
    }

    /**
     * 통계 관련 캐싱
     */
    public function rememberStats(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("stats:{$key}", $params);
        $ttl = config('ahhob_blog.cache.stats_ttl', 1800);
        
        return $this->remember($fullKey, $ttl, $callback, ['stats']);
    }

    /**
     * RSS/Sitemap 등 정적 콘텐츠 캐싱
     */
    public function rememberStatic(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("static:{$key}", $params);
        $ttl = config('ahhob_blog.cache.static_ttl', 43200); // 12시간
        
        return $this->remember($fullKey, $ttl, $callback, ['static']);
    }

    /**
     * 검색 결과 캐싱
     */
    public function rememberSearch(string $key, Closure $callback, array $params = []): mixed
    {
        $fullKey = $this->makeKey("search:{$key}", $params);
        $ttl = config('ahhob_blog.cache.search_ttl', 900); // 15분
        
        return $this->remember($fullKey, $ttl, $callback, ['search']);
    }

    /**
     * 특정 키 캐시 삭제
     */
    public function forget(string $key, array $params = []): bool
    {
        $cacheKey = $this->makeKey($key, $params);
        
        return Cache::forget($cacheKey);
    }

    /**
     * 태그 기반 캐시 무효화
     */
    public function invalidateByTags(array $tags): bool
    {
        if (!config('ahhob_blog.cache.enabled', true)) {
            return true;
        }

        try {
            Cache::tags(array_merge($this->defaultTags, $tags))->flush();
            return true;
        } catch (\Exception $e) {
            logger()->error('Cache invalidation failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 게시물 관련 캐시 무효화
     */
    public function invalidatePosts(): bool
    {
        return $this->invalidateByTags(['posts', 'stats']);
    }

    /**
     * 카테고리 관련 캐시 무효화
     */
    public function invalidateCategories(): bool
    {
        return $this->invalidateByTags(['categories', 'posts', 'stats']);
    }

    /**
     * 태그 관련 캐시 무효화
     */
    public function invalidateTags(): bool
    {
        return $this->invalidateByTags(['tags', 'posts', 'stats']);
    }

    /**
     * 페이지 관련 캐시 무효화
     */
    public function invalidatePages(): bool
    {
        return $this->invalidateByTags(['pages']);
    }

    /**
     * 댓글 관련 캐시 무효화
     */
    public function invalidateComments(): bool
    {
        return $this->invalidateByTags(['comments', 'posts', 'stats']);
    }

    /**
     * 전체 블로그 캐시 삭제
     */
    public function flush(): bool
    {
        if (!config('ahhob_blog.cache.enabled', true)) {
            return true;
        }

        try {
            Cache::tags($this->defaultTags)->flush();
            return true;
        } catch (\Exception $e) {
            logger()->error('Full cache flush failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 캐시 통계 정보 반환
     */
    public function getStats(): array
    {
        return [
            'prefix' => $this->prefix,
            'default_tags' => $this->defaultTags,
            'enabled' => config('ahhob_blog.cache.enabled', true),
            'driver' => config('cache.default'),
            'ttl_settings' => [
                'posts' => config('ahhob_blog.cache.posts_ttl', 3600),
                'categories' => config('ahhob_blog.cache.categories_ttl', 7200),
                'tags' => config('ahhob_blog.cache.tags_ttl', 3600),
                'pages' => config('ahhob_blog.cache.pages_ttl', 7200),
                'comments' => config('ahhob_blog.cache.comments_ttl', 1800),
                'stats' => config('ahhob_blog.cache.stats_ttl', 1800),
                'static' => config('ahhob_blog.cache.static_ttl', 43200),
                'search' => config('ahhob_blog.cache.search_ttl', 900),
            ]
        ];
    }

    /**
     * 워밍업 - 주요 캐시 미리 로드
     */
    public function warmup(): array
    {
        $results = [];
        
        try {
            // 카테고리 캐시 워밍업
            $results['categories'] = $this->rememberCategories('tree', function () {
                return \App\Models\Blog\Category::active()
                    ->roots()
                    ->withCount(['posts' => function ($query) {
                        $query->where('status', 'published');
                    }])
                    ->orderBy('sort_order')
                    ->get();
            });

            // 인기 태그 캐시 워밍업
            $results['popular_tags'] = $this->rememberTags('popular', function () {
                return \App\Models\Blog\Tag::with('posts')
                    ->where('posts_count', '>', 0)
                    ->orderBy('posts_count', 'desc')
                    ->limit(10)
                    ->get();
            });

            // 최근 게시물 캐시 워밍업
            $results['recent_posts'] = $this->rememberPosts('recent', function () {
                return \App\Models\Blog\Post::with(['user', 'category', 'tags'])
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc')
                    ->limit(10)
                    ->get();
            });

            $results['status'] = 'success';
            
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
            
            logger()->error('Cache warmup failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $results;
    }
}