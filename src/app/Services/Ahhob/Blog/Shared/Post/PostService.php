<?php

namespace App\Services\Ahhob\Blog\Shared;

use App\Models\Blog\Post;
use App\Models\Blog\Category;
use App\Models\Blog\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostService
{
    private MarkdownService $markdownService;

    public function __construct(MarkdownService $markdownService)
    {
        $this->markdownService = $markdownService;
    }

    /**
     * 게시물 생성
     */
    public function create(array $data): Post
    {
        // 슬러그 생성
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // 마크다운을 HTML로 변환
        if (isset($data['content'])) {
            $data['content_html'] = $this->markdownService->toHtml($data['content']);
        }

        // 요약 자동 생성 (비어있을 경우)
        if (empty($data['excerpt']) && isset($data['content'])) {
            $data['excerpt'] = $this->markdownService->extractExcerpt($data['content']);
        }

        // 메타 제목 기본값 설정
        if (empty($data['meta_title'])) {
            $data['meta_title'] = $data['title'];
        }

        // 메타 설명 기본값 설정
        if (empty($data['meta_description']) && !empty($data['excerpt'])) {
            $data['meta_description'] = $data['excerpt'];
        }

        // 발행일 처리
        if (isset($data['published_at'])) {
            $data['published_at'] = Carbon::parse($data['published_at']);
        }

        // 게시물 생성
        $post = Post::create($data);

        // 이미지 업로드 처리
        if (isset($data['featured_image'])) {
            $this->uploadFeaturedImage($post, $data['featured_image']);
        }

        if (isset($data['og_image'])) {
            $this->uploadOgImage($post, $data['og_image']);
        }

        // 태그 처리
        if (isset($data['tags'])) {
            $this->syncTags($post, $data['tags']);
        }

        return $post->load('category', 'tags');
    }

    /**
     * 게시물 업데이트
     */
    public function update(Post $post, array $data): Post
    {
        // 슬러그 생성 (변경된 경우만)
        if (isset($data['title']) && ($data['slug'] === null || $data['slug'] !== $post->slug)) {
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['title'], $post->id);
            }
        }

        // 마크다운을 HTML로 변환
        if (isset($data['content'])) {
            $data['content_html'] = $this->markdownService->toHtml($data['content']);

            // 요약 자동 업데이트 (비어있을 경우)
            if (empty($data['excerpt'])) {
                $data['excerpt'] = $this->markdownService->extractExcerpt($data['content']);
            }
        }

        // 메타 제목 기본값 설정
        if (isset($data['title']) && empty($data['meta_title'])) {
            $data['meta_title'] = $data['title'];
        }

        // 메타 설명 기본값 설정
        if (empty($data['meta_description']) && !empty($data['excerpt'])) {
            $data['meta_description'] = $data['excerpt'];
        }

        // 발행일 처리
        if (isset($data['published_at'])) {
            $data['published_at'] = Carbon::parse($data['published_at']);
        }

        // 게시물 업데이트
        $post->update($data);

        // 이미지 업로드 처리
        if (isset($data['featured_image'])) {
            $this->uploadFeaturedImage($post, $data['featured_image']);
        }

        if (isset($data['og_image'])) {
            $this->uploadOgImage($post, $data['og_image']);
        }

        // 태그 처리
        if (isset($data['tags'])) {
            $this->syncTags($post, $data['tags']);
        }

        return $post->load('category', 'tags');
    }

    /**
     * 썸네일 이미지 업로드
     */
    public function uploadFeaturedImage(Post $post, UploadedFile $file): void
    {
        // 기존 이미지 삭제
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $filename = $this->generateUniqueFilename($file, 'featured');
        $path = $file->storeAs('uploads/posts/featured', $filename, 'public');

        $post->update(['featured_image' => $path]);
    }

    /**
     * OG 이미지 업로드
     */
    public function uploadOgImage(Post $post, UploadedFile $file): void
    {
        // 기존 이미지 삭제
        if ($post->og_image) {
            Storage::disk('public')->delete($post->og_image);
        }

        $filename = $this->generateUniqueFilename($file, 'og');
        $path = $file->storeAs('uploads/posts/og', $filename, 'public');

        $post->update(['og_image' => $path]);
    }

    /**
     * 태그 동기화
     */
    private function syncTags(Post $post, string $tagsString): void
    {
        if (empty($tagsString)) {
            $post->tags()->detach();
            return;
        }

        $tagNames = array_map('trim', explode(',', $tagsString));
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            if (!empty($tagName)) {
                $tag = Tag::firstOrCreate([
                    'name' => $tagName,
                    'slug' => Str::slug($tagName)
                ]);
                $tagIds[] = $tag->id;
            }
        }

        $post->tags()->sync($tagIds);
    }

    /**
     * 고유한 슬러그 생성
     */
    private function generateSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        $query = Post::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;

            $query = Post::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * 고유한 파일명 생성
     */
    private function generateUniqueFilename(UploadedFile $file, string $prefix = ''): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName);

        if (strlen($safeName) > 50) {
            $safeName = substr($safeName, 0, 50);
        }

        $filename = $prefix ? $prefix . '_' : '';
        $filename .= $safeName . '_' . time() . '_' . Str::random(8) . '.' . $extension;

        return $filename;
    }

    /**
     * 게시물 삭제
     */
    public function delete(Post $post): bool
    {
        // 관련 이미지 삭제
        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        if ($post->og_image) {
            Storage::disk('public')->delete($post->og_image);
        }

        // 게시물 삭제 (관련 댓글, 태그 관계도 함께 삭제됨)
        return $post->delete();
    }

    /**
     * 게시물 목록 조회 (관리자용)
     */
    public function getPostsForAdmin(array $filters = [], int $perPage = 15)
    {
        $query = Post::with(['category', 'user', 'tags'])
            ->withCount('comments');

        // 필터 적용
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * 발행된 게시물 목록 조회 (웹용)
     */
    public function getPublishedPosts(array $filters = [], int $perPage = 10)
    {
        $query = Post::published()
            ->with(['category', 'user', 'tags'])
            ->withCount('comments');

        // 필터 적용
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', $filters['tag_id']);
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('excerpt', 'like', '%' . $filters['search'] . '%');
            });
        }

        // 정렬: 고정 게시물 우선, 그 다음 발행일 역순
        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 인기 게시물 조회
     */
    public function getPopularPosts(int $limit = 5)
    {
        return Post::published()
            ->with(['category'])
            ->withCount('views')
            ->orderBy('views_count', 'desc')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 최근 게시물 조회
     */
    public function getRecentPosts(int $limit = 5, ?int $excludeId = null)
    {
        $query = Post::published()
            ->with(['category'])
            ->orderBy('published_at', 'desc');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 관련 게시물 조회
     */
    public function getRelatedPosts(Post $post, int $limit = 3)
    {
        $query = Post::published()
            ->where('id', '!=', $post->id)
            ->with(['category']);

        // 같은 카테고리의 게시물 우선
        if ($post->category_id) {
            $query->where('category_id', $post->category_id);
        }

        return $query->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
