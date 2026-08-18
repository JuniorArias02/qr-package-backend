<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DailyBatch;
use App\Models\Package;
use App\Models\Driver;
use App\Models\Coordinator;
use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public function __construct(
        protected AddressService $addressService,
        protected RouteMatchingService $routeMatchingService
    ) {}

    /**
     * Create a new delivery record supporting automatic geocoding, route matching, and nullable driver.
     */
    public function createDelivery(Package $package, ?Driver $driver, Coordinator $coordinator): Delivery
    {
        return DB::transaction(function () use ($package, $driver, $coordinator) {
            // 1. Process Address Geocoding & Coordinate Reuse
            if (!$package->address_id && $package->address) {
                $address = $this->addressService->findOrCreateAddress($package->address);
                $package->update(['address_id' => $address->id]);
            } else {
                $address = $package->address;
            }

            // 2. Spatial Route Matching
            $routeId = null;
            if ($address && $address->latitude !== null && $address->longitude !== null) {
                $matchedRoute = $this->routeMatchingService->findMatchingRoute(
                    $address->latitude,
                    $address->longitude,
                    $coordinator->company_id
                );
                if ($matchedRoute) {
                    $routeId = $matchedRoute->id;
                }
            }

            // 3. Batch and Sequential Order
            $batch = $this->getOrCreateDailyBatch($coordinator->company_id);
            $deliveryOrder = Delivery::where('daily_batch_id', $batch->id)->count() + 1;

            // 4. Resolve Status & Driver
            $isDriverAssigned = $driver !== null && $driver->active;
            $status = $isDriverAssigned ? DeliveryStatus::ASSIGNED->value : DeliveryStatus::PENDING_ASSIGNMENT->value;

            $delivery = Delivery::create([
                'company_id' => $coordinator->company_id,
                'package_id' => $package->id,
                'driver_id' => $isDriverAssigned ? $driver->id : null,
                'coordinator_id' => $coordinator->id,
                'daily_batch_id' => $batch->id,
                'route_id' => $routeId,
                'delivery_order' => $deliveryOrder,
                'status' => $status,
                'assigned_at' => $isDriverAssigned ? now() : null,
            ]);

            // 5. Generate QR Code URL
            $qrUrl = "/delivery/{$delivery->id}";
            $delivery->update(['qr_url' => $qrUrl]);

            // 6. Update Package Status
            $packageStatus = $isDriverAssigned ? 'assigned' : 'pending';
            $package->update(['status' => $packageStatus]);

            return $delivery->load(['package.address', 'driver.user', 'route']);
        });
    }

    /**
     * Finds an available (active) driver for the given coordinator.
     */
    public function getAvailableDriver(Coordinator $coordinator): ?Driver
    {
        return $coordinator->drivers()
            ->where('active', true)
            ->first();
    }

    /**
     * Get or create the batch for the current day.
     */
    protected function getOrCreateDailyBatch(?int $companyId = null): DailyBatch
    {
        $todayCode = Carbon::now()->format('Ymd');

        return DailyBatch::firstOrCreate(
            [
                'daily_code' => $todayCode,
                'company_id' => $companyId,
            ],
            ['date' => Carbon::now()]
        );
    }
}
