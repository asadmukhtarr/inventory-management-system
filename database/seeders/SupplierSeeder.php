<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use Faker\Factory as Faker;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        
        $suppliers = [
            ['name' => 'TechMart', 'email' => 'techmart@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'FashionHub', 'email' => 'fashionhub@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'BookWorld', 'email' => 'bookworld@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'HomeDepot', 'email' => 'homedepot@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'SportsZone', 'email' => 'sportszone@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'ToyKingdom', 'email' => 'toykingdom@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'AutoParts', 'email' => 'autoparts@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'BeautyCo', 'email' => 'beautyco@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'BiscuitWorld', 'email' => 'biscuitworld@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'GlobalSupplies', 'email' => 'globalsupplies@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'ElectroWorld', 'email' => 'electroworld@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
            ['name' => 'FurnitureMart', 'email' => 'furnituremart@example.com', 'phone' => $faker->phoneNumber, 'status' => 'active'],
        ];
        
        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
        
        $this->command->info('Suppliers seeded successfully!');
    }
}