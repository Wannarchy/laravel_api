<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'stripe_payment_intent' => ['nullable', 'string', 'max:120'],
            'card_last4' => ['nullable', 'string', 'size:4'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ];
    }
}
