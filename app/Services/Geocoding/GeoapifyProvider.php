<?php

namespace App\Services\Geocoding;

use App\Contracts\GeocodingProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoapifyProvider implements GeocodingProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.geoapify.api_key', env('GEOAPIFY_API_KEY', ''));
        $this->baseUrl = 'https://api.geoapify.com/v1/geocode/search';
    }

    /**
     * Geocode an address string using Geoapify API.
     */
    public function geocode(string $address): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Geoapify API key is not configured.');
            return null;
        }

        try {
            $response = Http::withoutVerifying()->timeout(5)->get($this->baseUrl, [
                'text' => $address,
                'apiKey' => $this->apiKey,
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['features'][0]['properties'])) {
                    $properties = $data['features'][0]['properties'];

                    return [
                        'latitude' => (float) $properties['lat'],
                        'longitude' => (float) $properties['lon'],
                        'formatted_address' => $properties['formatted'] ?? $address,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Geoapify geocoding error: ' . $e->getMessage());
        }

        return null;
    }
}
