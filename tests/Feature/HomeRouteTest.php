<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeRouteTest extends TestCase
{
    public function test_the_root_url_serves_the_storefront_index_html(): void
    {
        $res = $this->get('/');
        $res->assertOk();
        $res->assertSee('Shop All Products', false);
    }
}
