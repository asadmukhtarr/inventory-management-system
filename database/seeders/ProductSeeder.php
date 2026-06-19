<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get existing categories, brands, and suppliers from database
        $categories = Category::pluck('id')->toArray();
        $brands = Brand::pluck('id')->toArray();
        $suppliers = Supplier::pluck('id')->toArray();
        
        // Check if any data exists
        if (empty($categories)) {
            $this->command->error('No categories found! Please run CategorySeeder first.');
            return;
        }
        
        if (empty($brands)) {
            $this->command->error('No brands found! Please run BrandSeeder first.');
            return;
        }
        
        if (empty($suppliers)) {
            $this->command->error('No suppliers found! Please run SupplierSeeder first.');
            return;
        }
        
        $this->command->info('Found ' . count($categories) . ' categories');
        $this->command->info('Found ' . count($brands) . ' brands');
        $this->command->info('Found ' . count($suppliers) . ' suppliers');
        
        $productNames = [
            // Electronics
            'iPhone', 'Samsung Galaxy', 'Sony Headphones', 'Apple Watch', 'iPad',
            'MacBook Pro', 'Dell XPS', 'HP Laptop', 'Lenovo ThinkPad', 'Asus ROG',
            'JBL Speaker', 'Bose Soundbar', 'Sony TV', 'LG Monitor', 'Canon Camera',
            'DJI Drone', 'GoPro Camera', 'Fitbit Tracker', 'Garmin Watch', 'Razer Mouse',
            
            // Clothing
            'Nike Air Max', 'Adidas Ultraboost', 'Puma T-Shirt', 'Levi\'s Jeans', 'Jack & Jones Jacket',
            'Zara Dress', 'H&M Hoodie', 'Tommy Hilfiger Shirt', 'Calvin Klein Underwear', 'Polo Ralph Lauren',
            'Gucci Belt', 'LV Bag', 'Armani Suit', 'Versace Sunglasses', 'Burberry Scarf',
            
            // Books
            'Atomic Habits', 'Psychology of Money', 'Think and Grow Rich', 'Rich Dad Poor Dad', 'The 7 Habits',
            'The Alchemist', 'Harry Potter', 'The Hobbit', 'Lord of the Rings', 'Game of Thrones',
            'Dune', '1984', 'Animal Farm', 'Pride and Prejudice', 'To Kill a Mockingbird',
            
            // Home & Garden
            'Dyson Vacuum', 'Philips Air Fryer', 'KitchenAid Mixer', 'Breville Toaster', 'Instant Pot',
            'Garden Hose', 'Plant Pot', 'Fertilizer', 'Garden Tools', 'Outdoor Furniture',
            'Lawn Mower', 'Leaf Blower', 'BBQ Grill', 'Fire Pit', 'Bird Feeder',
            
            // Sports
            'Wilson Tennis Racket', 'Puma Football', 'Nike Basketball', 'Adidas Soccer Ball', 'Yoga Mat',
            'Dumbbells', 'Treadmill', 'Exercise Bike', 'Jump Rope', 'Resistance Bands',
            'Baseball Bat', 'Golf Clubs', 'Hockey Stick', 'Ski Goggles', 'Surfboard',
            
            // Toys
            'Lego Set', 'Hot Wheels', 'Barbie Doll', 'Action Figures', 'Board Games',
            'Puzzle', 'Remote Control Car', 'Play-Doh', 'Slime', 'Teddy Bear',
            'Nerf Gun', 'Magic Set', 'Dollhouse', 'Train Set', 'Robot Toy',
            
            // Automotive
            'Michelin Tires', 'Exide Battery', 'Castrol Engine Oil', 'Car Wax', 'Air Freshener',
            'Car Cover', 'Floor Mats', 'GPS Navigation', 'Dash Cam', 'Car Charger',
            'Jump Starter', 'Tire Inflator', 'Car Vacuum', 'Sunshade', 'Seat Covers',
            
            // Health & Beauty
            'L\'Oreal Hair Kit', 'Vitamin C Serum', 'Nivea Cream', 'Dove Soap', 'Pantene Shampoo',
            'Oral-B Toothbrush', 'Colgate Whitening', 'Garnier Face Wash', 'Nail Polish', 'Perfume',
            'Face Mask', 'Sunscreen', 'Lip Balm', 'Hand Sanitizer', 'Body Lotion',
            
            // Food & Biscuits
            'Oreo Biscuits', 'Parle-G Biscuits', 'Britannia Cookies', 'Cadbury Chocolate', 'Lays Chips',
            'Pringles Can', 'KitKat', 'Dairy Milk', 'Bourbon Biscuits', 'Hide & Seek',
            'Good Day Butter', 'Marie Biscuits', 'Cream Crackers', 'Kurkure', 'Cheetos',
            
            // Furniture
            'Office Chair', 'Dining Table', 'Sofa Set', 'Bookshelf', 'Wardrobe',
            'Bed Frame', 'Mattress', 'Study Desk', 'Coffee Table', 'Shoe Rack',
            'TV Stand', 'Nightstand', 'Vanity Table', 'Cabinet', 'Bench'
        ];
        
        $this->command->info('Creating 100 products...');
        
        // Create 100 products
        for ($i = 0; $i < 100; $i++) {
            $productName = $faker->randomElement($productNames) . ' ' . ($i + 1);
            $sku = 'SKU-' . strtoupper($faker->bothify('???')) . '-' . str_pad(($i + 1), 5, '0', STR_PAD_LEFT);
            
            // Assign random category, brand, supplier
            $categoryId = $categories[array_rand($categories)];
            $brandId = $brands[array_rand($brands)];
            $supplierId = $suppliers[array_rand($suppliers)];
            
            $productData = [
                'name' => $productName,
                'sku' => $sku,
                'description' => $faker->paragraph(2),
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'supplier_id' => $supplierId,
                'quantity' => 100,
                'sale_price' => $faker->randomFloat(2, 100, 9999),
                'purchase_price' => $faker->randomFloat(2, 50, 8000),
                'status' => $faker->randomElement([0, 1]),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            Product::create($productData);
        }
        
        $this->command->info('✅ 100 products created successfully with 100 quantity each!');
        $this->command->info('📦 Total products: ' . Product::count());
        
        // Display sample products
        $sampleProducts = Product::with(['category', 'brand', 'supplier'])->limit(5)->get();
        $this->command->info('Sample products:');
        foreach ($sampleProducts as $product) {
            $this->command->line("  - {$product->name} | Category: {$product->category->title} | Brand: {$product->brand->title} | Quantity: {$product->quantity}");
        }
    }
}