<?php

namespace App\Console\Commands;

use App\Models\Blog\Image;
use App\Services\Ahhob\Blog\Shared\ImageOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * 이미지 최적화 Artisan 명령어
 * 
 * 이 명령어는 다음 기능을 제공합니다:
 * - 기존 이미지들의 일괄 최적화 및 썸네일 재생성
 * - 고아 이미지 파일 정리 (DB에 없는 파일들)
 * - 손상된 이미지 감지 및 복구
 * - 이미지 최적화 통계 및 보고서 생성
 * 
 * 사용법:
 * php artisan images:optimize                    # 모든 이미지 최적화
 * php artisan images:optimize --regenerate       # 썸네일 재생성
 * php artisan images:optimize --cleanup          # 고아 파일 정리
 * php artisan images:optimize --stats            # 통계만 표시
 */
class OptimizeImages extends Command
{
    /**
     * 명령어 시그니처
     */
    protected $signature = 'images:optimize 
                            {--regenerate : 기존 썸네일을 모두 재생성합니다}
                            {--cleanup : 고아 이미지 파일을 정리합니다}
                            {--stats : 최적화 통계만 표시합니다}
                            {--batch=50 : 한 번에 처리할 이미지 수}
                            {--force : 확인 없이 강제 실행}';

    /**
     * 명령어 설명
     */
    protected $description = '이미지 최적화, 썸네일 재생성, 고아 파일 정리를 수행합니다';

    /**
     * 이미지 최적화 서비스
     */
    protected ImageOptimizationService $optimizationService;

