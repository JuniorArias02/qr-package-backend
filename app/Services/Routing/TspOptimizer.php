<?php

namespace App\Services\Routing;

class TspOptimizer
{
    /**
     * Optimizes a route using Nearest Neighbor + 2-Opt.
     * The start point (hub/branch) is always fixed at index 0.
     * 
     * @param array $points Array of points: [['id' => 'hub', 'lat' => x, 'lon' => y], ['id' => 1, 'lat' => x, 'lon' => y], ...]
     * @return array Reordered points
     */
    public function optimizeRoute(array $points): array
    {
        if (count($points) <= 2) {
            return $points;
        }

        // 1. Initial route using Nearest Neighbor
        $route = $this->nearestNeighbor($points);

        // 2. Improve route using 2-Opt
        $optimizedRoute = $this->twoOpt($route);

        return $optimizedRoute;
    }

    private function nearestNeighbor(array $points): array
    {
        $route = [$points[0]]; // Start point is fixed
        $unvisited = array_slice($points, 1);

        $currentPoint = $route[0];

        while (!empty($unvisited)) {
            $nearestIndex = -1;
            $minDistance = PHP_FLOAT_MAX;

            foreach ($unvisited as $index => $point) {
                $distance = $this->haversineDistance(
                    $currentPoint['lat'], $currentPoint['lon'],
                    $point['lat'], $point['lon']
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            // Move the nearest point to the route
            $currentPoint = $unvisited[$nearestIndex];
            $route[] = $currentPoint;
            unset($unvisited[$nearestIndex]);
        }

        return $route;
    }

    private function twoOpt(array $route): array
    {
        $improvement = true;
        $numPoints = count($route);
        $maxIterations = 100; // prevent infinite loops
        $iteration = 0;

        while ($improvement && $iteration < $maxIterations) {
            $improvement = false;

            // i starts from 1 because index 0 is fixed (Hub)
            for ($i = 1; $i < $numPoints - 1; $i++) {
                for ($k = $i + 1; $k < $numPoints; $k++) {
                    $newRoute = $this->twoOptSwap($route, $i, $k);

                    $currentDist = $this->calculateTotalDistance($route);
                    $newDist = $this->calculateTotalDistance($newRoute);

                    if ($newDist < $currentDist) {
                        $route = $newRoute;
                        $improvement = true;
                    }
                }
            }
            $iteration++;
        }

        return $route;
    }

    private function twoOptSwap(array $route, int $i, int $k): array
    {
        $newRoute = array_slice($route, 0, $i);
        $reversedSegment = array_reverse(array_slice($route, $i, $k - $i + 1));
        $newRoute = array_merge($newRoute, $reversedSegment);
        $newRoute = array_merge($newRoute, array_slice($route, $k + 1));

        return $newRoute;
    }

    private function calculateTotalDistance(array $route): float
    {
        $total = 0;
        for ($i = 0; $i < count($route) - 1; $i++) {
            $total += $this->haversineDistance(
                $route[$i]['lat'], $route[$i]['lon'],
                $route[$i+1]['lat'], $route[$i+1]['lon']
            );
        }
        return $total;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
