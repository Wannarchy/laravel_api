<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor_type' => $this->actor_type,
            'admin_id' => $this->admin_id,
            'user_id' => $this->user_id,
            'admin' => $this->whenLoaded('admin', fn () => [
                'id' => $this->admin?->id,
                'prenom' => $this->admin?->prenom,
                'nom' => $this->admin?->nom,
                'email' => $this->admin?->email,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'prenom' => $this->user?->prenom,
                'nom' => $this->user?->nom,
                'email' => $this->user?->email,
                'bloquer' => (bool) $this->user?->bloquer,
            ]),
            'action' => $this->action,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'ip' => $this->ip,
            'details' => $this->details,
            'created_at' => $this->created_at,
        ];
    }
}
