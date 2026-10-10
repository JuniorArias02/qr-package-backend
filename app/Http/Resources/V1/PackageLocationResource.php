<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageLocationResource extends JsonResource
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
            'package_reference' => $this->package_reference,
            'recipient_name' => $this->recipient_name,
            'address' => $this->address,
            'status' => $this->status,
            'is_geocoded' => $this->relationLoaded('geocodedAddress') && $this->geocodedAddress !== null,
            'latitude' => $this->whenLoaded('geocodedAddress', fn() => $this->geocodedAddress?->latitude),
            'longitude' => $this->whenLoaded('geocodedAddress', fn() => $this->geocodedAddress?->longitude),
        ];
    }
}
