<?php

namespace App\Http\Resources\Ahhob\Blog\Web\Post;

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
            'content' => $this->when($request->route()->getName() === 'posts.show', $this->content),
            'featured_image' => $this->featured_image,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'allow_comments' => $this->allow_comments,
            'reading_time' => $this->reading_time,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // 관계
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar' => $this->user->avatar,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'color' => $this->category->color,
            ],
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                ];
            }),

            // SEO 정보 (상세 페이지에서만)
            'seo' => $this->when($request->route()->getName() === 'posts.show', [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'og_title' => $this->og_title,
                'og_description' => $this->og_description,
                'og_image' => $this->og_image,
                'canonical_url' => $this->canonical_url,
            ]),
        ];
    }
}
