<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSubscription extends Model
{
    public $timestamps = false;

    protected $table = 'product_subscriptions';

    protected $fillable = [
        'user_id',
        'order_id',
        'product_id',
        'cycle',
        'price',
        'status',
        'stripe_subscription_id',
        'start_date',
        'next_billing',
        'cancelled_at',
        'renewal_notified',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'start_date' => 'date',
            'next_billing' => 'date',
            'cancelled_at' => 'datetime',
            'renewal_notified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
