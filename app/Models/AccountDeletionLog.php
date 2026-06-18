<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
