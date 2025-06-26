<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog\Image;
use App\Services\Ahhob\Blog\Shared\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

/**
 * 관리자 이미지 관리 컨트롤러
 * 
 * 이 컨트롤러는 관리자 패널에서 이미지를 관리하는 기능을 제공합니다:
 * - 업로드된 이미지 목록 조회 및 필터링
 * - 이미지 메타데이터 수정 (alt 텍스트, 캡션)
 * - 이미지 삭제 및 일괄 관리
 * - 이미지 최적화 및 썸네일 재생성
 * - 이미지 사용 현황 및 통계 조회
 */
class ImageController extends Controller
{
    /**
     * 이미지 최적화 서비스
     */
    protected ImageOptimizationService $optimizationService;

    /**
     * 생성자
     */
    public function __construct(ImageOptimizationService $optimizationService)
    {
        $this->optimizationService = $optimizationService;
    }

    /**
     * 이미지 목록 페이지
     * 
     * 업로드된 모든 이미지를 페이지네이션과 함께 표시하며,
     * 다양한 필터링 옵션을 제공합니다.
     * 
     * @param Request $request 검색 및 필터 파라미터
     * @return View 이미지 목록 뷰
     */
    public function index(Request $request): View
    {
        // 필터 파라미터 추출
        $filters = $request->only([
            'search',           // 파일명 또는 alt 텍스트 검색
            'mime_type',        // MIME 타입 필터
            'size_min',         // 최소 파일 크기
            'size_max',         // 최대 파일 크기
            'date_from',        // 업로드 시작 날짜
            'date_to',          // 업로드 종료 날짜
            'has_thumbnails',   // 썸네일 존재 여부
            'imageable_type',   // 연결된 모델 타입
            'sort',             // 정렬 기준
            'sort_dir'          // 정렬 방향
        ]);

        // 쿼리 빌더 시작
        $query = Image::query();

        // 검색어 적용 (파일명, alt 텍스트, 캡션에서 검색)
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('original_name', 'LIKE', $searchTerm)
                  ->orWhere('file_name', 'LIKE', $searchTerm)
                  ->orWhere('alt_text', 'LIKE', $searchTerm)
                  ->orWhere('caption', 'LIKE', $searchTerm);
            });
        }

        // MIME 타입 필터
        if (!empty($filters['mime_type'])) {
            $query->byMimeType($filters['mime_type']);
        }

        // 파일 크기 범위 필터
        if (!empty($filters['size_min']) || !empty($filters['size_max'])) {
            $minSize = $filters['size_min'] ? (int)$filters['size_min'] * 1024 : 0; // KB를 바이트로 변환
            $maxSize = $filters['size_max'] ? (int)$filters['size_max'] * 1024 : 0;
            $query->bySizeRange($minSize, $maxSize);
        }

        // 날짜 범위 필터
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // 썸네일 존재 여부 필터
        if (isset($filters['has_thumbnails'])) {
            if ($filters['has_thumbnails'] === '1') {
                $query->whereNotNull('thumbnails');
            } else {
                $query->whereNull('thumbnails');
            }
        }

        // 연결된 모델 타입 필터
        if (!empty($filters['imageable_type'])) {
            $query->where('imageable_type', $filters['imageable_type']);
        }

        // 정렬 적용
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['sort_dir'] ?? 'desc';
        
        // 허용된 정렬 필드만 사용
        $allowedSortFields = ['created_at', 'file_size', 'original_name', 'width', 'height'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // 관계 데이터 로드 (필요한 경우)
        $query->with(['imageable']);

        // 페이지네이션 적용
        $perPage = config('ahhob_blog.pagination.admin_per_page', 20);
        $images = $query->paginate($perPage)->withQueryString();

        // 통계 정보 계산
        $stats = $this->calculateImageStats();

        // 필터 옵션 데이터
        $filterOptions = [
            'mime_types' => Image::distinct('mime_type')->pluck('mime_type')->toArray(),
            'imageable_types' => Image::distinct('imageable_type')->whereNotNull('imageable_type')->pluck('imageable_type')->toArray(),
        ];

        return view('ahhob.blog.admin.images.index', compact(
            'images', 
            'filters', 
            'stats', 
            'filterOptions'
        ));
    }

    /**
     * 이미지 상세 정보 페이지
     * 
     * @param Image $image 이미지 모델
     * @return View 이미지 상세 뷰
     */
    public function show(Image $image): View
    {
        // 이미지 최적화 정보 가져오기
        $optimizationInfo = $image->getOptimizationInfo();
        
        // 연결된 모델 정보 로드
        $image->load(['imageable']);
        
        // 이미지 사용 현황 분석
        $usageStats = $this->analyzeImageUsage($image);

        return view('ahhob.blog.admin.images.show', compact(
            'image', 
            'optimizationInfo', 
            'usageStats'
        ));
    }

    /**
     * 이미지 메타데이터 수정
     * 
     * @param Request $request 수정 요청
     * @param Image $image 이미지 모델
     * @return RedirectResponse 리다이렉트 응답
     */
    public function update(Request $request, Image $image): RedirectResponse
    {
        // 유효성 검증
        $validated = $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
        ]);

        // 메타데이터 업데이트
        $success = $image->updateCaptionAndAlt(
            $validated['caption'] ?? null,
            $validated['alt_text'] ?? null
        );

        if ($success) {
            return redirect()
                ->route('admin.images.show', $image)
                ->with('success', '이미지 정보가 성공적으로 업데이트되었습니다.');
        }

        return back()
            ->with('error', '이미지 정보 업데이트에 실패했습니다.')
            ->withInput();
    }

    /**
     * 이미지 삭제
     * 
     * @param Image $image 이미지 모델
     * @return RedirectResponse 리다이렉트 응답
     */
    public function destroy(Image $image): RedirectResponse
    {
        // 이미지 파일과 썸네일 삭제
        $deleted = $image->deletePhysicalFiles();
        
        if ($deleted) {
            // 데이터베이스에서 삭제 (소프트 삭제)
            $image->delete();
            
            return redirect()
                ->route('admin.images.index')
                ->with('success', '이미지가 성공적으로 삭제되었습니다.');
        }

        return back()->with('error', '이미지 삭제에 실패했습니다.');
    }

    /**
     * 이미지 일괄 작업 처리 (AJAX)
     * 
     * @param Request $request 일괄 작업 요청
     * @return JsonResponse JSON 응답
     */
    public function bulkAction(Request $request): JsonResponse
    {
        // 유효성 검증
        $validated = $request->validate([
            'image_ids' => 'required|array',
            'image_ids.*' => 'exists:images,id',
            'action' => 'required|in:delete,optimize,regenerate_thumbnails',
        ]);

        $imageIds = $validated['image_ids'];
        $action = $validated['action'];
        $results = ['success' => 0, 'failed' => 0, 'messages' => []];

        try {
            foreach ($imageIds as $imageId) {
                $image = Image::find($imageId);
                if (!$image) {
                    $results['failed']++;
                    continue;
                }

                $success = match($action) {
                    'delete' => $this->deleteImage($image),
                    'optimize' => $this->optimizeImage($image),
                    'regenerate_thumbnails' => $this->regenerateThumbnails($image),
                    default => false
                };

                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            }

            // 결과 메시지 생성
            $actionNames = [
                'delete' => '삭제',
                'optimize' => '최적화',
                'regenerate_thumbnails' => '썸네일 재생성'
            ];
            
            $actionName = $actionNames[$action];
            $message = "{$results['success']}개 이미지가 {$actionName}되었습니다.";
            
            if ($results['failed'] > 0) {
                $message .= " ({$results['failed']}개 실패)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '일괄 작업 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 썸네일 재생성 (AJAX)
     * 
     * @param Image $image 이미지 모델
     * @return JsonResponse JSON 응답
     */
    public function regenerateThumbnails(Image $image): JsonResponse
    {
        try {
            $success = $this->regenerateThumbnails($image);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => '썸네일이 성공적으로 재생성되었습니다.',
                    'thumbnails' => $image->fresh()->thumbnails
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => '썸네일 재생성에 실패했습니다.'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '썸네일 재생성 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 이미지 통계 계산
     * 
     * @return array 통계 정보
     */
    protected function calculateImageStats(): array
    {
        $totalImages = Image::count();
        $totalSize = Image::sum('file_size');
        $imagesWithThumbnails = Image::whereNotNull('thumbnails')->count();
        
        // MIME 타입별 통계
        $mimeTypeStats = Image::selectRaw('mime_type, COUNT(*) as count, SUM(file_size) as total_size')
            ->groupBy('mime_type')
            ->get()
            ->keyBy('mime_type');

        return [
            'total_images' => $totalImages,
            'total_size' => $totalSize,
            'images_with_thumbnails' => $imagesWithThumbnails,
            'thumbnail_coverage' => $totalImages > 0 ? round(($imagesWithThumbnails / $totalImages) * 100, 1) : 0,
            'average_size' => $totalImages > 0 ? round($totalSize / $totalImages) : 0,
            'mime_types' => $mimeTypeStats
        ];
    }

    /**
     * 이미지 사용 현황 분석
     * 
     * @param Image $image 이미지 모델
     * @return array 사용 현황 정보
     */
    protected function analyzeImageUsage(Image $image): array
    {
        $usage = [
            'is_used' => false,
            'usage_type' => null,
            'related_content' => null,
            'usage_count' => 0
        ];

        // 연결된 모델이 있는 경우
        if ($image->imageable) {
            $usage['is_used'] = true;
            $usage['usage_type'] = class_basename($image->imageable_type);
            $usage['related_content'] = $image->imageable;
            $usage['usage_count'] = 1;
        }

        // 콘텐츠 내에서 사용된 경우 (URL 기반 검색)
        // 이는 성능상 이유로 필요시에만 실행
        
        return $usage;
    }

    /**
     * 개별 이미지 삭제 헬퍼
     */
    protected function deleteImage(Image $image): bool
    {
        return $image->deletePhysicalFiles() && $image->delete();
    }

    /**
     * 개별 이미지 최적화 헬퍼
     */
    protected function optimizeImage(Image $image): bool
    {
        try {
            // 이미지가 이미 최적화되어 있는지 확인
            if ($image->thumbnails && count($image->thumbnails) > 0) {
                return true; // 이미 최적화됨
            }

            // 썸네일 재생성
            return $this->regenerateThumbnailsForImage($image);

        } catch (\Exception $e) {
            logger()->error('Image optimization failed', [
                'image_id' => $image->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 개별 이미지 썸네일 재생성 헬퍼
     */
    protected function regenerateThumbnailsForImage(Image $image): bool
    {
        // OptimizeImages 명령어의 로직을 재사용
        // 실제 구현에서는 ImageOptimizationService의 메서드를 호출
        return true; // 임시 반환값
    }
}