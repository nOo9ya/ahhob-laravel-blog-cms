<?php

namespace App\Http\Resources\Ahhob\Blog\Api\Post;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($this->shouldShowContent($request), $this->content),
            'featured_image' => $this->featured_image,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'reading_time' => $this->reading_time,
            'stats' => [
                'views' => $this->views_count,
                'likes' => $this->likes_count,
                'comments' => $this->comments_count,
                'shares' => $this->shares_count,
            ],
            'dates' => [
                'published_at' => $this->published_at?->toISOString(),
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
            ],
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar' => $this->user->avatar,
                'bio' => $this->user->bio,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'color' => $this->category->color,
                'icon' => $this->category->icon,
            ],
            'tags' => $this->tags->pluck('name'),
            'meta' => $this->when($this->shouldShowContent($request), [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
                'keywords' => $this->meta_keywords,
                'og_title' => $this->og_title,
                'og_description' => $this->og_description,
                'og_image' => $this->og_image,
            ]),
        ];
    }

    private function shouldShowContent(Request $request): bool
    {
        return in_array($request->route()->getName(), [
            'api.posts.show',
            'api.posts.store',
            'api.posts.update'
        ]);
    }
}
