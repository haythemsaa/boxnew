<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractAddonRequest extends FormRequest
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
        return [
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'billing_period' => ['nullable', Rule::in(array_keys(Product::BILLING_PERIODS))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'produit/service',
            'quantity' => 'quantité',
            'unit_price' => 'prix unitaire',
            'tax_rate' => 'taux de TVA',
            'billing_period' => 'période de facturation',
            'start_date' => 'date de début',
            'end_date' => 'date de fin',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Veuillez sélectionner un produit ou service.',
            'product_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'end_date.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}
