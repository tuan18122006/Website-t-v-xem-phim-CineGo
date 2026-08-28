<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Sự kiện', 'slug' => 'su-kien', 'description' => 'Các sự kiện khuyến mãi, ưu đãi và hoạt động tại CineGo'],
            ['name' => 'Tin tức phim', 'slug' => 'tin-tuc-phim', 'description' => 'Tin tức mới nhất về điện ảnh'],
            ['name' => 'Đánh giá phim', 'slug' => 'danh-gia-phim', 'description' => 'Đánh giá và review phim'],
        ];

        foreach ($categories as $cat) {
            BlogCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }
}
