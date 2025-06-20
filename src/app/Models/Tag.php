<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'posts_count',
        'is_featured',
    ];

    protected $casts = [
        'posts_count' => 'integer',
        'is_featured' => 'boolean',
    ];

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

    /**
     * 인기 태그 조회 (포스트 수 기준)
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('posts_count', 'desc')->limit($limit);
    }

    /**
     * 추천 태그 조회
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * 포스트 수 업데이트
     */
    public function updatePostsCount(): void
    {
        $this->posts_count = $this->publishedPosts()->count();
        $this->saveQuietly();
    }
}
