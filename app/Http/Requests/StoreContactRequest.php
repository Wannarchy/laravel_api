<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'sujet' => ['required', 'string', 'min:3', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
