<?php

namespace App\Services\Geocoding;

use App\Contracts\GeocodingProviderInterface;
use Illuminate\Support\Facades\Log;

class FallbackGeocodingProvider implements GeocodingProviderInterface
{
    /**
     * @var GeocodingProviderInterface[]
     */
    protected array $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function geocode(string $address): ?array
    {
        foreach ($this->providers as $provider) {
            $result = $provider->geocode($address);
            if ($result !== null) {
                // Return as soon as one provider succeeds
                return $result;
            }
        }

        Log::error("All geocoding providers failed for address: {$address}");
        return null;
    }
}
