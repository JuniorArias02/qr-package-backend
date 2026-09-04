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
}
