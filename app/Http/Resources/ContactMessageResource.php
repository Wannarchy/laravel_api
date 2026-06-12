<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'email' => $this->email,
            'sujet' => $this->sujet,
            'message' => $this->message,
            'status' => $this->status ?? 'pending',
            'admin_reply' => $this->admin_reply,
            'replied_by' => $this->replied_by,
            'replied_at' => $this->replied_at,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'prenom' => $this->user->prenom,
                'nom' => $this->user->nom,
                'email' => $this->user->email,
            ]),
            'replier' => $this->whenLoaded('replier', fn () => [
                'id' => $this->replier->id,
                'prenom' => $this->replier->prenom,
                'nom' => $this->replier->nom,
                'email' => $this->replier->email,
            ]),
            'replies' => ContactMessageReplyResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->whenCounted('replies'),
        ];
    }
}
