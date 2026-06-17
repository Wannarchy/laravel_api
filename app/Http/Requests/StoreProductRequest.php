<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'min:1', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:200'],
            'image_path' => ['nullable', 'string', 'max:512'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'stripe_product_id' => ['nullable', 'string', 'max:120'],
            'stripe_price_id_monthly' => ['nullable', 'string', 'max:120'],
            'stripe_price_id_yearly' => ['nullable', 'string', 'max:120'],
            'is_available' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'featured_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['is_available', 'is_featured'] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if (is_string($value)) {
                    $merge[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($value === '1');
                }
            }
        }

        if ($this->has('category_id') && $this->input('category_id') === '') {
            $merge['category_id'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
