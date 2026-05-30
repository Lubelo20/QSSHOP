<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug'  => $this->slug,
            'name'  => $this->name,
            'emoji' => $this->emoji,
            'desc'  => $this->description,
        ];
    }
}
