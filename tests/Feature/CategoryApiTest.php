<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_categories_returns_all_in_sort_order_with_legacy_shape(): void
    {
        $this->seed();

        $res = $this->getJson('/api/categories');

        $res->assertOk();
        $res->assertJsonCount(5);

        $this->assertSame(['slug', 'name', 'emoji', 'desc'], array_keys($res->json()[0]));
        $this->assertSame('apparel', $res->json()[0]['slug']);
        $this->assertSame('stationery', $res->json()[4]['slug']);
    }
}
