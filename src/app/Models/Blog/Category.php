<?php

namespace App\Models\Blog;

use App\Services\Ahhob\Blog\Shared\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

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
        'icon',
        'parent_id',
        'depth',
        'path',
        'children_count',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'depth' => 'integer',
        'children_count' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * 모델 부팅 - 이벤트 리스너 등록
     * 
     * 카테고리 생성, 수정, 삭제 시 자동으로 실행되는 로직을 정의합니다.
     * - 계층 구조 경로 자동 계산
     * - 하위 카테고리 수 업데이트
     * - 캐시 무효화
     * - 부모-자식 관계 정리
     */
    protected static function boot(): void
    {
        parent::boot();

        // 생성 전 처리
        static::creating(function (Category $category) {
            // 캐시 무효화
            $category->invalidateCache();
        });

        // 생성 후 처리
        static::created(function (Category $category) {
            // 경로 및 깊이 계산
            $category->updatePath();
            
            // 부모 카테고리의 하위 수 업데이트
            if ($category->parent) {
                $category->parent->updateChildrenCount();
            }
        });

        // 수정 전 처리
        static::updating(function (Category $category) {
            // 캐시 무효화
            $category->invalidateCache();
        });

        // 수정 후 처리
        static::updated(function (Category $category) {
            // 부모가 변경된 경우 경로 재계산
            if ($category->wasChanged('parent_id')) {
                $category->updatePath();

                // 이전 부모의 하위 수 업데이트
                $originalParentId = $category->getOriginal('parent_id');
                if ($originalParentId) {
                    $originalParent = Category::find($originalParentId);
                    if ($originalParent) {
                        $originalParent->updateChildrenCount();
                    }
                }
                
                // 새 부모의 하위 수 업데이트
                if ($category->parent) {
                    $category->parent->updateChildrenCount();
                }
            }
        });

        // 삭제 전 처리
        static::deleting(function (Category $category) {
            // 캐시 무효화
            $category->invalidateCache();
            
            // 하위 카테고리들 처리 (부모를 null로 설정하거나 다른 부모로 이동)
            $category->children()->update(['parent_id' => $category->parent_id]);
        });

        // 삭제 후 처리
        static::deleted(function (Category $category) {
            // 부모의 하위 수 업데이트
            if ($category->parent) {
                $category->parent->updateChildrenCount();
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
     * 부모 카테고리 관계
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 직접 하위 카테고리들
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * 모든 하위 카테고리들 (재귀적)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * 해당 카테고리의 게시물들
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 최상위 카테고리들만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRoots(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 활성화된 카테고리들만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 특정 깊이의 카테고리들만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $depth 조회할 깊이
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDepth(\Illuminate\Database\Eloquent\Builder $query, int $depth): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('depth', $depth);
    }

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 접근자 & 변경자 (Accessors & Mutators)
    |--------------------------------------------------------------------------
    */
    // region --- 접근자 & 변경자 (Accessors & Mutators) ---

    /**
     * 전체 경로명 가져오기 (부모 > 자식 형태)
     */
    public function getFullNameAttribute(): string
    {
        if (!$this->parent) {
            return $this->name;
        }

        return $this->parent->full_name . ' > ' . $this->name;
    }

    // endregion

    // 중복된 boot 메서드가 제거되었습니다. 위의 통합된 boot 메서드를 사용합니다.

    /*
    |--------------------------------------------------------------------------
    | 캐시 관련 메서드
    |--------------------------------------------------------------------------
    */
    // region --- 캐시 관련 메서드 ---

    /**
     * 카테고리 관련 캐시 무효화
     */
    public function invalidateCache(): void
    {
        if (config('ahhob_blog.cache.auto_invalidate.on_category_save', true)) {
            $cacheService = app(CacheService::class);
            $cacheService->invalidateCategories();
            
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
     * 계층 경로 업데이트
     */
    public function updatePath(): void
    {
        if ($this->parent_id) {
            $parent = $this->parent;
            $this->path = $parent->path ? $parent->path . '/' . $this->id : (string) $this->id;
            $this->depth = $parent->depth + 1;
        } else {
            $this->path = (string) $this->id;
            $this->depth = 0;
        }

        $this->saveQuietly(); // 이벤트 트리거 없이 저장
    }

    /**
     * 하위 카테고리 수 업데이트
     */
    public function updateChildrenCount(): void
    {
        $this->children_count = $this->children()->count();
        $this->saveQuietly();

        // 부모 카테고리도 업데이트
        if ($this->parent) {
            $this->parent->updateChildrenCount();
        }
    }

    /**
     * 카테고리가 특정 카테고리의 하위인지 확인
     */
    public function isChildOf(Category $category): bool
    {
        return $this->path && str_starts_with($this->path, $category->path . '/');
    }

    // endregion
}
