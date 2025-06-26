<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Services\Ahhob\Blog\Shared\CacheService;

/**
 * 이미지 모델
 * 
 * 이 모델은 업로드된 이미지의 메타데이터와 관련 정보를 저장합니다.
 * 다형성 관계를 통해 게시물, 사용자 프로필 등 다양한 엔티티와 연결될 수 있습니다.
 * 
 * @property int $id 고유 식별자
 * @property string $original_name 원본 파일명
 * @property string $file_name 저장된 파일명
 * @property string $file_path 파일 경로 (storage/app/public 기준)
 * @property string $disk 저장 디스크 (기본: public)
 * @property string $mime_type MIME 타입
 * @property int $file_size 파일 크기 (바이트)
 * @property int $width 이미지 너비 (픽셀)
 * @property int $height 이미지 높이 (픽셀)
 * @property array $thumbnails 썸네일 정보 (JSON)
 * @property array $metadata 메타데이터 (JSON)
 * @property string $alt_text 대체 텍스트 (접근성)
 * @property string $caption 이미지 캡션
 * @property string $imageable_type 연결된 모델 타입
 * @property int $imageable_id 연결된 모델 ID
 * @property \Carbon\Carbon $created_at 생성일시
 * @property \Carbon\Carbon $updated_at 수정일시
 * @property \Carbon\Carbon $deleted_at 삭제일시 (소프트 삭제)
 */
