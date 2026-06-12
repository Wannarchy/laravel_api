<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessageReply extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contact_message_id',
        'admin_id',
        'body',
        'mail_sent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'mail_sent' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function contactMessage(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class, 'contact_message_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
