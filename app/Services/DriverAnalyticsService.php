<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Driver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DriverAnalyticsService
{
    /**
     * Get main KPIs for a driver.
     */
    public function getDriverMetrics(int $driverId): array
    {
        $stats = Delivery::where('driver_id', $driverId)
            ->selectRaw('
                COUNT(*) as total_assigned,
                COUNT(CASE WHEN status = "delivered" THEN 1 END) as total_delivered,
                COUNT(CASE WHEN status = "assigned" THEN 1 END) as total_pending,
                AVG(CASE WHEN status = "delivered" THEN TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at) END) as avg_delivery_time
            ')
            ->first();

        $totalAssigned = $stats->total_assigned ?: 0;
        $totalDelivered = $stats->total_delivered ?: 0;
        
        $efficiency = $totalAssigned > 0 
            ? round(($totalDelivered / $totalAssigned) * 100, 1) 
            : 0;

        $lastDelivery = Delivery::where('driver_id', $driverId)
            ->where('status', 'delivered')
            ->with('package')
            ->orderBy('delivered_at', 'desc')
            ->first();

        return [
            'total_assigned' => $totalAssigned,
            'total_delivered' => $totalDelivered,
            'total_pending' => $stats->total_pending ?: 0,
            'efficiency' => $efficiency,
            'avg_delivery_time' => round($stats->avg_delivery_time ?: 0),
            'last_delivery' => $lastDelivery ? [
                'reference' => $lastDelivery->package->package_reference,
                'delivered_at' => $lastDelivery->delivered_at->format('Y-m-d H:i:s'),
                'address' => $lastDelivery->package->address
            ] : null
        ];
    }

    /**
     * Get progress for the current day.
     */
    public function getDailyProgress(int $driverId): array
    {
        $today = Carbon::today();

        $stats = Delivery::where('driver_id', $driverId)
            ->whereDate('created_at', $today)
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = "delivered" THEN 1 END) as completed
            ')
            ->first();

        $total = $stats->total ?: 0;
        $completed = $stats->completed ?: 0;
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $percentage
        ];
    }

    /**
     * Get activity for the last 7 days.
     */
    public function getWeeklyActivity(int $driverId): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[$date->format('Y-m-d')] = [
                'date' => $date->format('d/m'),
                'full_date' => $date->format('Y-m-d'),
                'delivered' => 0
            ];
        }

        $activity = Delivery::where('driver_id', $driverId)
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('DATE(delivered_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        foreach ($activity as $item) {
            if (isset($days[$item->date])) {
                $days[$item->date]['delivered'] = $item->count;
            }
        }

        return array_values($days);
    }
}
