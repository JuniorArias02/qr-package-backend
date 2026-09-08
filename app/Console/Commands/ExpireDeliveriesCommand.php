<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Delivery;
use App\Enums\DeliveryStatus;
use App\Notifications\DriverReportedIssue;

class ExpireDeliveriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deliveries:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks deliveries as failed if they have been assigned for more than 24 hours without resolution';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting delivery expiration check...');

        // Find all deliveries that are 'assigned' and were assigned more than 24 hours ago
        $expiredDeliveries = Delivery::with(['coordinator.user', 'package'])
            ->where('status', DeliveryStatus::ASSIGNED->value)
            ->whereNotNull('assigned_at')
            ->where('assigned_at', '<=', Carbon::now()->subHours(24))
            ->get();

        if ($expiredDeliveries->isEmpty()) {
            $this->info('No expired deliveries found.');
            return Command::SUCCESS;
        }

        $count = 0;
        $observationMsg = 'Expirado automáticamente: Tiempo de entrega (24h) superado sin reporte del motorizado.';

        DB::beginTransaction();
        try {
            foreach ($expiredDeliveries as $delivery) {
                // Update delivery
                $delivery->update([
                    'status' => DeliveryStatus::FAILED->value,
                    'observation' => $observationMsg,
                ]);

                // Update package
                if ($delivery->package) {
                    $delivery->package->update(['status' => 'en_espera']); // Keeping it consistent with enums
                }

                // Notify coordinator (optional, but good practice based on existing flows)
                if ($delivery->coordinator && $delivery->coordinator->user) {
                    $delivery->coordinator->user->notify(new DriverReportedIssue($delivery));
                }

                $count++;
            }

            DB::commit();

            $this->info("Successfully expired {$count} deliveries.");
            Log::info("Deliveries Expiration Job: Expired {$count} deliveries automatically.");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Deliveries Expiration Job Error: ' . $e->getMessage());
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
