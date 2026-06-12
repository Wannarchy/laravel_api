<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_message_id' => $this->contact_message_id,
            'admin_id' => $this->admin_id,
            'body' => $this->body,
            'mail_sent' => (bool) $this->mail_sent,
            'created_at' => $this->created_at,
        ];
    }
}
