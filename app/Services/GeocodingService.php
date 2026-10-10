<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.geoapify.com/v1/geocode/search';

    public function __construct()
    {
        $this->apiKey = config('services.geoapify.api_key');
    }

    /**
     * Get latitude and longitude from an address using Geoapify.
     *
     * @param string $address
     * @return array{latitude: float, longitude: float}|null
     */
    public function geocodeAddress(string $address): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('Geoapify API key is missing.');
            return null;
        }

        try {
            $response = Http::get($this->baseUrl, [
                'text' => $address,
                'apiKey' => $this->apiKey,
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['features']) && isset($data['features'][0]['geometry']['coordinates'])) {
                    $coordinates = $data['features'][0]['geometry']['coordinates'];
                    
                    // Geoapify (GeoJSON) returns coordinates in [longitude, latitude] format
                    return [
                        'longitude' => (float) $coordinates[0],
                        'latitude' => (float) $coordinates[1],
                    ];
                }
            }

            Log::warning("Geocoding failed for address: {$address}. Status: " . $response->status());
        } catch (\Exception $e) {
            Log::error("Exception during geocoding address: {$address}. Error: " . $e->getMessage());
        }

        return null;
    }
}
