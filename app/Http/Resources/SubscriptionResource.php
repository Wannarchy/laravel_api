<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'cycle' => $this->cycle,
            'price' => $this->price,
            'status' => $this->status,
            'stripe_subscription_id' => $this->stripe_subscription_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'next_billing' => $this->next_billing?->format('Y-m-d'),
            'cancelled_at' => $this->cancelled_at,
            'renewal_notified' => (bool) $this->renewal_notified,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
