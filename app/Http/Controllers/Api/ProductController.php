<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(
            Product::with('category')->orderBy('sort_order')->get()
        );
    }

    public function show(string $sku)
    {
        $product = Product::with('category')->where('sku', $sku)->firstOrFail();

        return new ProductResource($product);
    }
}
