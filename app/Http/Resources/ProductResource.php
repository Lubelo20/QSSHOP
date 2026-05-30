<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->sku,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'category'    => $this->category->name,
            'slug'        => $this->category->slug,
            'price'       => $this->price,
            'origPrice'   => $this->orig_price,
            'emoji'       => $this->emoji,
            'badge'       => $this->badge,
            'stock'       => $this->stock,
            'image'       => $this->image,
            'variants'    => $this->variants,
            'features'    => $this->features,
            'description' => $this->description,
        ];
    }
}
