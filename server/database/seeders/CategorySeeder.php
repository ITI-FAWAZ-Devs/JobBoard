<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Software Development', 'description' => 'Web, mobile, and software engineering roles'],
            ['name' => 'Data Science', 'description' => 'Data analysis, ML, and AI positions'],
            ['name' => 'Design', 'description' => 'UI/UX, graphic design, and creative roles'],
            ['name' => 'Marketing', 'description' => 'Digital marketing, SEO, and content strategy'],
            ['name' => 'Sales', 'description' => 'Sales, business development, and account management'],
            ['name' => 'Finance', 'description' => 'Accounting, financial analysis, and banking'],
            ['name' => 'Human Resources', 'description' => 'Recruiting, HR management, and talent acquisition'],
            ['name' => 'Customer Support', 'description' => 'Technical support and customer service'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
