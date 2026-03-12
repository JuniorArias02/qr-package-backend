<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DailyBatch;
use App\Models\Package;
use App\Models\Driver;
use App\Models\Coordinator;
use Carbon\Carbon;

class DeliveryService
{
    /**
     * Create a new delivery record.
     */
    public function createDelivery(Package $package, Driver $driver, Coordinator $coordinator): Delivery
    {
        $batch = $this->getOrCreateDailyBatch();

        // Sequential order per batch
        $deliveryOrder = Delivery::where('daily_batch_id', $batch->id)->count() + 1;

        $delivery = Delivery::create([
            'package_id' => $package->id,
            'driver_id' => $driver->id,
            'coordinator_id' => $coordinator->id,
            'daily_batch_id' => $batch->id,
            'delivery_order' => $deliveryOrder,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // Standardized QR URL format as requested: /delivery/{delivery_id}
        $qrUrl = "/delivery/{$delivery->id}";

        $delivery->update(['qr_url' => $qrUrl]);

        // Update package status to assigned
        $package->update(['status' => 'assigned']);

        return $delivery->load(['package', 'driver.user']);
    }

    /**
     * Finds an available (active) driver for the given coordinator.
     * Logic: Returns the first active driver associated with the coordinator.
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
    protected function getOrCreateDailyBatch(): DailyBatch
    {
        $todayCode = Carbon::now()->format('Ymd');

        return DailyBatch::firstOrCreate(
            ['daily_code' => $todayCode],
            ['date' => Carbon::now()]
        );
    }
}
