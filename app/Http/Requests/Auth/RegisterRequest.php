<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:utilisateurs,email'],
            'password' => PasswordRules::required(),
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.mixed' => 'Le mot de passe doit contenir des majuscules et des minuscules.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.symbols' => 'Le mot de passe doit contenir au moins un caractère spécial.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
