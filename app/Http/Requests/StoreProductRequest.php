<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'category_id' => ['required', 'integer', 'min:1', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:200',
                function (string $attribute, mixed $value, \Closure $fail) use ($productId): void {
                    $normalized = mb_strtolower(trim((string) $value));

                    if ($normalized === '') {
                        return;
                    }

                    $exists = Product::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->when($productId, fn ($query) => $query->where('id', '!=', $productId))
                        ->exists();

                    if ($exists) {
                        $fail('Un produit avec ce nom existe déjà.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'technical_specs' => ['nullable', 'array'],
            'technical_specs.*' => ['string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:512'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'stripe_product_id' => ['nullable', 'string', 'max:120'],
            'stripe_price_id_monthly' => ['nullable', 'string', 'max:120'],
            'stripe_price_id_yearly' => ['nullable', 'string', 'max:120'],
            'is_available' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'requires_shipping' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'featured_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.max' => 'Le nom du produit ne peut pas dépasser 200 caractères.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie sélectionnée est invalide.',
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

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
