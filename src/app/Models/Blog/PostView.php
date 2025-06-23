<?php

namespace App\Models\Blog;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'city',
        'device_type',
        'browser',
    ];

    /**
     * 조회된 게시물
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * 조회한 사용자 (회원인 경우)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 특정 기간의 조회 기록
     */
    public function scopeInPeriod($query, string $start, string $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * 특정 디바이스 타입의 조회 기록
     */
    public function scopeByDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * 특정 브라우저의 조회 기록
     */
    public function scopeByBrowser($query, string $browser)
    {
        return $query->where('browser', $browser);
    }

    /**
     * 오늘의 조회 기록
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 이번 주의 조회 기록
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * 이번 달의 조회 기록
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * 고유 방문자 수 계산 (IP 기준)
     */
    public static function uniqueVisitors($postId = null, $period = null)
    {
        $query = static::query();

        if ($postId) {
            $query->where('post_id', $postId);
        }

        if ($period) {
            switch ($period) {
                case 'today':
                    $query->today();
                    break;
                case 'week':
                    $query->thisWeek();
                    break;
                case 'month':
                    $query->thisMonth();
                    break;
            }
        }

        return $query->distinct('ip_address')->count();
    }
}
