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
        protected RouteMatchingService $routeMatchingService,
        protected IntelligentAssignmentService $intelligentAssignmentService
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
                $address = $package->geocodedAddress;
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

            return $delivery->load(['package.geocodedAddress', 'driver.user', 'route']);
        });
    }

    /**
     * Finds an available (active) driver for the given coordinator and package.
     */
    public function getAvailableDriver(Coordinator $coordinator, ?Package $package = null): ?Driver
    {
        if ($package) {
            $batch = $this->getOrCreateDailyBatch($coordinator->company_id);
            $bestDriverResult = $this->intelligentAssignmentService->findBestDriver($package, $batch->id, $coordinator->company_id);
            
            if ($bestDriverResult && isset($bestDriverResult['driver'])) {
                // Return the driver that minimizes marginal cost
                return $bestDriverResult['driver'];
            }
        }

        // Fallback to the first active driver if no package or intelligent routing fails
        $query = $coordinator->drivers()->where('active', true);
        if ($package) {
            $query->where('branch_id', $package->branch_id);
        }
        return $query->first();
    }

    /**
     * Evaluates the impact of manually assigning a package to a specific driver.
     */
    public function evaluateDriverImpact(Package $package, Driver $driver, Coordinator $coordinator): ?array
    {
        $batch = $this->getOrCreateDailyBatch($coordinator->company_id);
        return $this->intelligentAssignmentService->evaluateDriver($package, $driver, $batch->id);
    }

    /**
     * Get or create the batch for the current day.
     */
    protected function getOrCreateDailyBatch(?int $companyId = null, ?int $branchId = null): DailyBatch
    {
        // Add companyId and branchId to the daily_code to avoid unique constraint violations
        $todayCode = Carbon::now()->format('Ymd');
        if ($companyId) $todayCode .= "-C{$companyId}";
        if ($branchId) $todayCode .= "-B{$branchId}";
        
        $attributes = [
            'daily_code' => $todayCode,
            'company_id' => $companyId,
        ];
        
        if ($branchId !== null) {
            $attributes['branch_id'] = $branchId;
        }

        return DailyBatch::firstOrCreate(
            $attributes,
            ['date' => Carbon::now()]
        );
    }

    /**
     * Assign optimized routes from the Batch process to drivers.
     * Takes the results from IntelligentAssignmentService::generateOptimizedRoutes.
     */
    public function assignBatchRoutes(array $optimizedResults, Coordinator $coordinator, int $branchId): void
    {
        DB::transaction(function () use ($optimizedResults, $coordinator, $branchId) {
            $batch = $this->getOrCreateDailyBatch($coordinator->company_id, $branchId);
            
            foreach ($optimizedResults as $result) {
                $driver = $result['driver'];
                $packages = $result['ordered_packages'];
                
                // Clear out existing pending deliveries for this driver if we want to overwrite
                // For this implementation, we will append or create new.
                
                $order = 1;
                foreach ($packages as $package) {
                    
                    // Create Route object (Optional depending on how you structure Route table vs Delivery table)
                    // We'll just create the Delivery directly as before, but with the exact order
                    
                    $delivery = Delivery::updateOrCreate(
                        [
                            'package_id' => $package->id,
                            'daily_batch_id' => $batch->id,
                        ],
                        [
                            'company_id' => $coordinator->company_id,
                            'driver_id' => $driver->id,
                            'coordinator_id' => $coordinator->id,
                            'delivery_order' => $order,
                            'status' => DeliveryStatus::ASSIGNED->value,
                            'observation' => null,
                            'assigned_at' => now(),
                        ]
                    );

                    // Generate QR Code URL
                    if (empty($delivery->qr_url)) {
                        $qrUrl = "/delivery/{$delivery->id}";
                        $delivery->update(['qr_url' => $qrUrl]);
                    }

                    // Update Package Status
                    $package->update(['status' => 'assigned']);
                    
                    $order++;
                }
            }
        });
    }
}
