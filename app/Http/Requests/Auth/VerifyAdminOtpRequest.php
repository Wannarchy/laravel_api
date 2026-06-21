<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAdminOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_token' => ['required', 'string', 'size:64'],
            'code' => ['required', 'string', 'regex:/^\d{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Le code doit contenir exactement 8 chiffres.',
        ];
    }
}
