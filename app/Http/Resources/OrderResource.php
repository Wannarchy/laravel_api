<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total' => $this->total,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'promo_discount' => $this->promo_discount,
            'promo_code' => $this->promo_code,
            'subtotal_ht' => round((float) $this->total - (float) $this->tax_amount, 2),
            'billing_name' => $this->billing_name,
            'billing_address' => $this->billing_address,
            'shipping_name' => $this->shipping_name,
            'shipping_address' => $this->shipping_address,
            'stripe_payment_intent' => $this->stripe_payment_intent,
            'card_last4' => $this->card_last4,
            'payment_brand' => $this->payment_brand,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
