<?php

namespace App\Services;

use App\Models\Route;
use App\Models\RoutePoint;
use Illuminate\Support\Facades\DB;

class RouteService
{
    /**
     * Get routes for company.
     */
    public function getRoutes(?int $companyId = null)
    {
        $query = Route::with('points');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * Create a route with optional points.
     */
    public function createRoute(array $data, ?int $companyId = null): Route
    {
        return DB::transaction(function () use ($data, $companyId) {
            $route = Route::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'active' => $data['active'] ?? true,
            ]);

            if (!empty($data['points'])) {
                foreach ($data['points'] as $pointData) {
                    $route->points()->create([
                        'latitude' => $pointData['latitude'],
                        'longitude' => $pointData['longitude'],
                        'radius_km' => $pointData['radius_km'] ?? 2.00,
                    ]);
                }
            }

            return $route->load('points');
        });
    }

    /**
     * Add a geographic point to a route.
     */
    public function addPoint(Route $route, array $data): RoutePoint
    {
        return $route->points()->create([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius_km' => $data['radius_km'] ?? 2.00,
        ]);
    }
}
