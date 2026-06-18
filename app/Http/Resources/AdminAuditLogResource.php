<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admin_id' => $this->admin_id,
            'admin' => $this->whenLoaded('admin', fn () => [
                'id' => $this->admin?->id,
                'prenom' => $this->admin?->prenom,
                'nom' => $this->admin?->nom,
                'email' => $this->admin?->email,
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
