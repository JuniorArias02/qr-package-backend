<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\DriverAnalyticsService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DriverAnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(DriverAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    #[OA\Get(
        path: "/api/v1/driver/analytics/dashboard",
        summary: "Get full analytics data for driver dashboard",
        tags: ["Driver Analytics"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Analytics data retrieved successfully"),
            new OA\Response(response: 403, description: "User is not a driver")
        ]
    )]
    public function getDashboardData()
    {
        $user = auth('api')->user();
        if (!$user->driver) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Solo los conductores pueden acceder a estas métricas'
            ], 403);
        }

        $driverId = $user->driver->id;

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Métricas cargadas exitosamente',
            'objeto' => [
                'metrics' => $this->analyticsService->getDriverMetrics($driverId),
                'daily_progress' => $this->analyticsService->getDailyProgress($driverId),
                'weekly_activity' => $this->analyticsService->getWeeklyActivity($driverId)
            ]
        ]);
    }
}
