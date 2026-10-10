<?php

namespace App\Services\Geocoding;

use App\Contracts\GeocodingProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NominatimProvider implements GeocodingProviderInterface
{
    public function geocode(string $address): ?array
    {
        try {
            // Nominatim requires a valid User-Agent
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => 'QRPackageApp/1.0 (junior.arias02yt@gmail.com)'
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $results = $response->json();
                if (!empty($results) && isset($results[0]['lat']) && isset($results[0]['lon'])) {
                    return [
                        'latitude' => (float) $results[0]['lat'],
                        'longitude' => (float) $results[0]['lon'],
                        'provider' => 'nominatim'
                    ];
                }
            } else {
                Log::warning('Nominatim geocoding error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Nominatim geocoding exception: ' . $e->getMessage());
        }

        return null;
    }
}
