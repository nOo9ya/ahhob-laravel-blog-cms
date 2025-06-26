<?php

namespace App\Models\Blog;

use App\Models\User;
use App\Services\Ahhob\Blog\Shared\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'depth' => 'integer',
        'likes_count' => 'integer',
        'replies_count' => 'integer',
        'is_pinned' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * 모델 부팅 - 이벤트 리스너 등록
     * 
     * 댓글 생성, 수정, 삭제 시 자동으로 실행되는 로직을 정의합니다.
     * - 계층 구조 경로 자동 계산
     * - 댓글 수 업데이트
     * - 캐시 무효화
     * - 부모-자식 관계 정리
     */
    protected static function boot(): void
    {
        parent::boot();

        // 생성 전 처리
        static::creating(function (Comment $comment) {
            // 캐시 무효화
            $comment->invalidateCache();
        });

        // 생성 후 처리
        static::created(function (Comment $comment) {
            // 계층 구조 경로 설정
            $comment->updatePath();
            
            // 부모 댓글의 대댓글 수 증가
            if ($comment->parent) {
                $comment->parent->increment('replies_count');
            }
        });

        // 수정 전 처리
        static::updating(function (Comment $comment) {
            // 캐시 무효화
            $comment->invalidateCache();
        });

        // 수정 후 처리
        static::updated(function (Comment $comment) {
            // 상태가 변경된 경우 게시물 댓글 수 업데이트
            if ($comment->wasChanged('status')) {
                $comment->updatePostCommentsCount();
            }
        });

        // 삭제 전 처리
        static::deleting(function (Comment $comment) {
            // 캐시 무효화
            $comment->invalidateCache();
            
            // 자식 댓글들도 함께 삭제 (소프트 삭제)
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

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */
    // region --- 관계 (Relationships) ---

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

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 쿼리 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */
    // region --- 쿼리 스코프 (Query Scopes) ---

    /**
     * 승인된 댓글만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * 승인 대기 중인 댓글만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * 최상위 댓글만 조회 (대댓글 제외)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRoots(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 특정 깊이의 댓글만 조회
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

    // endregion

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */
    // region --- 공개 메서드 (Public Methods) ---

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
     * 댓글 경로 업데이트 (계층 구조 관리)
     * 
     * @return void
     */
    public function updatePath(): void
    {
        if ($this->parent_id) {
            $parent = $this->parent;
            if ($parent) {
                $this->path = $parent->path ? $parent->path . '/' . $this->id : (string) $this->id;
                $this->depth = $parent->depth + 1;
            }
        } else {
            $this->path = (string) $this->id;
            $this->depth = 0;
        }

        $this->saveQuietly(); // 이벤트 트리거 없이 저장
    }

    /**
     * 게시물의 댓글 수 업데이트
     * 
     * @return void
     */
    public function updatePostCommentsCount(): void
    {
        $originalStatus = $this->getOriginal('status');
        $newStatus = $this->status;

        // 승인됨 -> 다른 상태: 댓글 수 감소
        if ($originalStatus === 'approved' && $newStatus !== 'approved') {
            $this->post->decrement('comments_count');
        }
        // 다른 상태 -> 승인됨: 댓글 수 증가
        elseif ($originalStatus !== 'approved' && $newStatus === 'approved') {
            $this->post->increment('comments_count');
        }
    }

    /**
     * 댓글 관련 캐시 무효화
     * 
     * @return void
     */
    public function invalidateCache(): void
    {
        if (config('ahhob_blog.cache.auto_invalidate.on_comment_save', true)) {
            $cacheService = app(CacheService::class);
            
            // 게시물 캐시 무효화 (댓글이 포함된 게시물)
            $cacheService->invalidatePosts();
            
            // 댓글 관련 캐시 무효화
            $cacheService->invalidateByTags(['comments']);
            
            // 정적 콘텐츠 캐시 무효화
            $cacheService->invalidateByTags(['static']);
        }
    }

    // endregion
}
