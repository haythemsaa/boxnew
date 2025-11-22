<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'customer_number' => $this->customer_number,
            'type' => $this->type,

            // Individual fields
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth?->toDateString(),

            // Company fields
            'company_name' => $this->company_name,
            'company_registration' => $this->company_registration,
            'vat_number' => $this->vat_number,

            // Contact information
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,

            // Address
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,

            // Billing information
            'billing_address' => $this->billing_address,
            'billing_city' => $this->billing_city,
            'billing_postal_code' => $this->billing_postal_code,
            'billing_country' => $this->billing_country,

            // Payment information
            'payment_method' => $this->payment_method,
            'bank_account' => $this->bank_account,
            'iban' => $this->iban,
            'payment_day' => $this->payment_day,

            // Status and notes
            'status' => $this->status,
            'credit_limit' => $this->credit_limit,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional relationships
            'contracts' => ContractResource::collection($this->whenLoaded('contracts')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
