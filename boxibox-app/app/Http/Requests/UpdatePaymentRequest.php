<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update_payments');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $paymentId = $this->route('payment');

        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'payment_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('payments', 'payment_number')->ignore($paymentId),
            ],
            'type' => ['required', 'in:payment,refund,deposit'],
            'status' => ['required', 'in:pending,processing,completed,failed,refunded'],
            'amount' => ['required', 'numeric', 'min:0'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', 'in:card,bank_transfer,cash,cheque,sepa,stripe,paypal'],
            'gateway' => ['required', 'in:stripe,paypal,sepa,manual'],
            'gateway_payment_id' => ['nullable', 'string', 'max:255'],
            'gateway_customer_id' => ['nullable', 'string', 'max:255'],
            'gateway_response' => ['nullable', 'array'],
            'card_brand' => ['nullable', 'string', 'max:50'],
            'card_last_four' => ['nullable', 'string', 'size:4'],
            'paid_at' => ['nullable', 'date'],
            'processed_at' => ['nullable', 'date'],
            'failed_at' => ['nullable', 'date'],
            'refund_for_payment_id' => ['nullable', 'integer', 'exists:payments,id'],
            'refunded_amount' => ['nullable', 'numeric', 'min:0'],
            'failure_code' => ['nullable', 'string', 'max:100'],
            'failure_message' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'invoice_id' => 'invoice',
            'contract_id' => 'contract',
            'payment_number' => 'payment number',
            'gateway_payment_id' => 'gateway payment ID',
            'gateway_customer_id' => 'gateway customer ID',
            'card_last_four' => 'card last four digits',
            'paid_at' => 'paid date',
            'processed_at' => 'processed date',
            'failed_at' => 'failed date',
            'refund_for_payment_id' => 'original payment',
            'refunded_amount' => 'refunded amount',
            'failure_code' => 'failure code',
            'failure_message' => 'failure message',
        ];
    }
}
