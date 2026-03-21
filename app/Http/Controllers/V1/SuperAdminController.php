<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Package;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    /**
     * Dashboard Global: Métricas del sistema completo.
     */
    public function getDashboardStats()
    {
        $today = Carbon::today();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Estadísticas globales recuperadas',
            'objeto' => [
                'total_companies'   => Company::count(),
                'active_companies'  => Company::where('active', true)->count(),
                'total_users'       => User::count(),
                'total_packages'    => Package::count(),
                'total_deliveries'  => Delivery::count(),
                'today_deliveries'  => Delivery::whereDate('created_at', $today)->count(),
                
                // Datos para gráficas
                'companies_status' => [
                    ['name' => 'Activas', 'value' => Company::where('active', true)->count()],
                    ['name' => 'Inactivas', 'value' => Company::where('active', false)->count()],
                ],
                'deliveries_last_7_days' => $this->getDeliveriesChartData()
            ]
        ]);
    }

    /**
     * Listado global de entregas con filtros.
     */
    public function getAllDeliveries(Request $request)
    {
        $query = Delivery::with(['package', 'driver.user', 'company', 'coordinator.user']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $deliveries = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Entregas globales recuperadas',
            'objeto' => $deliveries
        ]);
    }

    private function getDeliveriesChartData()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $data[] = [
                'day' => $date->format('d/m'),
                'count' => Delivery::whereDate('created_at', $date)->count()
            ];
        }
        return $data;
    }
}
