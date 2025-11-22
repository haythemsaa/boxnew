<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'manager_name' => $this->manager_name,
            'total_boxes' => $this->total_boxes,
            'occupied_boxes' => $this->occupied_boxes,
            'available_boxes' => $this->available_boxes,
            'occupancy_rate' => $this->occupancy_rate,
            'status' => $this->status,
            'operating_hours' => $this->operating_hours,
            'amenities' => $this->amenities,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional relationships
            'boxes' => BoxResource::collection($this->whenLoaded('boxes')),
        ];
    }
}
