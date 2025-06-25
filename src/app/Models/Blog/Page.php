<?php

namespace App\Models\Blog;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * App\Models\Blog\Page
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $status
 * @property ?Carbon $published_at
 * @property ?string $meta_title
 * @property ?string $meta_description
 * @property ?string $keywords
 * @property ?string $og_title
 * @property ?string $og_description
 * @property ?string $og_image
 * @property ?string $canonical_url
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read User $user
 * @method static Builder|Page newModelQuery()
 * @method static Builder|Page newQuery()
 * @method static Builder|Page query()
 * @method static Builder|Page published()
 * @method static Builder|Page draft()
 */
class Page extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 모델 속성 (Attributes & Properties)
    |--------------------------------------------------------------------------
    */
    // region --- 모델 속성 (Attributes & Properties) ---

    /**
     * 대량 할당이 가능한 속성입니다.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
    ];

    /**
     * 네이티브 타입으로 캐스팅되어야 하는 속성입니다.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * 상태 상수
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';

    // endregion

    /**
     * 모델 부팅
     */
    protected static function boot()
    {
        parent::boot();

        // 생성 시 슬러그 자동 생성
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }

            // 중복 슬러그 처리
            $originalSlug = $page->slug;
            $counter = 1;

            while (static::where('slug', $page->slug)->exists()) {
                $page->slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // 정렬 순서 자동 설정
            if (is_null($page->sort_order)) {
                $maxOrder = static::max('sort_order') ?? 0;
                $page->sort_order = $maxOrder + 1;
            }
        });

        // 업데이트 시 슬러그 중복 확인
        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->getOriginal('slug'))) {
                $page->slug = Str::slug($page->title);
            }

            // 중복 슬러그 처리 (자신 제외)
            if ($page->isDirty('slug')) {
                $originalSlug = $page->slug;
                $counter = 1;

                while (static::where('slug', $page->slug)
                    ->where('id', '!=', $page->id)
                    ->exists()) {
                    $page->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

    /**
     * 이 페이지를 소유한 사용자를 가져옵니다.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 'published' 상태인 페이지만 포함하도록 쿼리의 범위를 지정합니다.
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * 'draft' 상태인 페이지만 포함하도록 쿼리의 범위를 지정합니다.
     */
    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    /**
     * 활성화된 페이지만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 정렬 순서대로 조회
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('title', 'asc');
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 라우트 모델 바인딩 (Route Model Binding)
    |--------------------------------------------------------------------------
    */
    // region --- 라우트 모델 바인딩 (Route Model Binding) ---

    /**
     * 모델의 라우트 키를 가져옵니다.
     * URL에서 ID 대신 slug를 사용하게 합니다. (e.g., /pages/about-us)
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 상태 목록 반환
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => '임시저장',
            self::STATUS_PUBLISHED => '발행',
        ];
    }

    /**
     * 상태 레이블 반환
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * URL 반환
     */
    public function getUrlAttribute(): string
    {
        return route('pages.show', $this->slug);
    }

    /**
     * 메타 제목 반환 (없으면 제목 사용)
     */
    public function getMetaTitleAttribute($value): string
    {
        return $value ?: $this->title;
    }

    /**
     * 메타 설명 반환 (없으면 요약 사용)
     */
    public function getMetaDescriptionAttribute($value): string
    {
        return $value ?: $this->excerpt;
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 헬퍼 메서드 (Helper Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 헬퍼 메서드 (Helper Methods) ---

    /**
     * 페이지가 현재 발행된 상태인지 확인합니다.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }

    /**
     * 페이지가 임시 저장 상태인지 확인합니다.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    // endregion
}
