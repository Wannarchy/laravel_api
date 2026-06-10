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
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
