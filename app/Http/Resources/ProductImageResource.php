<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'image_path' => $this->image_path,
            'caption' => $this->caption,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
