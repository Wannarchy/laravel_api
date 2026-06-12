<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'category_id',
        'name',
        'image_path',
        'price_monthly',
        'price_yearly',
        'stripe_product_id',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'is_available',
        'is_featured',
        'featured_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function productSubscriptions(): HasMany
    {
        return $this->hasMany(ProductSubscription::class, 'product_id');
    }
}
