<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $casts = [
        'is_active' => 'boolean',
        'depth' => 'integer',
        'children_count' => 'integer',
        'sort_order' => 'integer',
    ];

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

    /**
     * 최상위 카테고리들만 조회
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 활성화된 카테고리들만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 특정 깊이의 카테고리들만 조회
     */
    public function scopeByDepth($query, int $depth)
    {
        return $query->where('depth', $depth);
    }

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
     * 전체 경로명 가져오기 (부모 > 자식 형태)
     */
    public function getFullNameAttribute(): string
    {
        if (!$this->parent) {
            return $this->name;
        }

        return $this->parent->full_name . ' > ' . $this->name;
    }

    /**
     * 카테고리가 특정 카테고리의 하위인지 확인
     */
    public function isChildOf(Category $category): bool
    {
        return $this->path && str_starts_with($this->path, $category->path . '/');
    }

    /**
     * 모델 이벤트
     */
    protected static function boot(): void
    {
        parent::boot();

        // 생성될 때 경로 업데이트
        static::created(function ($category) {
            $category->updatePath();
            if ($category->parent) {
                $category->parent->updateChildrenCount();
            }
        });

        // 업데이트될 때 경로 재계산 (parent_id 변경 시)
        static::updated(function ($category) {
            if ($category->wasChanged('parent_id')) {
                $category->updatePath();

                // 이전 부모와 새 부모의 children_count 업데이트
                if ($category->getOriginal('parent_id')) {
                    Category::find($category->getOriginal('parent_id'))->updateChildrenCount();
                }
                if ($category->parent) {
                    $category->parent->updateChildrenCount();
                }
            }
        });

        // 삭제될 때 부모의 children_count 업데이트
        static::deleted(function ($category) {
            if ($category->parent) {
                $category->parent->updateChildrenCount();
            }
        });
    }
}
