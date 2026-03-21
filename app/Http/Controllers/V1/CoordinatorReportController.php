<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CoordinatorReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get the current coordinator id for the authenticated user.
     */
    private function getCoordinatorId()
    {
        $coordinator = Coordinator::where('user_id', auth('api')->id())->first();
        if (!$coordinator) {
            abort(403, 'User is not a coordinator.');
        }
        return $coordinator->id;
    }

    /**
     * Get KPI metrics.
     */
    public function metrics(Request $request)
    {
        $metrics = $this->reportService->getMetrics($this->getCoordinatorId(), $request->all());

        return response()->json([
            'status' => 'success',
            'objeto' => $metrics
        ]);
    }

    /**
     * Get detailed delivery report.
     */
    public function deliveries(Request $request)
    {
        $deliveries = $this->reportService->getDeliveryReport($this->getCoordinatorId(), $request->all());

        return response()->json([
            'status' => 'success',
            'objeto' => $deliveries
        ]);
    }

    /**
     * Get driver performance.
     */
    public function drivers(Request $request)
    {
        $drivers = $this->reportService->getDriverPerformance($this->getCoordinatorId(), $request->all());

        return response()->json([
            'status' => 'success',
            'objeto' => $drivers
        ]);
    }

    /**
     * Get chart data (deliveries per day).
     */
    public function chart(Request $request)
    {
        $days = $request->get('days', 7);
        $data = $this->reportService->getDeliveriesByDay($this->getCoordinatorId(), (int) $days);

        return response()->json([
            'status' => 'success',
            'objeto' => $data
        ]);
    }
}
