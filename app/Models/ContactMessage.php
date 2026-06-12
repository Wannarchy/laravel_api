<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'sujet',
        'message',
        'admin_reply',
        'replied_by',
        'replied_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function isReplied(): bool
    {
        return $this->admin_reply !== null && $this->admin_reply !== '';
    }
}
