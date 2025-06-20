<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
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

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * 라우트 모델 바인딩에서 slug 사용
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

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
     * 공개된 게시물만 조회
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * 추천 게시물만 조회
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * 특정 상태의 게시물만 조회
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 특정 사용자의 게시물만 조회
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 인기 게시물 (조회수 기준)
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('views_count', 'desc')->limit($limit);
    }

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
     * 모델 이벤트
     */
    protected static function boot()
    {
        parent::boot();

        // 생성될 때
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->generateSlug();
            }
        });

        // 생성된 후
        static::created(function ($post) {
            $post->generateExcerpt();
            $post->calculateReadingTime();
        });

        // 업데이트될 때
        static::updating(function ($post) {
            if ($post->isDirty('title') && empty($post->slug)) {
                $post->generateSlug();
            }

            if ($post->isDirty('content')) {
                $post->generateExcerpt();
                $post->calculateReadingTime();
            }
        });

        // 삭제될 때 관련 데이터 정리
        static::deleting(function ($post) {
            // 댓글도 함께 삭제
            $post->comments()->delete();

            // 조회 기록도 삭제
            $post->views()->delete();

            // 태그 관계 해제
            $post->tags()->detach();
        });
    }
}
