<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['title' => 'Electronics', 'status' => 'active'],
            ['title' => 'Clothing', 'status' => 'active'],
            ['title' => 'Books', 'status' => 'active'],
            ['title' => 'Home & Garden', 'status' => 'active'],
            ['title' => 'Sports', 'status' => 'active'],
            ['title' => 'Toys', 'status' => 'active'],
            ['title' => 'Automotive', 'status' => 'active'],
            ['title' => 'Health & Beauty', 'status' => 'active'],
            ['title' => 'Biscuits & Cookies', 'status' => 'active'],
            ['title' => 'Furniture', 'status' => 'active'],
        ];
        
        foreach ($categories as $category) {
            Category::create($category);
        }
        
        $this->command->info('Categories seeded successfully!');
    }
}