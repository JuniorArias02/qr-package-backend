<?php

namespace App\Jobs;

use App\Models\Package;
use App\Services\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeocodePackageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $package;

    /**
     * Create a new job instance.
     */
    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    /**
     * Execute the job.
     */
    public function handle(GeocodingService $geocodingService): void
    {
        // Prevent double geocoding
        if ($this->package->is_geolocated) {
            return;
        }

        $addressText = $this->package->address;
        
        if (empty($addressText)) {
            Log::warning("Package {$this->package->id} has no address string to geocode.");
            return;
        }

        // Request coordinates from our Geoapify Service
        $coordinates = $geocodingService->geocodeAddress($addressText);

        if ($coordinates) {
            $this->package->latitude = $coordinates['latitude'];
            $this->package->longitude = $coordinates['longitude'];
            $this->package->is_geolocated = true;
            $this->package->save();

            Log::info("Package {$this->package->id} geocoded successfully.", $coordinates);
            
            // 🚀 Disparamos la asignación a ruta y conductor
            \App\Jobs\AssignPackageToRouteJob::dispatch($this->package);
            
        } else {
            Log::warning("Could not geocode package {$this->package->id}. API returned no results.");
        }
    }
}
