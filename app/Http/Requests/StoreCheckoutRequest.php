<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billing_name' => ['required', 'string', 'max:200'],
            'billing_address' => ['required', 'string'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ];
    }
}
