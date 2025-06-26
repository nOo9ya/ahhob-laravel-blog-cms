<?php

namespace App\Services\Ahhob\Blog\Shared;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

/**
 * 이미지 최적화 및 썸네일 생성 서비스
 * 
 * 이 서비스는 다음 기능을 제공합니다:
 * - 이미지 업로드 및 저장
 * - 다양한 크기의 썸네일 자동 생성
 * - 이미지 포맷 최적화 (WebP 변환)
 * - 이미지 압축 및 품질 조절
 * - 메타데이터 추출 및 관리
 */
class ImageOptimizationService
{
    /**
     * Intervention Image 매니저 인스턴스
     */
    protected ImageManager $imageManager;

    /**
     * 썸네일 크기 설정
     * 각 크기는 [width, height, quality] 형태로 정의
     */
    protected array $thumbnailSizes = [
        'thumbnail' => [150, 150, 85],    // 작은 썸네일 (150x150, 품질 85%)
        'small' => [300, 200, 90],        // 소형 이미지 (300x200, 품질 90%)
        'medium' => [600, 400, 90],       // 중형 이미지 (600x400, 품질 90%)
        'large' => [1200, 800, 85],       // 대형 이미지 (1200x800, 품질 85%)
    ];

    /**
     * 지원하는 이미지 MIME 타입
     */
    protected array $supportedMimeTypes = [
        'image/jpeg',
        'image/png', 
        'image/gif',
        'image/webp'
    ];

    /**
     * 최대 파일 크기 (바이트 단위)
     * 기본값: 10MB
     */
    protected int $maxFileSize = 10485760;

