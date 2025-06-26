<?php

namespace App\Services\Ahhob\Blog\Shared\Post;

use App\Traits\Blog\QueryBuilderTrait;
use App\Services\Ahhob\Blog\Shared\MarkdownService;
use App\Models\Blog\Post;
use App\Models\Blog\Category;
use App\Models\Blog\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 공유 게시물 서비스 - 모든 모드에서 사용되는 기본 CRUD 로직
 * 
 * 이 서비스는 Admin, Web, API 서비스의 부모 클래스로,
 * 공통된 게시물 생성, 수정, 삭제 로직을 제공합니다.
 * QueryBuilderTrait를 사용하여 중복 코드를 방지합니다.
 * 
 * 주요 기능:
 * - 게시물 CRUD 작업
 * - 이미지 업로드 및 관리
 * - 태그 동기화
 * - 슬러그 생성
 * - 마크다운 처리
 */
class PostService
{
    use QueryBuilderTrait;
    
    protected MarkdownService $markdownService;

    public function __construct(MarkdownService $markdownService)
    {
        $this->markdownService = $markdownService;
    }

    /**
     * 게시물 생성 (모든 모드에서 공통 사용)
     * 
     * @param array $data 게시물 데이터
     * @return Post 생성된 게시물
     */
    public function createPost(array $data, ?UploadedFile $featuredImage = null, ?UploadedFile $ogImage = null): Post
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
        if ($featuredImage) {
            $this->uploadFeaturedImage($post, $featuredImage);
        }

        if ($ogImage) {
            $this->uploadOgImage($post, $ogImage);
        }

        // 태그 처리
        if (isset($data['tags'])) {
            $this->syncTags($post, $data['tags']);
        }

        return $post->load('category', 'tags');
    }

    /**
     * 게시물 업데이트 (모든 모드에서 공통 사용)
     * 
     * @param Post $post 수정할 게시물
     * @param array $data 수정 데이터
     * @param UploadedFile|null $featuredImage 대표 이미지 파일
     * @param UploadedFile|null $ogImage OG 이미지 파일
     * @return Post 수정된 게시물
     */
    public function updatePost(Post $post, array $data, ?UploadedFile $featuredImage = null, ?UploadedFile $ogImage = null): Post
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
        if ($featuredImage) {
            $this->uploadFeaturedImage($post, $featuredImage);
        }

        if ($ogImage) {
            $this->uploadOgImage($post, $ogImage);
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
     * 태그 동기화 (문자열 또는 배열 모두 지원)
     * 
     * @param Post $post 게시물
     * @param string|array $tags 태그 데이터
     * @return void
     */
    protected function syncTags(Post $post, $tags): void
    {
        if (empty($tags)) {
            $post->tags()->detach();
            return;
        }

        // 문자열인 경우 배열로 변환
        if (is_string($tags)) {
            $tagNames = array_map('trim', explode(',', $tags));
        } else {
            $tagNames = is_array($tags) ? $tags : [];
        }
        
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
     * 
     * @param string $title 제목
     * @param int|null $excludeId 제외할 게시물 ID (수정 시)
     * @return string 생성된 슬러그
     */
    protected function generateSlug(string $title, ?int $excludeId = null): string
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
     * 
     * @param UploadedFile $file 업로드된 파일
     * @param string $prefix 파일명 접두사
     * @return string 생성된 파일명
     */
    protected function generateUniqueFilename(UploadedFile $file, string $prefix = ''): string
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
     * 게시물 삭제 (관련 파일도 함께 삭제)
     * 
     * @param Post $post 삭제할 게시물
     * @return bool 삭제 성공 여부
     */
    public function deletePost(Post $post): bool
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

    // 이전 중복 메서드들은 제거됨
    // 각 서비스(Admin, Web, API)에서 QueryBuilderTrait를 사용하여 구현
}
