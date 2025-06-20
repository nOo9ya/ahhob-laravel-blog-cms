<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // 최상위 카테고리들
        $rootCategories = [
            [
                'name' => '개발',
                'slug' => 'development',
                'description' => '프로그래밍 및 개발 관련 글',
                'color' => '#3b82f6',
                'icon' => 'code',
                'sort_order' => 1,
            ],
            [
                'name' => '일상',
                'slug' => 'daily',
                'description' => '일상 생활과 경험담',
                'color' => '#10b981',
                'icon' => 'heart',
                'sort_order' => 2,
            ],
            [
                'name' => '리뷰',
                'slug' => 'review',
                'description' => '제품, 서비스 리뷰',
                'color' => '#f59e0b',
                'icon' => 'star',
                'sort_order' => 3,
            ],
        ];

        foreach ($rootCategories as $categoryData) {
            $category = Category::create($categoryData);

            // 개발 카테고리에 하위 카테고리 추가
            if ($category->slug === 'development') {
                $subCategories = [
                    ['name' => 'Laravel', 'slug' => 'laravel', 'color' => '#ef4444'],
                    ['name' => 'Vue.js', 'slug' => 'vuejs', 'color' => '#22c55e'],
                    ['name' => 'React', 'slug' => 'react', 'color' => '#06b6d4'],
                    ['name' => 'PHP', 'slug' => 'php', 'color' => '#8b5cf6'],
                ];

                foreach ($subCategories as $index => $subCategoryData) {
                    Category::create([
                        ...$subCategoryData,
                        'parent_id' => $category->id,
                        'description' => $subCategoryData['name'] . ' 관련 개발 글',
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        }
    }
}
