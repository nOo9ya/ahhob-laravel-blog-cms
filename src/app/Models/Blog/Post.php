<?php

namespace App\Models\Blog;

use App\Models\User;
use App\Services\Ahhob\Blog\Shared\MarkdownService;
use App\Services\Ahhob\Blog\Shared\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'content_html',
        'featured_image',
        'status',
        'user_id',
        'category_id',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'meta_keywords',
        'canonical_url',
        'index_follow',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'is_featured',
        'allow_comments',
        'reading_time',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta_keywords' => 'array',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'index_follow' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'reading_time' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * 모델 부팅
     * 
     * Observer 패턴을 사용하여 이벤트 처리를 분리했습니다.
     * 비즈니스 로직은 PostObserver에서 처리됩니다.
     * 
     * @see \App\Observers\Blog\PostObserver
     */
    protected static function boot(): void
    {
        parent::boot();
        // Observer는 AppServiceProvider에서 등록됩니다.
    }

    /**
     * 라우트 모델 바인딩에서 slug 사용
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 게시물 작성자
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 게시물 카테고리
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 게시물 태그들
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * 게시물 댓글들
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 승인된 댓글들만
     */
    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', 'approved');
    }

    /**
     * 게시물 조회 기록
     */
    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    /**
     * 이 포스트에 '좋아요'를 누른 사용자들
     */
    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_user')->withTimestamps();
    }
    
    /**
     * 게시물과 연결된 이미지들 (다형성 관계)
     * 
     * 이 관계는 다음과 같은 이미지들을 포함합니다:
     * - 게시물 본문에 삽입된 콘텐츠 이미지
     * - 대표 이미지(featured image)
     * - Open Graph 이미지
     * - 기타 게시물과 관련된 모든 이미지
     * 
     * @return MorphMany 이미지 컬렉션
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    
    /**
     * 게시물의 대표 이미지 (featured image)
     * 
     * @return MorphMany 대표 이미지만 필터링
     */
    public function featuredImages(): MorphMany
    {
        return $this->images()->where('alt_text', 'LIKE', '%featured%');
    }
    
    /**
     * 게시물 본문에 사용된 콘텐츠 이미지들
     * 
     * @return MorphMany 콘텐츠 이미지만 필터링
     */
    public function contentImages(): MorphMany
    {
        return $this->images()->whereNotNull('imageable_id')->where('imageable_id', '>', 0);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 공개된 게시물만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * 추천 게시물만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * 특정 상태의 게시물만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus(\Illuminate\Database\Eloquent\Builder $query, string $status): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', $status);
    }

    /**
     * 특정 사용자의 게시물만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 인기 게시물 (조회수 기준)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular(\Illuminate\Database\Eloquent\Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('views_count', 'desc')->limit($limit);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * SEO 메타 제목 (없으면 기본 제목 사용)
     */
    public function getMetaTitleAttribute(): string
    {
        return $this->attributes['meta_title'] ?: $this->title;
    }

    /**
     * SEO 메타 설명 (없으면 발췌문 사용)
     */
    public function getMetaDescriptionAttribute(): string
    {
        return $this->attributes['meta_description'] ?:
            ($this->excerpt ?: Str::limit(strip_tags($this->content), 160));
    }

    /**
     * OG 제목 (없으면 메타 제목 사용)
     */
    public function getOgTitleAttribute(): string
    {
        return $this->attributes['og_title'] ?: $this->meta_title;
    }

    /**
     * OG 설명 (없으면 메타 설명 사용)
     */
    public function getOgDescriptionAttribute(): string
    {
        return $this->attributes['og_description'] ?: $this->meta_description;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 부팅 (Boot)
    |--------------------------------------------------------------------------
    */
    // region --- 부팅 (Boot) ---


    // endregion

    /*
    |--------------------------------------------------------------------------
    | 캐시 관련 메서드
    |--------------------------------------------------------------------------
    */
    // region --- 캐시 관련 메서드 ---

    /**
     * 게시물 관련 캐시 무효화
     */
    public function invalidateCache(): void
    {
        if (config('ahhob_blog.cache.auto_invalidate.on_post_save', true)) {
            $cacheService = app(CacheService::class);
            $cacheService->invalidatePosts();
            
            // 카테고리 변경된 경우 카테고리 캐시도 무효화
            if ($this->isDirty('category_id')) {
                $cacheService->invalidateCategories();
            }
            
            // 정적 콘텐츠 캐시 무효화 (RSS, Sitemap 등)
            $cacheService->invalidateByTags(['static']);
        }
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

    /**
     * 읽기 시간 계산 (단어 수 기준)
     */
    public function calculateReadingTime(): void
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $this->reading_time = max(1, ceil($wordCount / 200)); // 분당 200단어 기준
        $this->saveQuietly();
    }

    /**
     * 발췌문 자동 생성
     */
    public function generateExcerpt(int $length = 200): void
    {
        if (empty($this->excerpt)) {
            $this->excerpt = Str::limit(strip_tags($this->content), $length);
            $this->saveQuietly();
        }
    }

    /**
     * 슬러그 자동 생성
     */
    public function generateSlug(): void
    {
        if (empty($this->slug)) {
            $baseSlug = Str::slug($this->title);
            $slug = $baseSlug;
            $counter = 1;

            while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $this->slug = $slug;
            $this->saveQuietly();
        }
    }

    /**
     * 게시물이 공개되었는지 확인
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' &&
            $this->published_at &&
            $this->published_at <= now();
    }

    /**
     * 사용자가 이 게시물을 수정할 수 있는지 확인
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) return false;

        return $user->id === $this->user_id ||
            in_array($user->role, ['admin', 'writer']);
    }

    /**
     * 현재 인증된 사용자가 이 포스트를 좋아하는지 확인합니다.
     */
    public function isLikedByCurrentUser(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // 'likers' 관계가 이미 로드되었는지 확인하여 불필요한 쿼리를 방지합니다.
        if ($this->relationLoaded('likers')) {
            return $this->likers->contains(Auth::user());
        }

        return $this->likers()->where('user_id', Auth::id())->exists();
    }

    /**
     * 마크다운을 HTML로 변환
     */
    public function convertMarkdownToHtml(): void
    {
        if (!empty($this->content)) {
            $markdownService = app(MarkdownService::class);
            $this->content_html = $markdownService->toHtml($this->content);
        }
    }

    /**
     * 렌더링된 HTML 콘텐츠 반환 (프론트엔드에서 사용)
     */
    public function getRenderedContent(): string
    {
        return $this->content_html ?: $this->content;
    }

    // endregion
}