    /**
     * 처리 통계
     */
    protected array $stats = [
        'processed' => 0,
        'optimized' => 0,
        'failed' => 0,
        'cleaned' => 0,
        'space_saved' => 0,
        'start_time' => null,
        'end_time' => null,
    ];

    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct();
        $this->optimizationService = app(ImageOptimizationService::class);
    }

    /**
     * 명령어 실행
     */
    public function handle(): int
    {
        $this->stats['start_time'] = now();
        
        // 헤더 출력
        $this->info('🖼️  이미지 최적화 도구 시작');
        $this->info('=====================================');
        
        try {
            // 통계만 표시하는 경우
            if ($this->option('stats')) {
                return $this->showStats();
            }
            
            // 고아 파일 정리만 하는 경우
            if ($this->option('cleanup')) {
                return $this->cleanupOrphanedFiles();
            }
            
            // 이미지 최적화 실행
            $this->optimizeImages();
            
            // 고아 파일 정리도 함께 수행 (기본 동작)
            if (!$this->option('regenerate')) {
                $this->line('');
                $this->cleanupOrphanedFiles();
            }
            
            // 최종 결과 출력
            $this->showFinalResults();
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('⚠️  오류가 발생했습니다: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }

    /**
     * 이미지 최적화 실행
     */
    protected function optimizeImages(): void
    {
        $this->info('📸 이미지 최적화 시작...');
        
        $batchSize = (int) $this->option('batch');
        $regenerate = $this->option('regenerate');
        
        // 전체 이미지 수 확인
        $totalImages = Image::count();
        
        if ($totalImages === 0) {
            $this->warn('처리할 이미지가 없습니다.');
            return;
        }
        
        $this->line("총 {$totalImages}개의 이미지를 처리합니다.");
        
        // 확인 요청 (강제 실행이 아닌 경우)
        if (!$this->option('force') && !$this->confirm('계속하시겠습니까?')) {
            $this->warn('작업이 취소되었습니다.');
            return;
        }
        
        // 진행 상황 표시줄 초기화
        $progressBar = $this->output->createProgressBar($totalImages);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('이미지 로딩 중...');
        $progressBar->start();
        
        // 배치 단위로 이미지 처리
        Image::chunk($batchSize, function ($images) use ($progressBar, $regenerate) {
            foreach ($images as $image) {
                $this->processImage($image, $regenerate, $progressBar);
                $progressBar->advance();
            }
        });
        
        $progressBar->finish();
        $this->line('');
    }

    /**
     * 개별 이미지 처리
     */
    protected function processImage(Image $image, bool $regenerate, $progressBar): void
    {
        try {
            $progressBar->setMessage("처리 중: {$image->original_name}");
            
            // 원본 파일 존재 확인
            if (!Storage::disk($image->disk ?? 'public')->exists($image->file_path)) {
                $this->stats['failed']++;
                return;
            }
            
            $originalSize = $image->file_size;
            
            // 썸네일 재생성이 요청된 경우 또는 썸네일이 없는 경우
            if ($regenerate || !$image->thumbnails) {
                $this->regenerateThumbnails($image);
            }
            
            // 최적화된 크기 계산
            $optimizedSize = $this->calculateOptimizedSize($image);
            $spaceSaved = $originalSize - $optimizedSize;
            
            if ($spaceSaved > 0) {
                $this->stats['space_saved'] += $spaceSaved;
                $this->stats['optimized']++;
            }
            
            $this->stats['processed']++;
            
        } catch (\Exception $e) {
            $this->stats['failed']++;
            logger()->error('Image optimization failed', [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 썸네일 재생성
     */
    protected function regenerateThumbnails(Image $image): void
    {
        try {
            // 기존 썸네일 파일들 삭제
            if ($image->thumbnails) {
                foreach ($image->thumbnails as $size => $thumbnailInfo) {
                    if (isset($thumbnailInfo['file_path'])) {
                        Storage::disk($image->disk ?? 'public')->delete($thumbnailInfo['file_path']);
                    }
                }
            }
            
            // 새 썸네일 생성
            $pathInfo = pathinfo($image->file_path);
            $basePath = $pathInfo['dirname'];
            $fileName = $pathInfo['filename'];
            
            // ImageOptimizationService의 thumbnailSizes 사용
            $thumbnailSizes = $this->optimizationService->getThumbnailSizes();
            $thumbnails = [];
            
            foreach ($thumbnailSizes as $sizeName => $sizeConfig) {
                $thumbnail = $this->generateSingleThumbnail($image, $sizeName, $sizeConfig, $basePath, $fileName);
                if ($thumbnail) {
                    $thumbnails[$sizeName] = $thumbnail;
                }
            }
            
            // 데이터베이스 업데이트
            $image->update(['thumbnails' => $thumbnails]);
            
        } catch (\Exception $e) {
            logger()->error('Thumbnail regeneration failed', [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 개별 썸네일 생성
     */
    protected function generateSingleThumbnail(Image $image, string $sizeName, array $sizeConfig, string $basePath, string $fileName): ?array
    {
        try {
            [$width, $height, $quality] = $sizeConfig;
            
            // 원본 이미지 로드
            $originalPath = Storage::disk($image->disk ?? 'public')->path($image->file_path);
            $imageManager = $this->optimizationService->getImageManager();
            $thumbnail = $imageManager->read($originalPath);
            
            // 썸네일 크기 조정
            $thumbnail->scale(width: $width, height: $height);
            
            // 썸네일 저장
            $thumbnailFileName = "{$fileName}_{$sizeName}.webp";
            $thumbnailPath = "{$basePath}/thumbnails/{$thumbnailFileName}";
            
            $encodedThumbnail = $thumbnail->toWebp($quality);
            Storage::disk($image->disk ?? 'public')->put($thumbnailPath, $encodedThumbnail);
            
            return [
                'file_name' => $thumbnailFileName,
                'file_path' => $thumbnailPath,
                'url' => Storage::disk($image->disk ?? 'public')->url($thumbnailPath),
                'width' => $thumbnail->width(),
                'height' => $thumbnail->height(),
                'size' => Storage::disk($image->disk ?? 'public')->size($thumbnailPath)
            ];
            
        } catch (\Exception $e) {
            logger()->error("Single thumbnail generation failed for size: {$sizeName}", [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * 최적화된 크기 계산
     */
    protected function calculateOptimizedSize(Image $image): int
    {
        $totalSize = $image->file_size;
        
        if ($image->thumbnails) {
            foreach ($image->thumbnails as $thumbnail) {
                $totalSize += $thumbnail['size'] ?? 0;
            }
        }
        
        return $totalSize;
    }

    /**
     * 고아 파일 정리
     */
    protected function cleanupOrphanedFiles(): int
    {
        $this->info('🧹 고아 이미지 파일 정리 시작...');
        
        try {
            $uploadPaths = [
                config('ahhob_blog.upload.post_images.path', 'uploads/posts'),
                config('ahhob_blog.upload.profile_images.path', 'uploads/profiles'),
                'uploads' // 기본 업로드 경로
            ];
            
            $totalCleaned = 0;
            
            foreach ($uploadPaths as $path) {
                $cleaned = $this->cleanupPath($path);
                $totalCleaned += $cleaned;
            }
            
            $this->stats['cleaned'] = $totalCleaned;
            
            if ($totalCleaned > 0) {
                $this->info("✅ {$totalCleaned}개의 고아 파일이 정리되었습니다.");
            } else {
                $this->info('ℹ️  정리할 고아 파일이 없습니다.');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('고아 파일 정리 중 오류가 발생했습니다: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 특정 경로의 고아 파일 정리
     */
    protected function cleanupPath(string $path): int
    {
        $disk = Storage::disk('public');
        $cleaned = 0;
        
        if (!$disk->exists($path)) {
            return 0;
        }
        
        $files = $disk->allFiles($path);
        
        foreach ($files as $file) {
            // 데이터베이스에서 해당 파일 검색
            $imageExists = Image::where('file_path', $file)
                ->orWhere('thumbnails', 'LIKE', "%{$file}%")
                ->exists();
            
            // 데이터베이스에 없는 파일이면 삭제
            if (!$imageExists) {
                $disk->delete($file);
                $cleaned++;
                
                logger()->info('Orphaned file cleaned', ['file' => $file]);
            }
        }
        
        return $cleaned;
    }

    /**
     * 최적화 통계 표시
     */
    protected function showStats(): int
    {
        $this->info('📊 이미지 최적화 통계');
        $this->info('=========================');
        
        $totalImages = Image::count();
        $totalSize = Image::sum('file_size');
        $imagesWithThumbnails = Image::whereNotNull('thumbnails')->count();
        
        $this->table(
            ['항목', '값'],
            [
                ['전체 이미지 수', number_format($totalImages) . '개'],
                ['썸네일이 있는 이미지', number_format($imagesWithThumbnails) . '개'],
                ['전체 저장 용량', $this->formatBytes($totalSize)],
                ['평균 이미지 크기', $totalImages > 0 ? $this->formatBytes($totalSize / $totalImages) : '0'],
                ['썸네일 적용률', $totalImages > 0 ? round(($imagesWithThumbnails / $totalImages) * 100, 1) . '%' : '0%'],
            ]
        );
        
        return Command::SUCCESS;
    }

    /**
     * 최종 결과 출력
     */
    protected function showFinalResults(): void
    {
        $this->stats['end_time'] = now();
        $duration = $this->stats['end_time']->diffInSeconds($this->stats['start_time']);
        
        $this->line('');
        $this->info('🎉 이미지 최적화 완료!');
        $this->info('========================');
        
        $this->table(
            ['항목', '결과'],
            [
                ['처리된 이미지', number_format($this->stats['processed']) . '개'],
                ['최적화된 이미지', number_format($this->stats['optimized']) . '개'],
                ['실패한 이미지', number_format($this->stats['failed']) . '개'],
                ['정리된 고아 파일', number_format($this->stats['cleaned']) . '개'],
                ['절약된 용량', $this->formatBytes($this->stats['space_saved'])],
                ['소요 시간', $this->formatDuration($duration)],
            ]
        );
        
        if ($this->stats['failed'] > 0) {
            $this->warn("⚠️  {$this->stats['failed']}개의 이미지 처리가 실패했습니다. 로그를 확인해주세요.");
        }
    }

    /**
     * 바이트를 읽기 쉬운 형태로 변환
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 시간을 읽기 쉬운 형태로 변환
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . '초';
        }
        
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        
        return $minutes . '분 ' . $seconds . '초';
    }
}