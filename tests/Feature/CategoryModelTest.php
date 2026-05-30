<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_be_created_and_read_back(): void
    {
        $cat = Category::create([
            'slug'        => 'apparel',
            'name'        => 'Apparel',
            'emoji'       => '👕',
            'description' => 'Premium branded golf shirts, caps and outerwear for your team.',
            'sort_order'  => 1,
        ]);

        $this->assertSame('Apparel', $cat->fresh()->name);
        $this->assertSame(1, Category::count());
    }
}
