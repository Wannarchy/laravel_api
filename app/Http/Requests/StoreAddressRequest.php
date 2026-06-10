<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:80'],
            'prenom' => ['required', 'string', 'max:80'],
            'nom' => ['required', 'string', 'max:80'],
            'adresse1' => ['required', 'string', 'max:200'],
            'adresse2' => ['nullable', 'string', 'max:200'],
            'ville' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'code_postal' => ['required', 'string', 'max:20'],
            'pays' => ['nullable', 'string', 'max:80'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
