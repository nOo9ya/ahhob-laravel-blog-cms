<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 비밀번호 히스토리 모델
 * 
 * 이 모델은 사용자의 이전 비밀번호들을 저장하여 
 * 비밀번호 재사용을 방지하는 보안 기능을 제공합니다.
 * 
 * 보안상 이유로 실제 비밀번호가 아닌 해시된 값만 저장되며,
 * 설정된 개수만큼만 보관하고 나머지는 자동으로 삭제됩니다.
 * 
 * @property int $id 고유 식별자
 * @property int $user_id 사용자 ID
 * @property string $password_hash 해시된 비밀번호
 * @property \Carbon\Carbon $created_at 생성일시
 * @property User $user 관련 사용자
 */
class PasswordHistory extends Model
{
    use HasFactory;

    /**
     * 테이블명 정의
     */
    protected $table = 'password_histories';

    /**
     * updated_at 타임스탬프 사용 안함 (이력 데이터이므로)
     */
    public $timestamps = false;

    /**
     * 대량 할당 가능한 필드들
     */
    protected $fillable = [
        'user_id',        // 사용자 ID
        'password_hash',  // 해시된 비밀번호
        'created_at'      // 생성일시
    ];

    /**
     * 캐스팅할 필드들
     */
    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * 모델 부팅 - 자동으로 created_at 설정
     */
    protected static function boot(): void
    {
        parent::boot();

        // 생성 시 자동으로 created_at 설정
        static::creating(function (PasswordHistory $passwordHistory) {
            if (!$passwordHistory->created_at) {
                $passwordHistory->created_at = now();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * 비밀번호 히스토리와 연결된 사용자
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | 스코프 (Query Scopes)
    |--------------------------------------------------------------------------
    */

    /**
     * 특정 사용자의 비밀번호 히스토리만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId 사용자 ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 최신 순으로 정렬
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 지정된 개수만큼만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $count 조회할 개수
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLimit($query, int $count)
    {
        return $query->limit($count);
    }

    /**
     * 특정 날짜 이후의 히스토리만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $date 기준 날짜
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAfterDate($query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    /*
    |--------------------------------------------------------------------------
    | 정적 메서드 (Static Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 사용자의 최근 비밀번호 히스토리 조회
     * 
     * @param int $userId 사용자 ID
     * @param int $limit 조회할 개수 (기본값: 설정에서 가져옴)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentForUser(int $userId, ?int $limit = null): \Illuminate\Database\Eloquent\Collection
    {
        $limit = $limit ?? config('security.password_policy.history.remember_count', 5);

        return static::forUser($userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * 사용자의 오래된 비밀번호 히스토리 정리
     * 
     * 설정된 개수를 초과하는 오래된 히스토리들을 삭제합니다.
     * 
     * @param int $userId 사용자 ID
     * @param int|null $keepCount 보관할 개수 (null이면 설정값 사용)
     * @return int 삭제된 레코드 수
     */
    public static function cleanupForUser(int $userId, ?int $keepCount = null): int
    {
        $keepCount = $keepCount ?? config('security.password_policy.history.remember_count', 5);

        // 보관할 레코드들의 ID 조회
        $keepIds = static::forUser($userId)
            ->latest()
            ->limit($keepCount)
            ->pluck('id');

        // 보관할 레코드를 제외한 나머지 삭제
        return static::forUser($userId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * 전체 시스템의 오래된 비밀번호 히스토리 정리
     * 
     * 모든 사용자의 오래된 비밀번호 히스토리를 정리합니다.
     * 배치 작업이나 스케줄러에서 사용할 수 있습니다.
     * 
     * @return array ['cleaned_users' => int, 'deleted_records' => int]
     */
    public static function cleanupAll(): array
    {
        $keepCount = config('security.password_policy.history.remember_count', 5);
        $cleanedUsers = 0;
        $deletedRecords = 0;

        // 비밀번호 히스토리가 있는 모든 사용자 ID 조회
        $userIds = static::distinct('user_id')->pluck('user_id');

        foreach ($userIds as $userId) {
            $deleted = static::cleanupForUser($userId, $keepCount);
            if ($deleted > 0) {
                $cleanedUsers++;
                $deletedRecords += $deleted;
            }
        }

        return [
            'cleaned_users' => $cleanedUsers,
            'deleted_records' => $deletedRecords
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 인스턴스 메서드 (Instance Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 비밀번호가 특정 값과 일치하는지 확인
     * 
     * @param string $password 확인할 비밀번호 (평문)
     * @return bool 일치 여부
     */
    public function checkPassword(string $password): bool
    {
        return \Hash::check($password, $this->password_hash);
    }

    /**
     * 히스토리 엔트리의 나이 (일 단위)
     * 
     * @return int 생성된 후 경과한 일 수
     */
    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * 히스토리 엔트리가 만료되었는지 확인
     * 
     * 비밀번호 히스토리 보관 기간이 설정되어 있는 경우,
     * 해당 기간을 초과했는지 확인합니다.
     * 
     * @return bool 만료 여부
     */
    public function isExpired(): bool
    {
        $retentionDays = config('security.password_policy.history.retention_days');
        
        if (!$retentionDays) {
            return false; // 보관 기간 설정이 없으면 만료되지 않음
        }

        return $this->getAgeInDays() > $retentionDays;
    }

    /**
     * 디버깅을 위한 문자열 표현
     * 
     * 보안상 비밀번호 해시는 표시하지 않습니다.
     * 
     * @return string
     */
    public function __toString(): string
    {
        return "PasswordHistory(user_id: {$this->user_id}, created: {$this->created_at->format('Y-m-d H:i:s')})";
    }
}