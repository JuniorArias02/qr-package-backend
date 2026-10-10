<?php

namespace App\Services\Routing;

use App\Contracts\RoutingProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoapifyRoutingProvider implements RoutingProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.geoapify.api_key', env('GEOAPIFY_API_KEY', ''));
        // Using the standard Routing API
        $this->baseUrl = 'https://api.geoapify.com/v1/routing';
    }

    /**
     * Calculate the route and get the distance, and time.
     * 
     * @param array $points Array of points [['lat' => X, 'lon' => Y], ...]
     * @param string $mode Routing mode. Default 'drive'.
     */
    public function calculateRoute(array $points, string $mode = 'drive'): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Geoapify API key is not configured for Routing API.');
            return null;
        }

        if (count($points) < 2) {
            return [
                'distance_meters' => 0,
                'time_seconds' => 0,
                'optimized_waypoints' => $points
            ];
        }

        // Format waypoints: lat1,lon1|lat2,lon2|...
        $waypoints = array_map(function ($p) {
            return $p['lat'] . ',' . $p['lon'];
        }, $points);
        
        $waypointsStr = implode('|', $waypoints);

        try {
            $response = Http::withoutVerifying()->timeout(10)->get($this->baseUrl, [
                'waypoints' => $waypointsStr,
                'mode' => $mode,
                'apiKey' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['features'][0]['properties'])) {
                    $properties = $data['features'][0]['properties'];
                    
                    // The API returns 'distance' in meters and 'time' in seconds by default
                    return [
                        'distance_meters' => $properties['distance'] ?? 0,
                        'time_seconds' => $properties['time'] ?? 0,
                        'optimized_waypoints' => $points, // We return the same order, as we didn't use an external TSP endpoint
                    ];
                }
            } else {
                Log::error('Geoapify routing error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('Geoapify routing exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Use Geoapify Route Planner API to optimize points order (TSP).
     */
    public function optimizeRoute(array $points, string $mode = 'drive'): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Geoapify API key is not configured for Route Planner API.');
            return null;
        }

        if (count($points) <= 2) {
            return $points;
        }

        $agents = [
            [
                "mode" => $mode,
                "start" => [
                    "location" => [$points[0]['lon'], $points[0]['lat']]
                ]
            ]
        ];

        $jobs = [];
        // The first point is the start (hub), so we loop from index 1.
        for ($i = 1; $i < count($points); $i++) {
            $jobs[] = [
                "id" => (string)$points[$i]['id'],
                "location" => [$points[$i]['lon'], $points[$i]['lat']]
            ];
        }

        $payload = [
            "mode" => $mode,
            "agents" => $agents,
            "jobs" => $jobs
        ];

        try {
            $response = Http::withoutVerifying()->timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://api.geoapify.com/v1/routeplanner?apiKey={$this->apiKey}", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['features'][0]['properties']['waypoints'])) {
                    $waypoints = $data['features'][0]['properties']['waypoints'];
                    $optimizedOrder = [];
                    
                    // Route Planner API waypoints order contains the optimized sequence
                    foreach ($waypoints as $wp) {
                        // The start point in Geoapify doesn't have an ID, it comes as "start" or without original id.
                        // But jobs have original IDs.
                        if (isset($wp['original_location'])) {
                            // Find the point by comparing coords or using original job ID
                            if (isset($wp['job_id'])) {
                                foreach ($points as $p) {
                                    if ((string)$p['id'] === (string)$wp['job_id']) {
                                        $optimizedOrder[] = $p;
                                        break;
                                    }
                                }
                            } else {
                                // Start point
                                $optimizedOrder[] = $points[0];
                            }
                        }
                    }
                    
                    if (count($optimizedOrder) === count($points)) {
                        return $optimizedOrder;
                    }
                }
            } else {
                Log::error('Geoapify routeplanner error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('Geoapify routeplanner exception: ' . $e->getMessage());
        }

        return null;
    }
}
