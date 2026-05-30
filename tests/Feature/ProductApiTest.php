<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_14_products_in_legacy_shape(): void
    {
        $this->seed();

        $res = $this->getJson('/api/products');

        $res->assertOk();
        $res->assertJsonCount(14);

        $first = collect($res->json())->firstWhere('sku', 'QS-APP-001');

        $this->assertSame('QS-APP-001', $first['id']);
        $this->assertSame('QS-APP-001', $first['sku']);
        $this->assertSame('QS Golf Shirt — Dark Green', $first['name']);
        $this->assertSame('Apparel', $first['category']);
        $this->assertSame('apparel', $first['slug']);
        $this->assertSame(580, $first['price']);
        $this->assertSame(750, $first['origPrice']);
        $this->assertSame('Popular', $first['badge']);
        $this->assertSame(18, $first['stock']);
        $this->assertSame(['sizes' => ['S', 'M', 'L', 'XL', 'XXL']], $first['variants']);
        $this->assertContains('Moisture wicking fabric', $first['features']);
    }

    public function test_show_returns_one_product(): void
    {
        $this->seed();

        $res = $this->getJson('/api/products/QS-DRK-001');

        $res->assertOk();
        $this->assertSame('QS Insulated Tumbler', $res->json('name'));
        $this->assertSame('QS-DRK-001', $res->json('id'));
    }

    public function test_show_returns_404_for_unknown_sku(): void
    {
        $this->seed();

        $this->getJson('/api/products/NOPE-999')->assertNotFound();
    }
}
