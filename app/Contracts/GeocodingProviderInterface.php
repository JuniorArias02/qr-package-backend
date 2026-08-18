<?php

namespace App\Contracts;

interface GeocodingProviderInterface
{
    /**
     * Geocode an address string to obtain geographic coordinates.
     *
     * @param string $address
     * @return array{latitude: float, longitude: float, formatted_address: string}|null
     */
    public function geocode(string $address): ?array;
}
