<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['title' => 'Apple', 'status' => 'active'],
            ['title' => 'Samsung', 'status' => 'active'],
            ['title' => 'Sony', 'status' => 'active'],
            ['title' => 'Nike', 'status' => 'active'],
            ['title' => 'Adidas', 'status' => 'active'],
            ['title' => 'Dyson', 'status' => 'active'],
            ['title' => 'Philips', 'status' => 'active'],
            ['title' => 'Lego', 'status' => 'active'],
            ['title' => 'Oreo', 'status' => 'active'],
            ['title' => 'Britannia', 'status' => 'active'],
            ['title' => 'Parle', 'status' => 'active'],
            ['title' => 'LG', 'status' => 'active'],
            ['title' => 'Dell', 'status' => 'active'],
            ['title' => 'HP', 'status' => 'active'],
            ['title' => 'Lenovo', 'status' => 'active'],
        ];
        
        foreach ($brands as $brand) {
            Brand::create($brand);
        }
        
        $this->command->info('Brands seeded successfully!');
    }
}