class Image extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * 테이블명 정의
     */
    protected $table = 'images';

    /**
     * 대량 할당 가능한 필드들
     */
    protected $fillable = [
        'original_name',     // 원본 파일명
        'file_name',         // 저장된 파일명  
        'file_path',         // 파일 경로
        'disk',              // 저장 디스크
        'mime_type',         // MIME 타입
        'file_size',         // 파일 크기
        'width',             // 이미지 너비
        'height',            // 이미지 높이
        'thumbnails',        // 썸네일 정보
        'metadata',          // 메타데이터
        'alt_text',          // 대체 텍스트
        'caption',           // 이미지 캡션
        'imageable_type',    // 다형성 관계 - 모델 타입
        'imageable_id',      // 다형성 관계 - 모델 ID
    ];

    /**
     * JSON으로 캐스팅할 필드들
     * 
     * thumbnails: 썸네일 정보를 배열로 저장
     * metadata: EXIF 데이터 등 메타정보를 배열로 저장
     */
    protected $casts = [
        'thumbnails' => 'array',
        'metadata' => 'array',
        'file_size' => 'integer',
        'width' => 'integer', 
        'height' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | 관계 (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * 다형성 관계 - 이미지가 속한 모델
     * 
     * 게시물, 사용자, 카테고리 등 다양한 모델과 연결 가능
     * 
     * @return MorphTo
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | 스코프 (Query Scopes)  
    |--------------------------------------------------------------------------
    */

    /**
     * 특정 MIME 타입의 이미지만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $mimeTypes MIME 타입 (단일 또는 배열)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMimeType($query, $mimeTypes)
    {
        if (is_array($mimeTypes)) {
            return $query->whereIn('mime_type', $mimeTypes);
        }
        
        return $query->where('mime_type', $mimeTypes);
    }

    /**
     * 특정 크기 이상의 이미지만 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $width 최소 너비
     * @param int $height 최소 높이
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMinSize($query, int $width = 0, int $height = 0)
    {
        if ($width > 0) {
            $query->where('width', '>=', $width);
        }
        
        if ($height > 0) {
            $query->where('height', '>=', $height);
        }
        
        return $query;
    }

    /**
     * 파일 크기 범위로 조회
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minSize 최소 크기 (바이트)
     * @param int $maxSize 최대 크기 (바이트)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySizeRange($query, int $minSize = 0, int $maxSize = 0)
    {
        if ($minSize > 0) {
            $query->where('file_size', '>=', $minSize);
        }
        
        if ($maxSize > 0) {
            $query->where('file_size', '<=', $maxSize);
        }
        
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | 접근자 (Accessors)
    |--------------------------------------------------------------------------
    */

    /**
     * 이미지 URL 접근자
     * 
     * 저장된 이미지의 공개 URL을 반환합니다.
     * 
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->url($this->file_path);
    }

    /**
     * 파일 크기를 사람이 읽기 쉬운 형태로 반환
     * 
     * @return string (예: "1.5 MB", "256 KB")
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 이미지 비율 계산
     * 
     * @return float
     */
    public function getAspectRatioAttribute(): float
    {
        return $this->height > 0 ? round($this->width / $this->height, 2) : 0;
    }

    /**
     * 이미지가 가로형인지 확인
     * 
     * @return bool
     */
    public function getIsLandscapeAttribute(): bool
    {
        return $this->width > $this->height;
    }

    /**
     * 이미지가 세로형인지 확인
     * 
     * @return bool
     */
    public function getIsPortraitAttribute(): bool
    {
        return $this->height > $this->width;
    }

    /**
     * 이미지가 정사각형인지 확인
     * 
     * @return bool
     */
    public function getIsSquareAttribute(): bool
    {
        return $this->width === $this->height;
    }

    /*
    |--------------------------------------------------------------------------
    | 모델 이벤트 (Model Events)
    |--------------------------------------------------------------------------
    */

    /**
     * 모델 부팅
     * 
     * 이미지 생성/수정/삭제 시 관련 캐시를 무효화합니다.
     */
    protected static function boot(): void
    {
        parent::boot();

        // 이미지 생성 시 캐시 무효화
        static::created(function (Image $image) {
            $image->invalidateRelatedCache();
        });

        // 이미지 수정 시 캐시 무효화
        static::updated(function (Image $image) {
            $image->invalidateRelatedCache();
        });

        // 이미지 삭제 시 실제 파일도 함께 삭제
        static::deleting(function (Image $image) {
            $image->deletePhysicalFiles();
            $image->invalidateRelatedCache();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 공개 메서드 (Public Methods)
    |--------------------------------------------------------------------------
    */

    /**
     * 특정 크기의 썸네일 URL 반환
     * 
     * @param string $size 썸네일 크기 (thumbnail, small, medium, large)
     * @return string|null 썸네일 URL 또는 null
     */
    public function getThumbnailUrl(string $size = 'thumbnail'): ?string
    {
        // 썸네일 정보가 없는 경우 원본 이미지 반환
        if (!$this->thumbnails || !isset($this->thumbnails[$size])) {
            return $this->url;
        }

        $thumbnailInfo = $this->thumbnails[$size];
        
        // 썸네일 파일이 실제로 존재하는지 확인
        $disk = $this->disk ?? 'public';
        if (!Storage::disk($disk)->exists($thumbnailInfo['file_path'])) {
            // 썸네일이 없으면 원본 이미지 반환
            return $this->url;
        }

        return Storage::disk($disk)->url($thumbnailInfo['file_path']);
    }

    /**
     * 사용 가능한 모든 썸네일 크기 반환
     * 
     * @return array 썸네일 크기 목록
     */
    public function getAvailableThumbnailSizes(): array
    {
        return $this->thumbnails ? array_keys($this->thumbnails) : [];
    }

    /**
     * 반응형 이미지 srcset 생성
     * 
     * 다양한 화면 크기에 대응하는 srcset 속성값을 생성합니다.
     * 
     * @return string srcset 속성값
     */
    public function getResponsiveSrcset(): string
    {
        $srcset = [];
        
        // 썸네일들을 너비 순으로 정렬
        $thumbnails = $this->thumbnails ?? [];
        $sortedThumbnails = [];
        
        foreach ($thumbnails as $size => $info) {
            $sortedThumbnails[$info['width']] = [
                'size' => $size,
                'url' => $this->getThumbnailUrl($size),
                'width' => $info['width']
            ];
        }
        
        // 너비 순으로 정렬
        ksort($sortedThumbnails);
        
        // srcset 문자열 생성
        foreach ($sortedThumbnails as $thumbnail) {
            $srcset[] = $thumbnail['url'] . ' ' . $thumbnail['width'] . 'w';
        }
        
        // 원본 이미지도 추가
        $srcset[] = $this->url . ' ' . $this->width . 'w';
        
        return implode(', ', $srcset);
    }

    /**
     * 이미지 최적화 정보 반환
     * 
     * @return array 최적화 통계
     */
    public function getOptimizationInfo(): array
    {
        $thumbnails = $this->thumbnails ?? [];
        $totalThumbnailSize = 0;
        
        foreach ($thumbnails as $thumbnail) {
            $totalThumbnailSize += $thumbnail['size'] ?? 0;
        }
        
        return [
            'original_size' => $this->file_size,
            'total_thumbnail_size' => $totalThumbnailSize,
            'total_storage_used' => $this->file_size + $totalThumbnailSize,
            'thumbnail_count' => count($thumbnails),
            'savings_ratio' => $this->file_size > 0 ? 
                round((1 - $totalThumbnailSize / $this->file_size) * 100, 2) : 0
        ];
    }

    /**
     * 물리적 파일 삭제
     * 
     * 원본 이미지와 모든 썸네일 파일을 실제 저장소에서 삭제합니다.
     * 
     * @return bool 삭제 성공 여부
     */
    public function deletePhysicalFiles(): bool
    {
        try {
            $disk = Storage::disk($this->disk ?? 'public');
            $deletedCount = 0;

            // 1. 원본 파일 삭제
            if ($disk->exists($this->file_path)) {
                $disk->delete($this->file_path);
                $deletedCount++;
            }

            // 2. 썸네일 파일들 삭제
            if ($this->thumbnails) {
                foreach ($this->thumbnails as $size => $thumbnailInfo) {
                    if (isset($thumbnailInfo['file_path']) && $disk->exists($thumbnailInfo['file_path'])) {
                        $disk->delete($thumbnailInfo['file_path']);
                        $deletedCount++;
                    }
                }
            }

            // 로깅
            logger()->info('Image files deleted', [
                'image_id' => $this->id,
                'original_path' => $this->file_path,
                'deleted_files' => $deletedCount
            ]);

            return true;

        } catch (\Exception $e) {
            // 삭제 실패 로깅
            logger()->error('Failed to delete image files', [
                'image_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 관련 캐시 무효화
     * 
     * 이미지와 연관된 캐시를 무효화합니다.
     */
    protected function invalidateRelatedCache(): void
    {
        try {
            $cacheService = app(CacheService::class);
            
            // 이미지 관련 캐시 무효화
            $cacheService->invalidateByTags(['images']);
            
            // 연관된 모델의 캐시도 무효화
            if ($this->imageable) {
                $modelType = class_basename($this->imageable_type);
                
                switch ($modelType) {
                    case 'Post':
                        $cacheService->invalidatePosts();
                        break;
                    case 'User':
                        $cacheService->invalidateByTags(['users']);
                        break;
                    case 'Category':
                        $cacheService->invalidateCategories();
                        break;
                }
            }

        } catch (\Exception $e) {
            // 캐시 무효화 실패는 로깅만 하고 계속 진행
            logger()->warning('Cache invalidation failed for image', [
                'image_id' => $this->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 이미지 메타데이터 업데이트
     * 
     * @param array $metadata 새로운 메타데이터
     * @return bool 업데이트 성공 여부
     */
    public function updateMetadata(array $metadata): bool
    {
        try {
            $currentMetadata = $this->metadata ?? [];
            $this->metadata = array_merge($currentMetadata, $metadata);
            
            return $this->save();
            
        } catch (\Exception $e) {
            logger()->error('Failed to update image metadata', [
                'image_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 이미지 캡션 및 대체 텍스트 업데이트
     * 
     * @param string|null $caption 이미지 캡션
     * @param string|null $altText 대체 텍스트
     * @return bool 업데이트 성공 여부
     */
    public function updateCaptionAndAlt(?string $caption = null, ?string $altText = null): bool
    {
        try {
            if ($caption !== null) {
                $this->caption = $caption;
            }
            
            if ($altText !== null) {
                $this->alt_text = $altText;
            }
            
            return $this->save();
            
        } catch (\Exception $e) {
            logger()->error('Failed to update image caption/alt text', [
                'image_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}