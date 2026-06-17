<?php

namespace App\Http\Requests;

use App\Rules\ValidPromoCode;
use Illuminate\Foundation\Http\FormRequest;

class ValidatePromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', new ValidPromoCode((float) $this->input('amount', 0))],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }
    }
}
