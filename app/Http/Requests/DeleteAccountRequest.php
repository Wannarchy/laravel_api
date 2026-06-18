<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:SUPPRIMER'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.in' => 'Veuillez saisir SUPPRIMER pour confirmer la suppression.',
        ];
    }
}
