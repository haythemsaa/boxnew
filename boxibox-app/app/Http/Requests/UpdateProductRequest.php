<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product')->id ?? $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')->where(function ($query) {
                    return $query->where('tenant_id', $this->user()->tenant_id);
                })->ignore($productId),
            ],
            'type' => ['required', Rule::in(array_keys(Product::TYPES))],
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'billing_period' => [
                'nullable',
                'required_if:type,recurring',
                Rule::in(array_keys(Product::BILLING_PERIODS)),
            ],
            'unit' => ['nullable', Rule::in(array_keys(Product::UNITS))],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1', 'gte:min_quantity'],
            'track_inventory' => ['boolean'],
            'requires_contract' => ['boolean'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom du produit',
            'sku' => 'référence (SKU)',
            'type' => 'type de produit',
            'category' => 'catégorie',
            'price' => 'prix',
            'cost_price' => 'prix d\'achat',
            'tax_rate' => 'taux de TVA',
            'billing_period' => 'période de facturation',
            'stock_quantity' => 'quantité en stock',
            'min_quantity' => 'quantité minimum',
            'max_quantity' => 'quantité maximum',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'Cette référence (SKU) existe déjà.',
            'billing_period.required_if' => 'La période de facturation est requise pour les produits récurrents.',
            'max_quantity.gte' => 'La quantité maximum doit être supérieure ou égale à la quantité minimum.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'track_inventory' => $this->boolean('track_inventory'),
            'requires_contract' => $this->boolean('requires_contract'),
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
