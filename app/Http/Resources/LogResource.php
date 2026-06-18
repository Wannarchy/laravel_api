<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor_type' => $this->actor_type,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'prenom' => $this->user?->prenom,
                'nom' => $this->user?->nom,
                'email' => $this->user?->email,
                'bloquer' => (bool) $this->user?->bloquer,
                'is_admin' => (bool) $this->user?->is_admin,
            ]),
            'action' => $this->action,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'ip' => $this->ip,
            'details' => ActivityLog::normalizeDetails($this->details),
            'created_at' => $this->created_at,
        ];
    }
}
