<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'website',
        'social_twitter',
        'social_github',
        'social_linkedin',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * 라우트 모델 바인딩에서 username 사용
     */
    public function getRouteKeyName(): string
    {
        return 'username';
    }

    /**
     * 작성한 게시물들
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * 공개된 게시물들만
     */
    public function publishedPosts(): HasMany
    {
        return $this->posts()->where('status', 'published');
    }

    /**
     * 작성한 댓글들
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 관리자인지 확인
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * 작성자인지 확인
     */
    public function isWriter(): bool
    {
        return in_array($this->role, ['admin', 'writer']);
    }

    /**
     * 활성 사용자만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 특정 역할의 사용자만 조회
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
