<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'author_name',
        'author_email',
        'author_website',
        'content',
        'parent_id',
        'depth',
        'path',
        'status',
        'ip_address',
        'user_agent',
        'likes_count',
        'replies_count',
        'is_pinned',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'depth' => 'integer',
        'likes_count' => 'integer',
        'replies_count' => 'integer',
        'is_pinned' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * 댓글이 속한 게시물
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * 댓글 작성자 (회원인 경우)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 부모 댓글
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * 대댓글들
     */
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * 모든 하위 댓글들 (재귀적)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * 댓글 승인자
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 승인된 댓글만 조회
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * 승인 대기 중인 댓글만 조회
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 최상위 댓글만 조회 (대댓글 제외)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 특정 깊이의 댓글만 조회
     */
    public function scopeByDepth($query, int $depth)
    {
        return $query->where('depth', $depth);
    }

    /**
     * 댓글 승인
     */
    public function approve(?User $approver = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver?->id,
        ]);

        // 게시물 댓글 수 증가
        $this->post->increment('comments_count');
    }

    /**
     * 댓글 거부
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);

        // 이미 승인되었던 댓글이라면 게시물 댓글 수 감소
        if ($this->getOriginal('status') === 'approved') {
            $this->post->decrement('comments_count');
        }
    }

    /**
     * 스팸으로 표시
     */
    public function markAsSpam(): void
    {
        $this->update(['status' => 'spam']);

        // 이미 승인되었던 댓글이라면 게시물 댓글 수 감소
        if ($this->getOriginal('status') === 'approved') {
            $this->post->decrement('comments_count');
        }
    }

    /**
     * 댓글 작성자 이름 (회원/비회원 구분)
     */
    public function getAuthorNameAttribute(): string
    {
        return $this->user ? $this->user->name : $this->attributes['author_name'];
    }

    /**
     * 댓글 작성자 이메일 (회원/비회원 구분)
     */
    public function getAuthorEmailAttribute(): string
    {
        return $this->user ? $this->user->email : $this->attributes['author_email'];
    }

    /**
     * 댓글이 회원 댓글인지 확인
     */
    public function isFromRegisteredUser(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * 사용자가 이 댓글을 수정할 수 있는지 확인
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

        // 생성될 때 경로 설정
        static::created(function ($comment) {
            if ($comment->parent_id) {
                $parent = $comment->parent;
                $comment->path = $parent->path ? $parent->path . '/' . $comment->id : (string) $comment->id;
                $comment->depth = $parent->depth + 1;
                $comment->saveQuietly();

                // 부모 댓글의 대댓글 수 증가
                $parent->increment('replies_count');
            } else {
                $comment->path = (string) $comment->id;
                $comment->depth = 0;
                $comment->saveQuietly();
            }
        });

        // 삭제될 때
        static::deleting(function ($comment) {
            // 자식 댓글들도 함께 삭제
            $comment->children()->delete();

            // 부모 댓글의 대댓글 수 감소
            if ($comment->parent) {
                $comment->parent->decrement('replies_count');
            }

            // 승인된 댓글이었다면 게시물 댓글 수 감소
            if ($comment->status === 'approved') {
                $comment->post->decrement('comments_count');
            }
        });
    }
}
