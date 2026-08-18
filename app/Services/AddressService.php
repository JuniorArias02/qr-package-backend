<?php

namespace App\Services;

use App\Contracts\GeocodingProviderInterface;
use App\Models\Address;

class AddressService
{
    public function __construct(
        protected GeocodingProviderInterface $geocodingProvider
    ) {}

    /**
     * Find an existing address record with cached coordinates or geocode it.
     */
    public function findOrCreateAddress(string $rawAddress): Address
    {
        $cleanAddress = trim($rawAddress);
        $normalized = strtolower(preg_replace('/\s+/', ' ', $cleanAddress));

        $address = Address::where('normalized_address', $normalized)->first();

        // If address exists and already has valid geocoded coordinates, reuse it
        if ($address && $address->latitude !== null && $address->longitude !== null) {
            return $address;
        }

        // Call the geocoding provider (Geoapify or any injected provider)
        $geocoded = $this->geocodingProvider->geocode($cleanAddress);

        if (!$address) {
            $address = new Address();
            $address->full_address = $cleanAddress;
            $address->normalized_address = $normalized;
        }

        if ($geocoded) {
            $address->latitude = $geocoded['latitude'];
            $address->longitude = $geocoded['longitude'];
            $address->provider = 'geoapify';
            $address->geocoded_at = now();
        }

        $address->save();

        return $address;
    }
}
