<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'email' => $this->email,
            'est_confirme' => (bool) $this->est_confirme,
            'is_admin' => (bool) $this->is_admin,
            'est_actif' => (bool) $this->est_actif,
            'bloquer' => $this->when($request->user()?->is_admin, (bool) $this->bloquer),
            'date_inscription' => $this->date_inscription,
            'derniere_connexion' => $this->derniere_connexion,
        ];
    }
}
