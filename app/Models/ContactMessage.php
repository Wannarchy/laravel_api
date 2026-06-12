<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REPLIED = 'replied';

    public const STATUS_CLOSED = 'closed';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'sujet',
        'message',
        'status',
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

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ContactMessageReply::class, 'contact_message_id')->orderBy('created_at');
    }
}
