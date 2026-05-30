<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku', 'name', 'category_id', 'price', 'orig_price', 'emoji',
        'badge', 'stock', 'image', 'description', 'features', 'variants', 'sort_order',
    ];

    protected $casts = [
        'features'   => 'array',
        'variants'   => 'array',
        'price'      => 'integer',
        'orig_price' => 'integer',
        'stock'      => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
