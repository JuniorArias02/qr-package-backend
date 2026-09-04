<?php

namespace App\Services\Routing;

use Illuminate\Support\Collection;

class ClusteringService
{
    /**
     * Group packages into $k clusters using a geographically aware K-Means algorithm.
     * 
     * @param array $packages Array of packages with their coordinates: [['id' => 1, 'lat' => x, 'lon' => y], ...]
     * @param int $k Number of drivers/clusters
     * @return array Array of clusters, where each cluster is an array of package arrays.
     */
    public function clusterPackages(array $packages, int $k): array
    {
        if (count($packages) <= $k) {
            // If we have fewer or equal packages than drivers, just assign one per driver
            $clusters = [];
            foreach ($packages as $i => $package) {
                $clusters[$i % $k][] = $package;
            }
            return array_values($clusters);
        }

        // 1. Initialize centroids randomly (or smartly using K-Means++)
        $centroids = $this->initializeCentroidsKMeansPlusPlus($packages, $k);

        $clusters = [];
        $maxIterations = 50;
        $iteration = 0;
        $hasChanged = true;

        while ($hasChanged && $iteration < $maxIterations) {
            $clusters = array_fill(0, $k, []);
            $hasChanged = false;

            // 2. Assign packages to nearest centroid
            foreach ($packages as $package) {
                $minDistance = PHP_FLOAT_MAX;
                $closestCentroidIndex = 0;

                foreach ($centroids as $index => $centroid) {
                    $distance = $this->haversineDistance(
                        $package['lat'], $package['lon'],
                        $centroid['lat'], $centroid['lon']
                    );

                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $closestCentroidIndex = $index;
                    }
                }

                $clusters[$closestCentroidIndex][] = $package;
            }

            // 3. Recalculate centroids
            foreach ($clusters as $index => $clusterPackages) {
                if (empty($clusterPackages)) {
                    // Handle empty cluster by reinitializing it to a random package
                    $randomPackage = $packages[array_rand($packages)];
                    $centroids[$index] = [
                        'lat' => $randomPackage['lat'],
                        'lon' => $randomPackage['lon']
                    ];
                    $hasChanged = true;
                    continue;
                }

                $sumLat = 0;
                $sumLon = 0;
                foreach ($clusterPackages as $p) {
                    $sumLat += $p['lat'];
                    $sumLon += $p['lon'];
                }
                
                $newLat = $sumLat / count($clusterPackages);
                $newLon = $sumLon / count($clusterPackages);

                if (abs($centroids[$index]['lat'] - $newLat) > 0.0001 || 
                    abs($centroids[$index]['lon'] - $newLon) > 0.0001) {
                    $centroids[$index] = ['lat' => $newLat, 'lon' => $newLon];
                    $hasChanged = true;
                }
            }

            $iteration++;
        }

        // Optional: Re-balance clusters if they are highly disproportionate
        // This is a simple spatial clustering, so sizes might vary based on density.
        // We prioritize continuous zones over strict balance.

        return array_values(array_filter($clusters)); // Remove empty clusters just in case
    }

    /**
     * K-Means++ initialization to spread out the initial centroids.
     */
    private function initializeCentroidsKMeansPlusPlus(array $packages, int $k): array
    {
        $centroids = [];
        // First centroid is random
        $centroids[] = $packages[array_rand($packages)];

        for ($i = 1; $i < $k; $i++) {
            $distances = [];
            $totalDistance = 0;

            foreach ($packages as $packageIndex => $package) {
                $minDistToCentroid = PHP_FLOAT_MAX;
                foreach ($centroids as $centroid) {
                    $dist = $this->haversineDistance($package['lat'], $package['lon'], $centroid['lat'], $centroid['lon']);
                    if ($dist < $minDistToCentroid) {
                        $minDistToCentroid = $dist;
                    }
                }
                // We use squared distance for K-Means++
                $distances[$packageIndex] = $minDistToCentroid ** 2;
                $totalDistance += $distances[$packageIndex];
            }

            if ($totalDistance == 0) {
                $centroids[] = $packages[array_rand($packages)];
                continue;
            }

            // Roulette wheel selection
            $rand = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax() * $totalDistance;
            $runningSum = 0;
            foreach ($distances as $packageIndex => $dist) {
                $runningSum += $dist;
                if ($runningSum >= $rand) {
                    $centroids[] = $packages[$packageIndex];
                    break;
                }
            }
        }

        return $centroids;
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
