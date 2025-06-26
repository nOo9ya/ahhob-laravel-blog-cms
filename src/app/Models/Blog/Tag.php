<?php

namespace App\Models\Blog;

use App\Services\Ahhob\Blog\Shared\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'posts_count',
        'is_featured',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'posts_count' => 'integer',
        'is_featured' => 'boolean',
    ];

    /**
     * 모델 부팅 - 이벤트 리스너 등록
     * 
     * 태그 생성, 수정, 삭제 시 자동으로 실행되는 로직을 정의합니다.
     * - 게시물 수 업데이트
     * - 캐시 무효화
     * - 관련 데이터 정리
     */
    protected static function boot(): void
    {
        parent::boot();

        // 생성 전 처리
        static::creating(function (Tag $tag) {
            // 캐시 무효화
            $tag->invalidateCache();
        });

        // 생성 후 처리
        static::created(function (Tag $tag) {
            // 게시물 수 초기화
            $tag->posts_count = 0;
            $tag->saveQuietly();
        });

        // 수정 전 처리
        static::updating(function (Tag $tag) {
            // 캐시 무효화
            $tag->invalidateCache();
        });

        // 삭제 전 처리
        static::deleting(function (Tag $tag) {
            // 캐시 무효화
            $tag->invalidateCache();
            
            // 게시물과의 관계 해제
            $tag->posts()->detach();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 해당 태그를 사용하는 게시물들
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    /**
     * 게시된 글들만 조회
     */
    public function publishedPosts(): BelongsToMany
    {
        return $this->posts()->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 인기 태그 조회 (포스트 수 기준)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit 조회할 개수
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular(\Illuminate\Database\Eloquent\Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('posts_count', 'desc')->limit($limit);
    }

    /**
     * 추천 태그 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_featured', true);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 포스트 수 업데이트
     */
    public function updatePostsCount(): void
    {
        $this->posts_count = $this->publishedPosts()->count();
        $this->saveQuietly();
    }

    /**
     * 태그 관련 캐시 무효화
     * 
     * @return void
     */
    public function invalidateCache(): void
    {
        if (config('ahhob_blog.cache.auto_invalidate.on_tag_save', true)) {
            $cacheService = app(CacheService::class);
            $cacheService->invalidateByTags(['tags']);
            
            // 정적 콘텐츠 캐시 무효화 (RSS, Sitemap 등)
            $cacheService->invalidateByTags(['static']);
        }
    }

    // endregion
}
