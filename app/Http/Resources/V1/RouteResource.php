<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'active' => (bool) $this->active,
            'branch_id' => $this->branch_id,
            'polygon_coordinates' => $this->polygon_coordinates ?? [],
            'drivers' => DriverResource::collection($this->whenLoaded('drivers')),
        ];
    }
}
