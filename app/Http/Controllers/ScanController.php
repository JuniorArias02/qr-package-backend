<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanController extends Controller
{
    /**
     * Store a newly created scan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'datos_qr_leidos' => 'required',
            'correcto' => 'required|boolean',
            'motivo_error' => 'nullable',
            'ubicacion_gps' => 'nullable',
            'ip' => 'nullable',
        ]);

        $scan = Scan::create([
            'package_id' => $validated['package_id'],
            'user_id' => Auth::id(),
            'datos_qr_leidos' => $validated['datos_qr_leidos'],
            'correcto' => $validated['correcto'],
            'motivo_error' => $validated['motivo_error'] ?? null,
            'ubicacion_gps' => $validated['ubicacion_gps'] ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json($scan, 201);
    }

    /**
     * Display a listing of scans.
     */
    public function index()
    {
        $scans = Scan::where('user_id', Auth::id())->with('package')->get();
        return response()->json($scans);
    }
}
