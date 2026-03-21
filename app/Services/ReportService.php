<?php

namespace App\Services;

use App\Models\Delivery;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Get general metrics for a coordinator.
     */
    public function getMetrics(int $coordinatorId, array $filters = [])
    {
        $query = Delivery::where('coordinator_id', $coordinatorId);

        $this->applyFilters($query, $filters);

        $metrics = $query->selectRaw('
            COUNT(*) as total_deliveries,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as pending_count,
            AVG(CASE WHEN status = "delivered" AND delivered_at IS NOT NULL AND assigned_at IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) ELSE NULL END) as avg_delivery_time_minutes
        ')->first();

        // Deliveries with observations
        $withObservations = (clone $query)->whereNotNull('observation')->where('observation', '!=', '')->count();

        return [
            'total_deliveries' => (int) $metrics->total_deliveries,
            'delivered_count' => (int) $metrics->delivered_count,
            'pending_count' => (int) $metrics->pending_count,
            'delivery_rate' => $metrics->total_deliveries > 0 
                ? round(($metrics->delivered_count / $metrics->total_deliveries) * 100, 2) 
                : 0,
            'avg_delivery_time' => round($metrics->avg_delivery_time_minutes ?? 0, 2),
            'deliveries_with_observations' => $withObservations
        ];
    }

    /**
     * Get detailed delivery report.
     */
    public function getDeliveryReport(int $coordinatorId, array $filters = [])
    {
        $query = Delivery::with(['driver.user', 'dailyBatch', 'package'])
            ->where('coordinator_id', $coordinatorId);

        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get driver performance metrics.
     */
    public function getDriverPerformance(int $coordinatorId, array $filters = [])
    {
        $query = DB::table('deliveries')
            ->join('drivers', 'deliveries.driver_id', '=', 'drivers.id')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->where('deliveries.coordinator_id', $coordinatorId)
            ->select(
                'users.name as driver_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as pending'),
                DB::raw('AVG(CASE WHEN status = "delivered" AND delivered_at IS NOT NULL AND assigned_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) ELSE NULL END) as avg_time')
            )
            ->groupBy('drivers.id', 'users.name');

        if (isset($filters['start_date'])) {
            $query->where('deliveries.created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('deliveries.created_at', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    /**
     * Get deliveries grouped by day for charts.
     */
    public function getDeliveriesByDay(int $coordinatorId, int $days = 7)
    {
        return Delivery::where('coordinator_id', $coordinatorId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Apply common filters to query.
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if (isset($filters['daily_batch_id'])) {
            $query->where('daily_batch_id', $filters['daily_batch_id']);
        }
    }
}
