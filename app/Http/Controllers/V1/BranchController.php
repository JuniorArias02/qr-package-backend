<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Http\Resources\V1\BranchResource;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Get a list of branches (sedes).
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        
        $query = Branch::query();
        
        // If the user belongs to a specific company, filter by it
        if ($user && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $branches = $query->select('id', 'name', 'latitude', 'longitude')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Sedes obtenidas correctamente',
            'data' => BranchResource::collection($branches)
        ]);
    }

    /**
     * Create a new branch.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = auth('api')->user();
        if (!$user->company_id && !in_array($user->role, ['super_admin'])) {
            return response()->json(['status' => 'error', 'mensaje' => 'No company assigned to user'], 403);
        }

        $addressService = app(\App\Services\AddressService::class);
        $address = $addressService->findOrCreateAddress($request->address);

        $branch = Branch::create([
            'company_id' => $request->company_id ?? $user->company_id,
            'name' => $request->name,
            'address_id' => $address->id,
            'latitude' => $request->latitude ?? $address->latitude,
            'longitude' => $request->longitude ?? $address->longitude,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Sede creada correctamente',
            'data' => new BranchResource($branch->load('address'))
        ], 201);
    }

    /**
     * Update an existing branch.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $branch = Branch::findOrFail($id);

        // Security check
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $branch->company_id !== $user->company_id) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized'], 403);
        }

        if ($request->has('address')) {
            $addressService = app(\App\Services\AddressService::class);
            $address = $addressService->findOrCreateAddress($request->address);
            $branch->address_id = $address->id;
            
            // Si mandan nueva dirección pero no coordenadas, tratamos de actualizarlas con la nueva geocodificación
            if (!$request->has('latitude')) {
                $branch->latitude = $address->latitude;
            }
            if (!$request->has('longitude')) {
                $branch->longitude = $address->longitude;
            }
        }

        if ($request->has('name')) {
            $branch->name = $request->name;
        }
        
        if ($request->has('latitude')) {
            $branch->latitude = $request->latitude;
        }
        
        if ($request->has('longitude')) {
            $branch->longitude = $request->longitude;
        }

        $branch->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Sede actualizada correctamente',
            'data' => new BranchResource($branch->load('address'))
        ]);
    }

    /**
     * Delete a branch.
     */
    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Security check
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $branch->company_id !== $user->company_id) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized'], 403);
        }

        $branch->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sede eliminada correctamente',
        ]);
    }
}
