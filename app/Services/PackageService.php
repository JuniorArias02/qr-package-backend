<?php

namespace App\Services;

use App\Models\Package;

class PackageService
{
    /**
     * Find a package by its original reference.
     */
    public function findByReference(string $reference): ?Package
    {
        return Package::where('package_reference', $reference)->first();
    }

    /**
     * Update package status.
     */
    public function updateStatus(Package $package, string $status): bool
    {
        return $package->update(['status' => $status]);
    }
}
