<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'site_id' => $this->site_id,
            'box_id' => $this->box_id,
            'contract_number' => $this->contract_number,
            'status' => $this->status,

            // Dates
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'signed_at' => $this->signed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),

            // Pricing
            'monthly_price' => $this->monthly_price,
            'deposit_amount' => $this->deposit_amount,
            'deposit_status' => $this->deposit_status,
            'billing_frequency' => $this->billing_frequency,
            'billing_day' => $this->billing_day,
            'payment_method' => $this->payment_method,

            // Contract details
            'auto_renew' => $this->auto_renew,
            'renewal_period' => $this->renewal_period,
            'insurance_required' => $this->insurance_required,
            'insurance_amount' => $this->insurance_amount,
            'insurance_provider' => $this->insurance_provider,

            // Signing information
            'signed_by_customer' => $this->signed_by_customer,
            'signed_by_staff' => $this->signed_by_staff,
            'signature_ip' => $this->signature_ip,

            // Additional information
            'access_code' => $this->access_code,
            'special_conditions' => $this->special_conditions,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional relationships
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'site' => new SiteResource($this->whenLoaded('site')),
            'box' => new BoxResource($this->whenLoaded('box')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
        ];
    }
}
