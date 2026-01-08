<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get packages assigned to the authenticated user
        $packages = Package::where('user_id_asignado', Auth::id())->get();
        return response()->json($packages);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Check if a QR code exists.
     */
    public function checkQr(Request $request)
    {
        $request->validate(['referencia_qr' => 'required|string']);

        $package = Package::where('referencia_qr', $request->referencia_qr)->first();

        if ($package) {
            return response()->json([
                'exists' => true,
                'package' => $package
            ]);
        }

        return response()->json([
            'exists' => false
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'referencia_qr' => 'required|unique:packages',
            'direccion' => 'required',
            'nombre_receptor' => 'required',
            'telefono_receptor' => 'required',
        ]);

        $package = Package::create([
            'referencia_qr' => $validated['referencia_qr'],
            'direccion' => $validated['direccion'],
            'nombre_receptor' => $validated['nombre_receptor'],
            'telefono_receptor' => $validated['telefono_receptor'],
            'estado' => 'Recibido', // Default state
            'user_id_asignado' => Auth::id(), // Assign to current user
        ]);

        return response()->json($package, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $package = Package::findOrFail($id);
        return response()->json($package);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $package = Package::findOrFail($id);
        $package->update($request->all());
        return response()->json($package);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Package::destroy($id);
        return response()->json(null, 204);
    }
}
