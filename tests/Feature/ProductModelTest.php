<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_stores_json_features_variants_and_belongs_to_a_category(): void
    {
        $cat = Category::create([
            'slug'        => 'apparel',
            'name'        => 'Apparel',
            'emoji'       => '👕',
            'description' => 'x',
            'sort_order'  => 1,
        ]);

        $product = Product::create([
            'sku'         => 'QS-APP-001',
            'name'        => 'QS Golf Shirt — Dark Green',
            'category_id' => $cat->id,
            'price'       => 580,
            'orig_price'  => 750,
            'emoji'       => '👕',
            'badge'       => 'Popular',
            'stock'       => 18,
            'image'       => 'https://example.com/shirt.jpg',
            'description' => 'A shirt.',
            'features'    => ['Moisture wicking fabric', 'UV protection built-in'],
            'variants'    => ['sizes' => ['S', 'M', 'L', 'XL', 'XXL']],
            'sort_order'  => 1,
        ]);

        $fresh = $product->fresh();

        $this->assertSame(['Moisture wicking fabric', 'UV protection built-in'], $fresh->features);
        $this->assertSame(['sizes' => ['S', 'M', 'L', 'XL', 'XXL']], $fresh->variants);
        $this->assertSame('Apparel', $fresh->category->name);
    }
}
