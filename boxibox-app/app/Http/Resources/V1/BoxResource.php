<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoxResource extends JsonResource
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
            'site_id' => $this->site_id,
            'name' => $this->name,
            'code' => $this->code,
            'floor' => $this->floor,
            'zone' => $this->zone,
            'size_category' => $this->size_category,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'volume' => $this->volume,
            'base_price' => $this->base_price,
            'current_price' => $this->current_price,
            'status' => $this->status,
            'condition' => $this->condition,
            'features' => $this->features,
            'access_code' => $this->access_code,
            'last_inspection_date' => $this->last_inspection_date?->toDateString(),
            'next_inspection_date' => $this->next_inspection_date?->toDateString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional relationships
            'site' => new SiteResource($this->whenLoaded('site')),
            'current_contract' => new ContractResource($this->whenLoaded('currentContract')),
        ];
    }
}
