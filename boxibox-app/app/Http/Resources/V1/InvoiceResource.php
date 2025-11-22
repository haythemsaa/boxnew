<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'contract_id' => $this->contract_id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,

            // Dates
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at?->toISOString(),

            // Amounts
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'balance' => $this->balance,

            // Payment information
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,

            // Invoice details
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'items' => $this->items,
            'notes' => $this->notes,

            // Reminder tracking
            'reminder_count' => $this->reminder_count,
            'last_reminder_sent' => $this->last_reminder_sent?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional relationships
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'contract' => new ContractResource($this->whenLoaded('contract')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
