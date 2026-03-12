<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class CoordinatorController extends Controller
{


    #[OA\Get(
        path: "/api/v1/coordinator/stats",
        summary: "Get stats for all drivers",
        tags: ["Coordinator"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Stats retrieved")
        ]
    )]
    public function getStats()
    {
        $coordinator = auth('api')->user()->coordinator;
        $drivers = $coordinator->drivers()->with('user')->withCount('deliveries')->get();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Stats retrieved successfully',
            'objeto' => [
                'total_drivers' => $drivers->count(),
                'assigned_today' => \App\Models\Delivery::where('coordinator_id', $coordinator->id)
                    ->whereDate('created_at', now())
                    ->count(),
                'driver_performance' => $drivers->map(function ($d) {
                    return [
                        'name' => $d->user->name,
                        'count' => $d->deliveries_count
                    ];
                })
            ]
        ]);
    }
}
