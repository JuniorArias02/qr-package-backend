<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AddressService;

class LocationController extends Controller
{
    /**
     * Geocodes an address string and returns latitude and longitude.
     */
    public function geocode(Request $request, AddressService $addressService)
    {
        $request->validate([
            'address' => 'required|string|min:5',
        ]);

        try {
            $address = $addressService->findOrCreateAddress($request->address);

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Dirección geocodificada correctamente',
                'objeto' => [
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'formatted_address' => $address->street
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'No se pudo geocodificar la dirección: ' . $e->getMessage()
            ], 422);
        }
    }
}
