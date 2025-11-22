<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'payment_number' => $this->payment_number,
            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,

            // Dates
            'paid_at' => $this->paid_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),

            // Payment details
            'reference' => $this->reference,
            'transaction_id' => $this->transaction_id,
            'card_last_four' => $this->card_last_four,
            'card_brand' => $this->card_brand,

            // Banking information
            'bank_name' => $this->bank_name,
            'account_holder' => $this->account_holder,

            // Additional information
            'notes' => $this->notes,
            'processed_by' => $this->processed_by,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional relationships
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
        ];
    }
}
