<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'apparel', 'name' => 'Apparel', 'emoji' => '👕', 'description' => 'Premium branded golf shirts, caps and outerwear for your team.', 'sort_order' => 1],
            ['slug' => 'drinkware', 'name' => 'Drinkware', 'emoji' => '🥤', 'description' => 'Insulated tumblers and water bottles, branded for your event.', 'sort_order' => 2],
            ['slug' => 'golf-accessories', 'name' => 'Golf Accessories', 'emoji' => '⛳', 'description' => 'Branded balls, towels, shoe bags, umbrellas and more.', 'sort_order' => 3],
            ['slug' => 'gift-sets', 'name' => 'Gift Sets', 'emoji' => '🎁', 'description' => 'Curated premium gift boxes and corporate starter packs.', 'sort_order' => 4],
            ['slug' => 'stationery', 'name' => 'Stationery & Branding', 'emoji' => '📓', 'description' => 'Branded notebooks and pen sets for a complete event kit.', 'sort_order' => 5],
        ];

        foreach ($categories as $c) {
            Category::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
