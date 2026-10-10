<?php

namespace App\Services;

use App\Contracts\RoutingProviderInterface;
use App\Models\Driver;
use App\Models\Package;
use App\Models\Delivery;
use Illuminate\Support\Facades\Log;

class IntelligentAssignmentService
{
    protected RoutingProviderInterface $routingProvider;
    protected RouteMatchingService $haversineService;

    // Weights for scoring
    protected float $weightDistance = 1.0; // Per kilometer
    protected float $weightTime = 1.0; // Per minute
    protected float $maxHaversineRadius = 3.0; // km

    public function __construct(
        RoutingProviderInterface $routingProvider,
        RouteMatchingService $haversineService
    ) {
        $this->routingProvider = $routingProvider;
        $this->haversineService = $haversineService;
    }

    /**
     * Find the best driver for the given package based on marginal route cost.
     */
    public function findBestDriver(Package $package, int $dailyBatchId, ?int $companyId = null): ?array
    {
        if (!$package->geocodedAddress || !$package->geocodedAddress->latitude || !$package->geocodedAddress->longitude) {
            return null;
        }

        $packageLat = $package->geocodedAddress->latitude;
        $packageLon = $package->geocodedAddress->longitude;

        // 1. Get all active drivers for this company and same branch as package
        $query = Driver::where('active', true)
            ->where('branch_id', $package->branch_id);
            
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $drivers = $query->get();

        if ($drivers->isEmpty()) {
            return null;
        }

        $candidates = [];

        // 2. Pre-filter drivers using Haversine distance
        foreach ($drivers as $driver) {
            $deliveries = Delivery::where('driver_id', $driver->id)
                ->where('daily_batch_id', $dailyBatchId)
                ->with('package.geocodedAddress')
                ->orderBy('delivery_order')
                ->get();

            if ($deliveries->isEmpty()) {
                // 0 packages: Perfect candidate, distance is 0.
                $candidates[] = [
                    'driver' => $driver,
                    'deliveries' => [],
                    'min_haversine' => 0,
                    'best_index' => 0
                ];
                continue;
            }

            // Find closest point in current route to the new package
            $minHaversine = PHP_FLOAT_MAX;
            $bestIndex = 0; // Where to insert
            $pointsCount = $deliveries->count();

            // Simple insertion heuristic based on Haversine
            for ($i = 0; $i <= $pointsCount; $i++) {
                $prevDel = $i > 0 ? $deliveries[$i - 1] : null;
                $nextDel = $i < $pointsCount ? $deliveries[$i] : null;

                $prevPoint = $prevDel && $prevDel->package->geocodedAddress && $prevDel->package->geocodedAddress->latitude ? $prevDel->package->geocodedAddress : null;
                $nextPoint = $nextDel && $nextDel->package->geocodedAddress && $nextDel->package->geocodedAddress->latitude ? $nextDel->package->geocodedAddress : null;

                $delta = 0;
                if ($prevPoint) {
                    $delta += $this->haversineService->calculateHaversineDistance(
                        $prevPoint->latitude, $prevPoint->longitude,
                        $packageLat, $packageLon
                    );
                }
                if ($nextPoint) {
                    $delta += $this->haversineService->calculateHaversineDistance(
                        $packageLat, $packageLon,
                        $nextPoint->latitude, $nextPoint->longitude
                    );
                }
                if ($prevPoint && $nextPoint) {
                    $delta -= $this->haversineService->calculateHaversineDistance(
                        $prevPoint->latitude, $prevPoint->longitude,
                        $nextPoint->latitude, $nextPoint->longitude
                    );
                }

                if ($delta < $minHaversine) {
                    $minHaversine = $delta;
                    $bestIndex = $i;
                }
            }

            // Only consider if it's reasonably close to the route
            if ($minHaversine <= $this->maxHaversineRadius) {
                $candidates[] = [
                    'driver' => $driver,
                    'deliveries' => $deliveries,
                    'min_haversine' => $minHaversine,
                    'best_index' => $bestIndex
                ];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Sort candidates by haversine heuristic to only call API for top 3
        usort($candidates, fn($a, $b) => $a['min_haversine'] <=> $b['min_haversine']);
        $topCandidates = array_slice($candidates, 0, 3);

        $bestScore = PHP_FLOAT_MAX;
        $winner = null;

        $newPackagePoint = [
            'lat' => $packageLat,
            'lon' => $packageLon,
            'id' => $package->id,
        ];

        // 3. Evaluate with Geoapify Routing API
        foreach ($topCandidates as $candidate) {
            $deliveries = $candidate['deliveries'];
            
            if (empty($deliveries)) {
                // If 0 deliveries, score is 0
                $score = 0;
                $increaseDist = 0;
                $increaseTime = 0;
                $newPoints = [$newPackagePoint];
            } else {
                // Build current route points
                $currentPoints = [];
                foreach ($deliveries as $del) {
                    if ($del->package->geocodedAddress && $del->package->geocodedAddress->latitude !== null) {
                        $currentPoints[] = [
                            'lat' => $del->package->geocodedAddress->latitude,
                            'lon' => $del->package->geocodedAddress->longitude,
                            'id' => $del->package_id,
                        ];
                    }
                }

                // Call API for current route
                $currentRoute = $this->routingProvider->calculateRoute($currentPoints);
                
                // Build new route points based on best_index
                $newPoints = $currentPoints;
                array_splice($newPoints, $candidate['best_index'], 0, [$newPackagePoint]);

                // Call API for new route
                $newRoute = $this->routingProvider->calculateRoute($newPoints);

                if (!$newRoute || !$currentRoute) {
                    continue; // Skip if API failed
                }

                $increaseDistMeters = max(0, $newRoute['distance_meters'] - $currentRoute['distance_meters']);
                $increaseTimeSeconds = max(0, $newRoute['time_seconds'] - $currentRoute['time_seconds']);

                $increaseDistKm = $increaseDistMeters / 1000;
                $increaseTimeMin = $increaseTimeSeconds / 60;

                $score = ($this->weightDistance * $increaseDistKm) + ($this->weightTime * $increaseTimeMin);
                $increaseDist = $increaseDistKm;
                $increaseTime = $increaseTimeMin;
            }

            if ($score < $bestScore) {
                $bestScore = $score;
                $winner = [
                    'driver' => $candidate['driver'],
                    'score' => $score,
                    'increase_distance_km' => $increaseDist,
                    'increase_time_min' => $increaseTime,
                    'new_order' => $newPoints, // Contains IDs to update delivery_order
                ];
            }
        }

        return $winner;
    }

    /**
     * Evaluate the marginal cost of assigning a package to a specific driver.
     */
    public function evaluateDriver(Package $package, Driver $driver, int $dailyBatchId): ?array
    {
        if (!$package->geocodedAddress || !$package->geocodedAddress->latitude || !$package->geocodedAddress->longitude) {
            return null;
        }

        $packageLat = $package->geocodedAddress->latitude;
        $packageLon = $package->geocodedAddress->longitude;

        $deliveries = Delivery::where('driver_id', $driver->id)
            ->where('daily_batch_id', $dailyBatchId)
            ->with('package.geocodedAddress')
            ->orderBy('delivery_order')
            ->get();

        $newPackagePoint = [
            'lat' => $packageLat,
            'lon' => $packageLon,
            'id' => $package->id,
        ];

        if ($deliveries->isEmpty()) {
            return [
                'increase_distance_km' => 0,
                'increase_time_min' => 0,
            ];
        }

        // Build current route points
        $currentPoints = [];
        foreach ($deliveries as $del) {
            if ($del->package->geocodedAddress && $del->package->geocodedAddress->latitude !== null) {
                $currentPoints[] = [
                    'lat' => $del->package->geocodedAddress->latitude,
                    'lon' => $del->package->geocodedAddress->longitude,
                    'id' => $del->package_id,
                ];
            }
        }

        // Call API for current route
        $currentRoute = $this->routingProvider->calculateRoute($currentPoints);
        
        // Build new route points (appending to the end for a simple evaluation)
        // Ideally we would find the best index, but for manual assignment we can just append
        $newPoints = $currentPoints;
        $newPoints[] = $newPackagePoint;

        // Call API for new route
        $newRoute = $this->routingProvider->calculateRoute($newPoints);

        if (!$newRoute || !$currentRoute) {
            return null;
        }

        $increaseDistMeters = max(0, $newRoute['distance_meters'] - $currentRoute['distance_meters']);
        $increaseTimeSeconds = max(0, $newRoute['time_seconds'] - $currentRoute['time_seconds']);

        return [
            'increase_distance_km' => $increaseDistMeters / 1000,
            'increase_time_min' => $increaseTimeSeconds / 60,
        ];
    }

    /**
     * Generate optimized routes for a batch of packages and drivers starting from a branch.
     * 
     * @param \App\Models\Branch $branch The branch (hub) starting point.
     * @param array $packages Array of App\Models\Package models.
     * @param array $drivers  Array of App\Models\Driver models.
     * @return array Array of optimized routes mapped to drivers.
     */
    public function generateOptimizedRoutes(\App\Models\Branch $branch, array $packages, array $drivers): array
    {
        if (empty($packages) || empty($drivers)) {
            return [];
        }

        if (!$branch->address || !$branch->address->latitude || !$branch->address->longitude) {
            throw new \Exception("Branch address must be geocoded to generate routes.");
        }

        $hubPoint = [
            'id' => 'hub_' . $branch->id,
            'lat' => $branch->address->latitude,
            'lon' => $branch->address->longitude,
        ];

        // 1. Prepare package points for clustering
        $packagePoints = [];
        foreach ($packages as $pkg) {
            if ($pkg->geocodedAddress && $pkg->geocodedAddress->latitude) {
                $packagePoints[] = [
                    'id' => $pkg->id,
                    'lat' => $pkg->geocodedAddress->latitude,
                    'lon' => $pkg->geocodedAddress->longitude,
                    'package' => $pkg
                ];
            }
        }

        if (empty($packagePoints)) {
            return [];
        }

        // 2. Cluster packages
        $clusteringService = new \App\Services\Routing\ClusteringService();
        $clusters = $clusteringService->clusterPackages($packagePoints, count($drivers));

        $tspOptimizer = new \App\Services\Routing\TspOptimizer();
        $results = [];

        // 3. Optimize each cluster and assign to driver
        foreach ($drivers as $index => $driver) {
            // It's possible some drivers don't get a cluster if there are fewer packages than drivers
            if (!isset($clusters[$index]) || empty($clusters[$index])) {
                continue;
            }

            $clusterPackages = $clusters[$index];
            
            // Prepare points for TSP: Hub + Packages
            $routePoints = [$hubPoint];
            foreach ($clusterPackages as $p) {
                $routePoints[] = $p;
            }

            // Apply TSP optimization
            $optimizedRoutePoints = $tspOptimizer->optimizeRoute($routePoints);
            
            // Remove the hub from the final sequence returned to be assigned as deliveries
            // We only need the ordered packages.
            $orderedPackages = [];
            foreach ($optimizedRoutePoints as $point) {
                if ($point['id'] !== $hubPoint['id'] && isset($point['package'])) {
                    $orderedPackages[] = $point['package'];
                }
            }

            $results[] = [
                'driver' => $driver,
                'ordered_packages' => $orderedPackages
            ];
        }

        return $results;
    }
}
