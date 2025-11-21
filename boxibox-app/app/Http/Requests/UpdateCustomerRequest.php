<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_customers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'type' => ['required', 'in:individual,company'],
            'civility' => ['nullable', 'in:mr,mrs,ms,dr,prof'],
            'first_name' => ['required_if:type,individual', 'nullable', 'string', 'max:255'],
            'last_name' => ['required_if:type,individual', 'nullable', 'string', 'max:255'],
            'company_name' => ['required_if:type,company', 'nullable', 'string', 'max:255'],
            'siret' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'id_type' => ['nullable', 'in:passport,id_card,driver_license,residence_permit'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'id_issue_date' => ['nullable', 'date'],
            'id_expiry_date' => ['nullable', 'date', 'after:id_issue_date'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_country' => ['nullable', 'string', 'max:100'],
            'credit_score' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select a customer type.',
            'first_name.required_if' => 'First name is required for individual customers.',
            'last_name.required_if' => 'Last name is required for individual customers.',
            'company_name.required_if' => 'Company name is required for company customers.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already registered.',
            'id_expiry_date.after' => 'ID expiry date must be after issue date.',
        ];
    }
}
