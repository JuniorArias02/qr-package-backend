<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Route;
use App\Models\Delivery;
use App\Models\DailyBatch;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PackageAssignmentService
{
    /**
     * Finds the route polygon containing the package and assigns it to the most optimal driver.
     */
    public function assignPackage(Package $package): bool
    {
        if (!$package->latitude || !$package->longitude) {
            Log::warning("Cannot assign package {$package->id}: no coordinates.");
            return false;
        }

        // 1. Find matching route using ST_Contains (Geoespacial de MySQL)
        // OJO: ST_Contains y Point() utilizan el orden (Longitud, Latitud) en GIS.
        $route = Route::where('active', true)
            ->where('branch_id', $package->branch_id)
            ->whereNotNull('polygon')
            ->whereRaw("ST_Contains(polygon, Point(?, ?))", [$package->longitude, $package->latitude])
            ->first();

        if (!$route) {
            Log::info("No matching route polygon found for package {$package->id}. Left as pending.");
            return false;
        }

        // 2. Find drivers assigned to this route
        $drivers = $route->drivers()->where('active', true)->get();
        if ($drivers->isEmpty()) {
            Log::warning("Route '{$route->name}' has no active drivers.");
            return false;
        }

        // 3. Find or Create active DailyBatch for today
        $dailyBatch = DailyBatch::firstOrCreate([
            'company_id' => $package->company_id,
            'date' => now()->toDateString(),
        ], [
            'daily_code' => now()->format('Ymd') . '-' . Str::upper(Str::random(4)),
        ]);

        // 4. Balanceo de Carga (Workload Balancing)
        $bestDriver = null;
        $minLoad = PHP_INT_MAX;

        foreach ($drivers as $driver) {
            $currentLoad = Delivery::where('driver_id', $driver->id)
                ->where('daily_batch_id', $dailyBatch->id)
                ->where('status', 'assigned')
                ->count();

            if ($currentLoad < $minLoad) {
                $minLoad = $currentLoad;
                $bestDriver = $driver;
            }
        }

        if (!$bestDriver) {
            return false; 
        }

        // 5. Create Delivery (Asignación)
        Delivery::create([
            'company_id' => $package->company_id,
            'package_id' => $package->id,
            'driver_id' => $bestDriver->id,
            'coordinator_id' => $bestDriver->coordinator_id,
            'daily_batch_id' => $dailyBatch->id,
            'delivery_order' => $minLoad + 1,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // Actualizamos estado original del paquete
        $package->status = 'assigned';
        $package->save();

        Log::info("Package {$package->id} assigned to driver {$bestDriver->id} via Route '{$route->name}' (Load: {$minLoad} -> ".($minLoad + 1).")");

        return true;
    }
}
