<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'technical_specs' => $this->technical_specs ?? [],
            'image_path' => $this->image_path,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly,
            'stripe_product_id' => $this->when($request->user()?->is_admin, $this->stripe_product_id),
            'stripe_price_id_monthly' => $this->when($request->user()?->is_admin, $this->stripe_price_id_monthly),
            'stripe_price_id_yearly' => $this->when($request->user()?->is_admin, $this->stripe_price_id_yearly),
            'is_available' => (bool) $this->is_available,
            'stock' => (int) $this->stock,
            'requires_shipping' => (bool) $this->requires_shipping,
            'is_purchasable' => (bool) $this->is_available && (int) $this->stock > 0,
            'is_featured' => (bool) $this->is_featured,
            'featured_order' => $this->featured_order,
            'created_at' => $this->created_at,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
