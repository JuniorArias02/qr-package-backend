<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PackageController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function statistics(Request $request)
    {
        $userId = Auth::id();

        // Total scans (from scans table)
        $totalScans = Scan::where('user_id', $userId)->count();

        // Active packages (not delivered)
        $activePackages = Package::where('user_id_asignado', $userId)
            ->whereNotIn('estado', ['Entregado'])
            ->count();

        // This week scans
        $weekStart = Carbon::now()->startOfWeek();
        $weekScans = Scan::where('user_id', $userId)
            ->where('created_at', '>=', $weekStart)
            ->count();

        // Completed packages
        $completedPackages = Package::where('user_id_asignado', $userId)
            ->where('estado', 'Entregado')
            ->count();

        // Recent activity (last 10 packages)
        $recentActivity = Package::where('user_id_asignado', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'package' => $package->referencia_qr,
                    'action' => $this->getActionFromStatus($package->estado),
                    'time' => $package->created_at, // Send raw UTC timestamp
                    'status' => $this->getStatusType($package->estado),
                ];
            });

        // Monthly statistics
        $monthStart = Carbon::now()->startOfMonth();
        $monthlyScans = Scan::where('user_id', $userId)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $monthlyCompleted = Package::where('user_id_asignado', $userId)
            ->where('estado', 'Entregado')
            ->where('updated_at', '>=', $monthStart)
            ->count();

        return response()->json([
            'stats' => [
                'total_scans' => $totalScans,
                'active_packages' => $activePackages,
                'week_scans' => $weekScans,
                'completed_packages' => $completedPackages,
            ],
            'recent_activity' => $recentActivity,
            'monthly' => [
                'scans' => $monthlyScans,
                'completed' => $monthlyCompleted,
            ]
        ]);
    }

    /**
     * Helper to get action text from status
     */
    private function getActionFromStatus($estado)
    {
        $actions = [
            'Recibido' => 'Escaneado',
            'En tránsito' => 'En tránsito',
            'Entregado' => 'Entregado',
            'Pendiente' => 'Pendiente',
        ];

        return $actions[$estado] ?? 'Procesado';
    }

    /**
     * Helper to get status type
     */
    private function getStatusType($estado)
    {
        return in_array($estado, ['Entregado']) ? 'success' : 'pending';
    }

    /**
     * Helper to get relative time
     */
    private function getRelativeTime($datetime)
    {
        $carbon = Carbon::parse($datetime);
        $now = Carbon::now();

        $diffInMinutes = $now->diffInMinutes($carbon);
        $diffInHours = $now->diffInHours($carbon);
        $diffInDays = $now->diffInDays($carbon);

        if ($diffInMinutes < 60) {
            return "Hace " . $diffInMinutes . " minuto" . ($diffInMinutes != 1 ? 's' : '');
        } elseif ($diffInHours < 24) {
            return "Hace " . $diffInHours . " hora" . ($diffInHours != 1 ? 's' : '');
        } elseif ($diffInDays < 7) {
            return "Hace " . $diffInDays . " día" . ($diffInDays != 1 ? 's' : '');
        } else {
            return $carbon->format('d/m/Y');
        }
    }

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
