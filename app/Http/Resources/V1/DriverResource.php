<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'active' => (bool) $this->active,
            // Datos del User relacionado (aplanados para facilitar su consumo en Frontend)
            'name' => $this->whenLoaded('user', fn() => $this->user->name),
            'email' => $this->whenLoaded('user', fn() => $this->user->email),
            'profile_image' => $this->whenLoaded('user', fn() => $this->user->profile_image),
        ];
    }
}
