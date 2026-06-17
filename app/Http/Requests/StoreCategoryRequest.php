<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:150'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($categoryId): void {
                    $sortOrder = (int) $value;

                    $exists = Category::query()
                        ->where('sort_order', $sortOrder)
                        ->when($categoryId, fn ($query) => $query->where('id', '!=', $categoryId))
                        ->exists();

                    if ($exists) {
                        $fail('Cet ordre d\'affichage est déjà utilisé par une autre catégorie.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'sort_order.required' => 'L\'ordre d\'affichage est obligatoire.',
            'sort_order.min' => 'L\'ordre d\'affichage doit être au minimum 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $value = $this->input('is_active');
            if (is_string($value)) {
                $this->merge([
                    'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($value === '1'),
                ]);
            }
        }
    }
}
