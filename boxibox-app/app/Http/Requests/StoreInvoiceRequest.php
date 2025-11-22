<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_invoices');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'invoice_number' => ['nullable', 'string', 'max:255', 'unique:invoices,invoice_number'],
            'type' => ['required', 'in:invoice,credit_note,proforma'],
            'status' => ['required', 'in:draft,sent,paid,partial,overdue,cancelled'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'paid_at' => ['nullable', 'date'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_recurring' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'contract_id' => 'contract',
            'invoice_number' => 'invoice number',
            'invoice_date' => 'invoice date',
            'due_date' => 'due date',
            'paid_at' => 'paid date',
            'period_start' => 'period start',
            'period_end' => 'period end',
            'items.*.description' => 'item description',
            'items.*.quantity' => 'item quantity',
            'items.*.unit_price' => 'item unit price',
            'items.*.total' => 'item total',
            'tax_rate' => 'tax rate',
            'tax_amount' => 'tax amount',
            'discount_amount' => 'discount amount',
            'paid_amount' => 'paid amount',
        ];
    }
}
