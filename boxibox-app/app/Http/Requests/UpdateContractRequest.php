<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update_contracts');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contractId = $this->route('contract');

        return [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'box_id' => ['required', 'integer', 'exists:boxes,id'],
            'contract_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('contracts', 'contract_number')->ignore($contractId),
            ],
            'status' => ['required', 'in:draft,pending_signature,active,expired,terminated,cancelled'],
            'type' => ['required', 'in:standard,short_term,long_term'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'actual_end_date' => ['nullable', 'date'],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'auto_renew' => ['required', 'boolean'],
            'renewal_period' => ['required', 'in:monthly,quarterly,yearly'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'deposit_paid' => ['required', 'boolean'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:30'], // Max 30% to prevent revenue loss
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'billing_frequency' => ['required', 'in:monthly,quarterly,yearly'],
            'billing_day' => ['required', 'integer', 'min:1', 'max:31'],
            'payment_method' => ['required', 'in:card,bank_transfer,cash,sepa'],
            'auto_pay' => ['required', 'boolean'],
            'access_code' => ['nullable', 'string', 'max:10'],
            'key_given' => ['required', 'boolean'],
            'key_returned' => ['required', 'boolean'],
            'signed_by_customer' => ['required', 'boolean'],
            'customer_signed_at' => ['nullable', 'date'],
            'signed_by_staff' => ['required', 'boolean'],
            'staff_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'termination_reason' => ['nullable', 'in:customer_request,non_payment,breach,end_of_term,other'],
            'termination_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'site_id' => 'site',
            'customer_id' => 'customer',
            'box_id' => 'box',
            'contract_number' => 'contract number',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'actual_end_date' => 'actual end date',
            'notice_period_days' => 'notice period',
            'monthly_price' => 'monthly price',
            'deposit_amount' => 'deposit amount',
            'billing_day' => 'billing day',
            'payment_method' => 'payment method',
            'staff_user_id' => 'staff member',
        ];
    }
}