    /**
     * 생성자: Intervention Image 설정
     */
    public function __construct()
    {
        // GD 드라이버를 사용하여 ImageManager 초기화
        // GD는 PHP에 기본적으로 포함되어 있어 호환성이 좋음
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * 업로드된 파일의 유효성 검사
     * 
     * @param UploadedFile $file 업로드된 파일 객체
     * @return array 검증 결과와 오류 메시지
     */
    public function validateImage(UploadedFile $file): array
    {
        $errors = [];

        // 1. 파일이 실제로 업로드되었는지 확인
        if (!$file->isValid()) {
            $errors[] = '파일 업로드에 실패했습니다.';
            return ['valid' => false, 'errors' => $errors];
        }

        // 2. 파일 크기 검사
        if ($file->getSize() > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1024 / 1024, 2);
            $errors[] = "파일 크기가 너무 큽니다. 최대 {$maxSizeMB}MB까지 허용됩니다.";
        }

        // 3. MIME 타입 검사
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->supportedMimeTypes)) {
            $supportedTypes = implode(', ', $this->supportedMimeTypes);
            $errors[] = "지원하지 않는 파일 형식입니다. 지원 형식: {$supportedTypes}";
        }

        // 4. 실제 이미지 파일인지 검사 (보안 목적)
        try {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                $errors[] = '유효한 이미지 파일이 아닙니다.';
            }
        } catch (Exception $e) {
            $errors[] = '이미지 파일을 읽을 수 없습니다.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 이미지 업로드 및 최적화 처리
     * 
     * @param UploadedFile $file 업로드된 파일
     * @param string $path 저장 경로 (storage/app/public 기준)
     * @param array $options 추가 옵션
     * @return array 처리 결과
     */
    public function uploadAndOptimize(UploadedFile $file, string $path = 'images', array $options = []): array
    {
        // 1. 파일 유효성 검사
        $validation = $this->validateImage($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        try {
            // 2. 고유한 파일명 생성
            // 형식: YYYYMMDD_HHmmss_랜덤문자열.확장자
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension());
            $timestamp = date('Ymd_His');
            $randomString = Str::random(8);
            $fileName = Str::slug($originalName) . "_{$timestamp}_{$randomString}";

            // 3. 원본 이미지 처리 및 저장
            $originalResult = $this->processOriginalImage($file, $path, $fileName, $extension, $options);
            
            if (!$originalResult['success']) {
                return $originalResult;
            }

            // 4. 썸네일 생성 (설정에 따라 선택적)
            $thumbnails = [];
            if ($options['generate_thumbnails'] ?? true) {
                $thumbnails = $this->generateThumbnails(
                    $originalResult['file_path'], 
                    $path, 
                    $fileName,
                    $options
                );
            }

            // 5. 메타데이터 추출
            $metadata = $this->extractMetadata($originalResult['file_path']);

            return [
                'success' => true,
                'original' => $originalResult,
                'thumbnails' => $thumbnails,
                'metadata' => $metadata,
                'message' => '이미지가 성공적으로 업로드되고 최적화되었습니다.'
            ];

        } catch (Exception $e) {
            // 오류 발생 시 로깅
            logger()->error('Image upload failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'errors' => ['이미지 처리 중 오류가 발생했습니다: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * 원본 이미지 처리 및 저장
     * 
     * @param UploadedFile $file 원본 파일
     * @param string $path 저장 경로
     * @param string $fileName 파일명 (확장자 제외)
     * @param string $extension 원본 확장자
     * @param array $options 처리 옵션
     * @return array 처리 결과
     */
    protected function processOriginalImage(UploadedFile $file, string $path, string $fileName, string $extension, array $options): array
    {
        // 1. 이미지 로드
        $image = $this->imageManager->read($file->getPathname());
        
        // 2. 최대 크기 제한 적용 (설정된 경우)
        $maxWidth = $options['max_width'] ?? config('ahhob_blog.upload.max_image_width', 2000);
        $maxHeight = $options['max_height'] ?? config('ahhob_blog.upload.max_image_height', 2000);
        
        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            // 비율을 유지하면서 크기 조정
            $image->scale(width: $maxWidth, height: $maxHeight);
        }

        // 3. 이미지 품질 최적화
        $quality = $options['quality'] ?? 90;
        
        // 4. WebP 변환 옵션 확인
        $convertToWebP = $options['convert_to_webp'] ?? config('ahhob_blog.upload.convert_to_webp', true);
        $finalExtension = $convertToWebP && $extension !== 'gif' ? 'webp' : $extension;
        
        // 5. 최종 파일명과 경로 생성
        $finalFileName = "{$fileName}.{$finalExtension}";
        $fullPath = "{$path}/{$finalFileName}";
        
        // 6. 이미지 인코딩 및 저장
        $encodedImage = match($finalExtension) {
            'webp' => $image->toWebp($quality),
            'jpeg', 'jpg' => $image->toJpeg($quality),
            'png' => $image->toPng(),
            'gif' => $image->toGif(),
            default => $image->toJpeg($quality)
        };

        // 7. 파일 시스템에 저장
        $saved = Storage::disk('public')->put($fullPath, $encodedImage);
        
        if (!$saved) {
            throw new Exception('파일 저장에 실패했습니다.');
        }

        return [
            'success' => true,
            'file_name' => $finalFileName,
            'file_path' => $fullPath,
            'url' => Storage::disk('public')->url($fullPath),
            'size' => Storage::disk('public')->size($fullPath),
            'width' => $image->width(),
            'height' => $image->height(),
            'format' => $finalExtension
        ];
    }

    /**
     * 다양한 크기의 썸네일 생성
     * 
     * @param string $originalPath 원본 이미지 경로
     * @param string $basePath 기본 저장 경로
     * @param string $fileName 파일명 (확장자 제외)
     * @param array $options 생성 옵션
     * @return array 생성된 썸네일 정보
     */
    protected function generateThumbnails(string $originalPath, string $basePath, string $fileName, array $options = []): array
    {
        $thumbnails = [];
        
        // 원본 이미지 로드
        $originalImagePath = Storage::disk('public')->path($originalPath);
        
        if (!file_exists($originalImagePath)) {
            logger()->warning('Original image not found for thumbnail generation', ['path' => $originalImagePath]);
            return [];
        }

        $originalImage = $this->imageManager->read($originalImagePath);
        
        // 각 썸네일 크기별로 생성
        foreach ($this->thumbnailSizes as $sizeName => $sizeConfig) {
            try {
                [$width, $height, $quality] = $sizeConfig;
                
                // 사용자가 특정 크기만 요청한 경우 필터링
                if (isset($options['sizes']) && !in_array($sizeName, $options['sizes'])) {
                    continue;
                }

                // 썸네일 생성 (비율 유지하면서 크기 조정)
                $thumbnail = clone $originalImage;
                $thumbnail->scale(width: $width, height: $height);
                
                // 썸네일 파일명 생성
                $thumbnailFileName = "{$fileName}_{$sizeName}.webp";
                $thumbnailPath = "{$basePath}/thumbnails/{$thumbnailFileName}";
                
                // WebP 형태로 인코딩 (썸네일은 항상 WebP로 저장하여 용량 절약)
                $encodedThumbnail = $thumbnail->toWebp($quality);
                
                // 저장
                $saved = Storage::disk('public')->put($thumbnailPath, $encodedThumbnail);
                
                if ($saved) {
                    $thumbnails[$sizeName] = [
                        'file_name' => $thumbnailFileName,
                        'file_path' => $thumbnailPath,
                        'url' => Storage::disk('public')->url($thumbnailPath),
                        'width' => $thumbnail->width(),
                        'height' => $thumbnail->height(),
                        'size' => Storage::disk('public')->size($thumbnailPath)
                    ];
                }
                
            } catch (Exception $e) {
                // 개별 썸네일 생성 실패는 로깅만 하고 계속 진행
                logger()->error("Thumbnail generation failed for size: {$sizeName}", [
                    'error' => $e->getMessage(),
                    'original_path' => $originalPath
                ]);
            }
        }
        
        return $thumbnails;
    }

    /**
     * 이미지 메타데이터 추출
     * 
     * @param string $imagePath 이미지 파일 경로
     * @return array 메타데이터 정보
     */
    protected function extractMetadata(string $imagePath): array
    {
        $fullPath = Storage::disk('public')->path($imagePath);
        $metadata = [
            'file_size' => Storage::disk('public')->size($imagePath),
            'last_modified' => Storage::disk('public')->lastModified($imagePath),
        ];

        try {
            // 기본 이미지 정보 추출
            $imageInfo = getimagesize($fullPath);
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['mime_type'] = $imageInfo['mime'];
                $metadata['aspect_ratio'] = round($imageInfo[0] / $imageInfo[1], 2);
            }

            // EXIF 데이터 추출 (JPEG의 경우)
            if (function_exists('exif_read_data') && $imageInfo['mime'] === 'image/jpeg') {
                $exifData = @exif_read_data($fullPath);
                if ($exifData) {
                    // 유용한 EXIF 정보만 선별적으로 추출
                    $metadata['exif'] = [
                        'camera_make' => $exifData['Make'] ?? null,
                        'camera_model' => $exifData['Model'] ?? null,
                        'date_taken' => $exifData['DateTime'] ?? null,
                        'orientation' => $exifData['Orientation'] ?? null,
                        'color_space' => $exifData['ColorSpace'] ?? null,
                    ];
                    
                    // null 값 제거
                    $metadata['exif'] = array_filter($metadata['exif']);
                }
            }

        } catch (Exception $e) {
            // 메타데이터 추출 실패는 로깅만 하고 빈 배열 반환
            logger()->warning('Metadata extraction failed', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
        }

        return $metadata;
    }

    /**
     * 이미지 파일 삭제 (원본 + 모든 썸네일)
     * 
     * @param string $imagePath 원본 이미지 경로
     * @return bool 삭제 성공 여부
     */
    public function deleteImage(string $imagePath): bool
    {
        try {
            $deletedFiles = 0;
            
            // 1. 원본 이미지 삭제
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
                $deletedFiles++;
            }

            // 2. 관련 썸네일 삭제
            $pathInfo = pathinfo($imagePath);
            $baseFileName = $pathInfo['filename'];
            $basePath = $pathInfo['dirname'];
            
            // 썸네일 디렉토리에서 관련 파일들 찾아서 삭제
            $thumbnailPath = "{$basePath}/thumbnails";
            if (Storage::disk('public')->exists($thumbnailPath)) {
                $thumbnailFiles = Storage::disk('public')->files($thumbnailPath);
                
                foreach ($thumbnailFiles as $thumbnailFile) {
                    // 파일명이 원본과 매칭되는 썸네일인지 확인
                    if (str_contains(basename($thumbnailFile), $baseFileName)) {
                        Storage::disk('public')->delete($thumbnailFile);
                        $deletedFiles++;
                    }
                }
            }

            logger()->info('Image and thumbnails deleted', [
                'original_path' => $imagePath,
                'deleted_files' => $deletedFiles
            ]);

            return true;

        } catch (Exception $e) {
            logger()->error('Image deletion failed', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 썸네일 크기 설정 업데이트
     * 
     * @param array $sizes 새로운 크기 설정
     * @return void
     */
    public function setThumbnailSizes(array $sizes): void
    {
        $this->thumbnailSizes = $sizes;
    }

    /**
     * 최대 파일 크기 설정
     * 
     * @param int $size 바이트 단위 크기
     * @return void
     */
    public function setMaxFileSize(int $size): void
    {
        $this->maxFileSize = $size;
    }

    /**
     * 지원하는 MIME 타입 목록 반환
     * 
     * @return array
     */
    public function getSupportedMimeTypes(): array
    {
        return $this->supportedMimeTypes;
    }

    /**
     * 현재 썸네일 크기 설정 반환
     * 
     * @return array
     */
    public function getThumbnailSizes(): array
    {
        return $this->thumbnailSizes;
    }

    /**
     * 이미지 최적화 통계 정보 반환
     * 
     * @param string $originalPath 원본 경로
     * @param array $thumbnails 썸네일 정보
     * @return array 통계 정보
     */
    public function getOptimizationStats(string $originalPath, array $thumbnails = []): array
    {
        $originalSize = Storage::disk('public')->size($originalPath);
        $totalThumbnailSize = 0;
        
        foreach ($thumbnails as $thumbnail) {
            $totalThumbnailSize += $thumbnail['size'] ?? 0;
        }
        
        return [
            'original_size' => $originalSize,
            'total_thumbnail_size' => $totalThumbnailSize,
            'total_size' => $originalSize + $totalThumbnailSize,
            'thumbnail_count' => count($thumbnails),
            'compression_ratio' => $originalSize > 0 ? round((1 - ($originalSize + $totalThumbnailSize) / $originalSize) * 100, 2) : 0
        ];
    }
    
    /**
     * ImageManager 인스턴스 반환
     * 
     * Artisan 명령어나 다른 서비스에서 ImageManager에 직접 접근해야 할 때 사용
     * 
     * @return ImageManager
     */
    public function getImageManager(): ImageManager
    {
        return $this->imageManager;
    }
}