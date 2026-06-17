<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'total',
        'subtotal',
        'tax_amount',
        'promo_discount',
        'promo_code',
        'billing_name',
        'billing_address',
        'shipping_name',
        'shipping_address',
        'stripe_payment_intent',
        'stripe_checkout_session_id',
        'card_last4',
        'payment_brand',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'promo_discount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function productSubscriptions(): HasMany
    {
        return $this->hasMany(ProductSubscription::class, 'order_id');
    }
}
