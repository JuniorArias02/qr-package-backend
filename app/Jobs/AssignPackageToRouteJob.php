<?php

namespace App\Jobs;

use App\Models\Package;
use App\Services\PackageAssignmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignPackageToRouteJob implements ShouldQueue
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
    public function handle(PackageAssignmentService $assignmentService): void
    {
        // Ejecuta el servicio que busca la ruta geoespacial y balancea
        $assignmentService->assignPackage($this->package);
    }
}
