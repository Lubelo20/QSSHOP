<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_load_5_categories_and_14_products(): void
    {
        $this->seed();

        $this->assertSame(5, Category::count());
        $this->assertSame(14, Product::count());

        $shirt = Product::where('sku', 'QS-APP-001')->first();
        $this->assertSame('QS Golf Shirt — Dark Green', $shirt->name);
        $this->assertSame('apparel', $shirt->category->slug);
        $this->assertSame(['sizes' => ['S', 'M', 'L', 'XL', 'XXL']], $shirt->variants);
    }
}
