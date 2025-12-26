<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductSaleRequest extends FormRequest
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
            'customer_id' => ['required', 'exists:customers,id'],
            'contract_id' => ['nullable', 'exists:contracts,id'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'payment_method' => ['nullable', Rule::in(['cash', 'card', 'bank_transfer', 'stripe', 'other'])],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Items de la vente
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],

            // Remise globale
            'discount_amount' => ['nullable', 'numeric', 'min:0'],

            // Paiement immédiat
            'mark_as_paid' => ['boolean'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'client',
            'contract_id' => 'contrat',
            'site_id' => 'site',
            'payment_method' => 'méthode de paiement',
            'items' => 'produits',
            'items.*.product_id' => 'produit',
            'items.*.quantity' => 'quantité',
            'items.*.unit_price' => 'prix unitaire',
            'items.*.discount_amount' => 'remise',
            'discount_amount' => 'remise globale',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Veuillez ajouter au moins un produit à la vente.',
            'items.min' => 'Veuillez ajouter au moins un produit à la vente.',
            'items.*.product_id.required' => 'Veuillez sélectionner un produit.',
            'items.*.product_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'items.*.quantity.required' => 'La quantité est requise.',
            'items.*.quantity.min' => 'La quantité minimum est 1.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mark_as_paid' => $this->boolean('mark_as_paid'),
        ]);
    }
}
