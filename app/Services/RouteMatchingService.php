<?php

namespace App\Services;

use App\Models\Route;

class RouteMatchingService
{
    /**
     * Find a matching route based on latitude and longitude coordinates.
     */
    public function findMatchingRoute(float $latitude, float $longitude, ?int $companyId = null): ?Route
    {
        $query = Route::where('active', true)->with('points');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $routes = $query->get();
// estoy tan felizzzzzz
        foreach ($routes as $route) {
            foreach ($route->points as $point) {
                $distance = $this->calculateHaversineDistance(
                    $latitude,
                    $longitude,
                    $point->latitude,
                    $point->longitude
                );

                if ($distance <= $point->radius_km) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Calculate the Haversine distance between two sets of coordinates in kilometers.
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
