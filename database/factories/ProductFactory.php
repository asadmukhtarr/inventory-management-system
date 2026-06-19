<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $categories = Category::pluck('id')->toArray();
        $brands = Brand::pluck('id')->toArray();
        $suppliers = Supplier::pluck('id')->toArray();
        
        $productNames = [
            'Smartphone', 'Laptop', 'Headphones', 'Smart Watch', 'Tablet',
            'Running Shoes', 'Backpack', 'Jacket', 'Jeans', 'T-Shirt',
            'Coffee Maker', 'Blender', 'Toaster', 'Microwave', 'Refrigerator',
            'Biscuits', 'Chocolate', 'Chips', 'Cookies', 'Candy',
            'Dumbbells', 'Yoga Mat', 'Tennis Racket', 'Football', 'Basketball',
            'Books', 'Notebook', 'Pen Set', 'Desk Lamp', 'Chair',
            'Perfume', 'Shampoo', 'Soap', 'Lotion', 'Cream',
            'Toy Car', 'Action Figure', 'Board Game', 'Puzzle', 'Doll',
            'Car Tire', 'Car Battery', 'Engine Oil', 'Car Wax', 'Air Freshener',
            'Garden Tools', 'Plant Pot', 'Fertilizer', 'Seeds', 'Garden Hose',
            'Pet Food', 'Pet Toy', 'Pet Bed', 'Pet Leash', 'Pet Bowl'
        ];
        
        return [
            'name' => $this->faker->randomElement($productNames) . ' ' . $this->faker->randomNumber(3),
            'sku' => 'SKU-' . strtoupper($this->faker->bothify('???')) . '-' . $this->faker->unique()->randomNumber(5),
            'description' => $this->faker->sentence(10),
            'category_id' => $this->faker->randomElement($categories) ?? 1,
            'brand_id' => $this->faker->randomElement($brands) ?? 1,
            'supplier_id' => $this->faker->randomElement($suppliers) ?? 1,
            'quantity' => 100,
            'sale_price' => $this->faker->randomFloat(2, 100, 1000),
            'purchase_price' => $this->faker->randomFloat(2, 50, 800),
            'status' => $this->faker->randomElement([0, 1]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}