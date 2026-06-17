<?php

namespace App\Http\Requests;

use App\Models\Product;
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
            'shipping_name' => ['nullable', 'string', 'max:200'],
            'shipping_address' => ['nullable', 'string'],
            'payment_method' => ['required', 'string'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->requiresShippingAddress()) {
                return;
            }

            if (! $this->filled('shipping_name') || ! $this->filled('shipping_address')) {
                $validator->errors()->add(
                    'shipping_address',
                    'Une adresse de livraison est requise pour les produits physiques.'
                );
            }
        });
    }

    private function requiresShippingAddress(): bool
    {
        foreach ($this->input('items', []) as $item) {
            $product = Product::find($item['product_id'] ?? 0);

            if ($product && $product->requires_shipping) {
                return true;
            }
        }

        return false;
    }
}
