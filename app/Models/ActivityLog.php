<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const ACTOR_ADMIN = 'admin';

    public const ACTOR_USER = 'user';

    public $timestamps = false;

    protected $table = 'logs';

    protected $fillable = [
        'actor_type',
        'admin_id',
        'user_id',
        'action',
        'target_type',
        'target_id',
        'ip',
        'details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
