<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'general', 'name' => 'General / ทั่วไป'],
            ['code' => 'world', 'name' => 'World / โลก'],
            ['code' => 'politics', 'name' => 'Politics / การเมือง'],
            ['code' => 'business', 'name' => 'Business / ธุรกิจ'],
            ['code' => 'technology', 'name' => 'Technology / เทคโนโลยี'],
            ['code' => 'sports', 'name' => 'Sports / กีฬา'],
            ['code' => 'science', 'name' => 'Science / วิทยาศาสตร์'],
            ['code' => 'health', 'name' => 'Health / สุขภาพ'],
            ['code' => 'entertainment', 'name' => 'Entertainment / บันเทิง'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['code' => $category['code']], [
                'name' => $category['name'],
                'is_active' => true,
            ]);
        }
    }
}
