<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Admin\Post\PostRequest;
use App\Services\Ahhob\Blog\Admin\Post\PostService;
use App\Models\Blog\Post;
use App\Models\Blog\Category;
use App\Models\Blog\Tag;
use App\Traits\Blog\ControllerResponseTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PostController extends Controller
{
    use ControllerResponseTrait;
    
    public function __construct(
        private PostService $postService
    ) {}

    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'author_id', 'category_id', 'search', 'date_from', 'date_to', 'sort', 'sort_dir']);
        $posts = $this->postService->getPosts($filters);

        // 필터링 옵션들
        $categories = Category::active()->orderBy('name')->get();
        $authors = \App\Models\User::byRole('writer')->orWhere('role', 'admin')->get();

        return view('admin.post.index', compact('posts', 'categories', 'authors', 'filters'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $categories = Category::active()
            ->with('children')
            ->roots()
            ->orderBy('sort_order')
            ->get();

        $tags = Tag::orderBy('name')->get();

        return view('admin.post.create', compact('categories', 'tags'));
    }

    /**
     * @param PostRequest $request
     * @return RedirectResponse
     */
    public function store(PostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $featuredImage = $request->file('featured_image');
        $ogImage = $request->file('og_image');

        $post = $this->postService->createPost($data, $featuredImage, $ogImage);

        return $this->resourceCreated('게시물', 'admin.posts.edit', $post);
    }

    /**
     * @param Post $post
     * @return View
     */
    public function show(Post $post): View
    {
        $post->load(['user', 'category', 'tags', 'comments.user']);

        // 조회수 통계
        $viewStats = [
            'today' => $post->views()->today()->count(),
            'week' => $post->views()->thisWeek()->count(),
            'month' => $post->views()->thisMonth()->count(),
            'total' => $post->views_count,
        ];

        return view('admin.post.show', compact('post', 'viewStats'));
    }

    /**
     * @param Post $post
     * @return View
     */
    public function edit(Post $post): View
    {
        // 권한 확인
        if (!$post->canBeEditedBy(Auth::user())) {
            abort(403, '이 게시물을 수정할 권한이 없습니다.');
        }

        $post->load(['category', 'tags']);

        $categories = Category::active()
            ->with('children')
            ->roots()
            ->orderBy('sort_order')
            ->get();

        $allTags = Tag::orderBy('name')->get();

        return view('admin.post.edit', compact('post', 'categories', 'allTags'));
    }

    /**
     * @param PostRequest $request
     * @param Post $post
     * @return RedirectResponse
     */
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        $featuredImage = $request->file('featured_image');
        $ogImage = $request->file('og_image');

        $post = $this->postService->updatePost($post, $data, $featuredImage, $ogImage);

        return $this->resourceUpdated('게시물', 'admin.posts.edit', $post);
    }

    /**
     * @param Post $post
     * @return RedirectResponse
     */
    public function destroy(Post $post): RedirectResponse
    {
        // 권한 확인
        if (!$post->canBeEditedBy(Auth::user())) {
            abort(403, '이 게시물을 삭제할 권한이 없습니다.');
        }

        $title = $post->title;

        if ($this->postService->deletePost($post)) {
            return $this->successRedirect('admin.posts.index', "게시물 '{$title}'이(가) 성공적으로 삭제되었습니다.");
        }

        return $this->errorRedirect('게시물 삭제 중 오류가 발생했습니다.');
    }

    /**
     * @param Post $post
     * @return RedirectResponse
     */
    public function restore(Post $post): RedirectResponse
    {
        $post->restore();

        return back()->with('success', '게시물이 성공적으로 복원되었습니다.');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'post_ids' => 'required|array',
            'post_ids.*' => 'exists:posts,id',
            'action' => 'required|in:publish,draft,archive,delete,feature,unfeature',
        ]);

        $result = $this->postService->bulkAction(
            $request->post_ids,
            $request->action,
            Auth::user()
        );

        $actionMessages = [
            'publish' => '발행',
            'draft' => '임시저장',
            'archive' => '보관',
            'delete' => '삭제',
            'feature' => '추천 설정',
            'unfeature' => '추천 해제',
        ];
        
        $actionName = $actionMessages[$request->action] ?? $request->action;
        $message = $this->getBulkActionMessage($actionName, $result);

        return $this->successResponse($message, $result);
    }

    /**
     * 마크다운 에디터에서 이미지 업로드 처리 (최적화 포함)
     * 
     * 이 메서드는 Toast UI Editor에서 드래그&드롭으로 업로드된 이미지를 처리합니다.
     * 새로운 ImageOptimizationService를 사용하여 다음 기능을 제공합니다:
     * - 이미지 유효성 검증 및 보안 검사
     * - 자동 크기 조정 및 압축 최적화
     * - WebP 형식 변환 (용량 절약)
     * - 다양한 크기의 썸네일 자동 생성
     * - 메타데이터 추출 및 저장
     * - 데이터베이스에 이미지 정보 저장
     * 
     * @param Request $request 업로드 요청 (image 파일 포함)
     * @return JsonResponse 업로드 결과 (성공 시 URL, 실패 시 오류 메시지)
     */
    public function uploadImage(Request $request): JsonResponse
    {
        // 기본 파일 유효성 검증
        $this->validateImageUpload($request);

        try {
            $file = $request->file('image');
            
            // 이미지 최적화 처리
            $optimizationResult = $this->processImageOptimization($file);
            
            if (!$optimizationResult['success']) {
                return $this->errorResponse(
                    '이미지 업로드에 실패했습니다.',
                    $optimizationResult['errors'] ?? ['알 수 없는 오류가 발생했습니다.'],
                    422
                );
            }
            
            // 데이터베이스에 이미지 정보 저장
            $savedImage = $this->saveImageToDatabase($file, $optimizationResult);
            
            // 성공 응답 생성
            return $this->successResponse($optimizationResult, $savedImage);
            
        } catch (\Exception $e) {
            return $this->handleImageUploadException($e, $file ?? null);
        }
    }

    /**
     * 이미지 업로드 유효성 검증
     *
     * @param Request $request 요청 객체
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateImageUpload(Request $request): void
    {
        $maxSize = config('ahhob_blog.upload.post_images.max_size', 5120);
        $allowedTypes = config('ahhob_blog.upload.post_images.allowed_types', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        
        $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                "max:{$maxSize}",
                'mimes:' . implode(',', $allowedTypes)
            ]
        ], [
            'image.required' => '이미지 파일을 선택해주세요.',
            'image.image' => '유효한 이미지 파일만 업로드 가능합니다.',
            'image.max' => "파일 크기는 {$maxSize}KB를 초과할 수 없습니다.",
            'image.mimes' => '지원하는 이미지 형식: ' . implode(', ', $allowedTypes)
        ]);
    }

    /**
     * 이미지 최적화 처리
     *
     * @param \Illuminate\Http\UploadedFile $file 업로드된 파일
     * @return array 최적화 결과
     */
    protected function processImageOptimization(\Illuminate\Http\UploadedFile $file): array
    {
        $imageOptimizationService = app(\App\Services\Ahhob\Blog\Shared\ImageOptimizationService::class);
        
        $options = $this->getImageOptimizationOptions();
        $storagePath = config('ahhob_blog.upload.post_images.path', 'uploads/posts');
        
        return $imageOptimizationService->uploadAndOptimize($file, $storagePath, $options);
    }

    /**
     * 이미지 최적화 옵션 구성
     *
     * @return array 최적화 옵션
     */
    protected function getImageOptimizationOptions(): array
    {
        return [
            'generate_thumbnails' => config('ahhob_blog.upload.optimization.generate_thumbnails', true),
            'convert_to_webp' => config('ahhob_blog.upload.optimization.convert_to_webp', true),
            'quality' => config('ahhob_blog.upload.optimization.default_quality', 90),
            'max_width' => config('ahhob_blog.upload.optimization.max_image_width', 2000),
            'max_height' => config('ahhob_blog.upload.optimization.max_image_height', 2000),
            'sizes' => ['small', 'medium'] // 에디터용 썸네일
        ];
    }

    /**
     * 이미지 정보를 데이터베이스에 저장
     *
     * @param \Illuminate\Http\UploadedFile $file 원본 파일
     * @param array $optimizationResult 최적화 결과
     * @return \App\Models\Blog\Image 저장된 이미지 모델
     */
    protected function saveImageToDatabase(\Illuminate\Http\UploadedFile $file, array $optimizationResult): \App\Models\Blog\Image
    {
        return \App\Models\Blog\Image::create([
            'original_name' => $file->getClientOriginalName(),
            'file_name' => $optimizationResult['original']['file_name'],
            'file_path' => $optimizationResult['original']['file_path'],
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'file_size' => $optimizationResult['original']['size'],
            'width' => $optimizationResult['original']['width'],
            'height' => $optimizationResult['original']['height'],
            'thumbnails' => $optimizationResult['thumbnails'],
            'metadata' => $optimizationResult['metadata'],
            'alt_text' => $this->generateDefaultAltText($file->getClientOriginalName()),
            'imageable_type' => 'content',
            'imageable_id' => 0, // 게시물 연결 전 상태
        ]);
    }

    /**
     * 기본 alt 텍스트 생성
     *
     * @param string $fileName 파일명
     * @return string 생성된 alt 텍스트
     */
    protected function generateDefaultAltText(string $fileName): string
    {
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        
        // 언더스코어나 하이픈을 공백으로 변환하고 정리
        $altText = str_replace(['_', '-'], ' ', $baseName);
        $altText = preg_replace('/\s+/', ' ', $altText);
        
        return trim($altText);
    }

    /**
     * 성공 응답 생성
     *
     * @param array $optimizationResult 최적화 결과
     * @param \App\Models\Blog\Image $savedImage 저장된 이미지
     * @return JsonResponse 성공 응답
     */
    protected function successResponse(array $optimizationResult, \App\Models\Blog\Image $savedImage): JsonResponse
    {
        return response()->json([
            'success' => true,
            'url' => $optimizationResult['original']['url'],
            'alt' => $savedImage->alt_text,
            'image_id' => $savedImage->id,
            'thumbnails' => $this->formatThumbnailsForResponse($optimizationResult['thumbnails']),
            'metadata' => [
                'file_size' => $this->formatFileSize($optimizationResult['original']['size']),
                'dimensions' => $optimizationResult['original']['width'] . 'x' . $optimizationResult['original']['height'],
                'format' => $optimizationResult['original']['format']
            ]
        ]);
    }

    // errorResponse 메서드는 ControllerResponseTrait에서 제공됨

    /**
     * 이미지 업로드 예외 처리
     *
     * @param \Exception $e 발생한 예외
     * @param \Illuminate\Http\UploadedFile|null $file 업로드 파일 (있는 경우)
     * @return JsonResponse 오류 응답
     */
    protected function handleImageUploadException(\Exception $e, ?\Illuminate\Http\UploadedFile $file): JsonResponse
    {
        logger()->error('Image upload failed in PostController', [
            'file_name' => $file?->getClientOriginalName(),
            'file_size' => $file?->getSize(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip()
        ]);
        
        $message = '이미지 업로드 중 예상치 못한 오류가 발생했습니다.';
        $debugInfo = app()->environment('local') ? $e->getMessage() : '서버 오류';
        
        return $this->errorResponse($message, [$debugInfo], 500);
    }

    /**
     * 썸네일 정보를 응답용 형태로 변환
     *
     * @param array $thumbnails 원본 썸네일 정보
     * @return array 응답용 썸네일 정보
     */
    protected function formatThumbnailsForResponse(array $thumbnails): array
    {
        return array_map(function($thumbnail) {
            return [
                'url' => $thumbnail['url'],
                'width' => $thumbnail['width'],
                'height' => $thumbnail['height']
            ];
        }, $thumbnails);
    }
    
    // formatFileSize 메서드는 ControllerResponseTrait에서 제공됨

    // getBulkActionMessage 메서드는 ControllerResponseTrait에서 제공됨
}

