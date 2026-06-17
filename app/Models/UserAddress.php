<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'label',
        'usage_type',
        'prenom',
        'nom',
        'adresse1',
        'adresse2',
        'ville',
        'region',
        'code_postal',
        'pays',
        'telephone',
        'is_default',
        'is_default_shipping',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_default_shipping' => 'boolean',
        ];
    }

    public function supportsBilling(): bool
    {
        return in_array($this->usage_type, ['billing', 'both'], true);
    }

    public function supportsShipping(): bool
    {
        return in_array($this->usage_type, ['shipping', 'both'], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
