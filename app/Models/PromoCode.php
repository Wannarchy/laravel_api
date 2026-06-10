<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_amount',
        'max_uses',
        'uses_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
