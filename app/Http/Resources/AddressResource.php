<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'usage_type' => $this->usage_type,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'adresse1' => $this->adresse1,
            'adresse2' => $this->adresse2,
            'ville' => $this->ville,
            'region' => $this->region,
            'code_postal' => $this->code_postal,
            'pays' => $this->pays,
            'telephone' => $this->telephone,
            'is_default' => (bool) $this->is_default,
            'is_default_shipping' => (bool) $this->is_default_shipping,
            'created_at' => $this->created_at,
        ];
    }
}
