<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'branch_id' => 'required|exists:branches,id',
            'polygon_coordinates' => 'required|array|min:3',
            'polygon_coordinates.*' => 'array|size:2',
            'polygon_coordinates.*.0' => 'required|numeric', // latitude
            'polygon_coordinates.*.1' => 'required|numeric', // longitude
        ];
    }
}
