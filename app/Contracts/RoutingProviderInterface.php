<?php

namespace App\Contracts;

interface RoutingProviderInterface
{
    /**
     * Calculate the route and get the optimized order, distance, and time.
     *
     * @param array $points Array of points, each containing 'lat', 'lon', and 'id' (optional identifier).
     * @param string $mode Routing mode (e.g., 'drive', 'walk'). Default is 'drive'.
     * @return array|null Returns an array with 'distance_meters', 'time_seconds', and 'optimized_waypoints' (array of points in optimal order).
     */
    public function calculateRoute(array $points, string $mode = 'drive'): ?array;
}
