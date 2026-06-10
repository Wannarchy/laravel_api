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
            'billing_name' => $this->billing_name,
            'billing_address' => $this->billing_address,
            'stripe_payment_intent' => $this->stripe_payment_intent,
            'card_last4' => $this->card_last4,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
