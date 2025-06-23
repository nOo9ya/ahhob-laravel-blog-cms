<?php

namespace App\Services\Ahhob\Blog\Shared\Post;

use App\Models\Blog\Post;
use App\Models\Blog\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    /**
     * 게시물 생성
     * @param array $data
     * @param UploadedFile|null $featuredImage
     * @param UploadedFile|null $ogImage
     * @return Post
     */
    public function createPost(
        array $data,
        ?UploadedFile $featuredImage = null,
        ?UploadedFile $ogImage = null
    ): Post
    {
        // 이미지 처리
        if ($featuredImage) {
            $data['featured_image'] = $this->uploadImage($featuredImage, 'posts');
        }

        if ($ogImage) {
            $data['og_image'] = $this->uploadImage($ogImage, 'og');
        }

        // 게시물 생성
        $post = Post::create($data);

        // 태그 처리
        if (isset($data['tags'])) {
            $this->syncTags($post, $data['tags']);
        }

        return $post->load(['user', 'category', 'tags']);
    }

    /**
     * 게시물 업데이트
     * @param Post $post
     * @param array $data
     * @param UploadedFile|null $featuredImage
     * @param UploadedFile|null $ogImage
     * @return Post
     */
    public function updatePost(
        Post $post,
        array $data,
        ?UploadedFile $featuredImage = null,
        ?UploadedFile $ogImage = null
    ): Post
    {
        // 기존 이미지 백업
        $oldFeaturedImage = $post->featured_image;
        $oldOgImage = $post->og_image;

        // 새 이미지 업로드
        if ($featuredImage) {
            $data['featured_image'] = $this->uploadImage($featuredImage, 'posts');
        }

        if ($ogImage) {
            $data['og_image'] = $this->uploadImage($ogImage, 'og');
        }

        // 게시물 업데이트
        $post->update($data);

        // 기존 이미지 삭제
        if ($featuredImage && $oldFeaturedImage) {
            $this->deleteImage($oldFeaturedImage);
        }

        if ($ogImage && $oldOgImage) {
            $this->deleteImage($oldOgImage);
        }

        // 태그 처리
        if (isset($data['tags'])) {
            $this->syncTags($post, $data['tags']);
        }

        return $post->load(['user', 'category', 'tags']);
    }

    /**
     * 게시물 삭제
     * @param Post $post
     * @return bool
     */
    public function deletePost(Post $post): bool
    {
        // 이미지 파일 삭제
        if ($post->featured_image) {
            $this->deleteImage($post->featured_image);
        }

        if ($post->og_image) {
            $this->deleteImage($post->og_image);
        }

        return $post->delete();
    }

    /**
     * 태그 동기화
     * @param Post $post
     * @param array $tagNames
     * @return void
     */
    private function syncTags(Post $post, array $tagNames): void
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;

            $tag = Tag::firstOrCreate(
                ['name' => $tagName],
                ['slug' => Str::slug($tagName), 'color' => $this->getRandomColor()]
            );

            $tagIds[] = $tag->id;
        }

        $post->tags()->sync($tagIds);

        // 태그별 포스트 수 업데이트
        Tag::whereIn('id', $tagIds)->get()->each(function ($tag) {
            $tag->updatePostsCount();
        });
    }

    /**
     * 이미지 업로드
     * @param UploadedFile $file
     * @param string $folder
     * @return string
     */
    private function uploadImage(UploadedFile $file, string $folder): string
    {
        $path = $file->store("uploads/{$folder}", 'public');
        return 'storage/' . $path;
    }

    /**
     * 이미지 삭제
     * @param string $imagePath
     * @return bool
     */
    private function deleteImage(string $imagePath): bool
    {
        $path = str_replace('storage/', '', $imagePath);
        return Storage::disk('public')->delete($path);
    }

    /**
     * 랜덤 색상 생성
     * @return string
     */
    private function getRandomColor(): string
    {
        $colors = ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e'];
        return $colors[array_rand($colors)];
    }

    /**
     * 게시물 검색
     * @param string $query
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function searchPosts(string $query, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $posts = Post::with(['user', 'category', 'tags'])
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%");
            });

        // 카테고리 필터
        if (isset($filters['category_id'])) {
            $posts->where('category_id', $filters['category_id']);
        }

        // 태그 필터
        if (isset($filters['tags'])) {
            $posts->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('name', $filters['tags']);
            });
        }

        // 기간 필터
        if (isset($filters['date_from'])) {
            $posts->where('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $posts->where('published_at', '<=', $filters['date_to']);
        }

        // 정렬
        $sortBy = $filters['sort'] ?? 'latest';
        switch ($sortBy) {
            case 'popular':
                $posts->orderBy('views_count', 'desc');
                break;
            case 'liked':
                $posts->orderBy('likes_count', 'desc');
                break;
            case 'commented':
                $posts->orderBy('comments_count', 'desc');
                break;
            default:
                $posts->orderBy('published_at', 'desc');
        }

        return $posts->paginate($filters['per_page'] ?? 15);
    }

    /**
     * 인기 게시물 조회
     * @param int $limit
     * @param string $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularPosts(int $limit = 10, string $period = 'week'): \Illuminate\Database\Eloquent\Collection
    {
        $query = Post::with(['user', 'category'])
            ->where('status', 'published');

        // 기간별 필터
        switch ($period) {
            case 'today':
                $query->whereDate('published_at', today());
                break;
            case 'week':
                $query->where('published_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('published_at', '>=', now()->subMonth());
                break;
        }

        return $query->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
