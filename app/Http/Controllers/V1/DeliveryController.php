<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\DeliveryService;
use App\Services\PackageService;
use App\Services\SystemSettingService;
use App\Models\Driver;
use App\Models\Package;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DeliveryController extends Controller
{
    protected $deliveryService;
    protected $packageService;
    protected $settingService;
    protected $intelligentAssignmentService;

    public function __construct(
        DeliveryService $deliveryService,
        PackageService $packageService,
        SystemSettingService $settingService,
        \App\Services\IntelligentAssignmentService $intelligentAssignmentService
    ) {
        $this->deliveryService = $deliveryService;
        $this->packageService = $packageService;
        $this->settingService = $settingService;
        $this->intelligentAssignmentService = $intelligentAssignmentService;
    }

    #[OA\Post(
        path: "/api/v1/delivery/scan",
        summary: "Identify package via QR scan and handle automatic/manual assignment",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "package_reference", type: "string", example: "PKG-12345"),
                    new OA\Property(property: "driver_id", type: "integer", example: 1, description: "Optional for manual assignment from frontend")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Package identified and potentially assigned"),
            new OA\Response(response: 404, description: "Package not found"),
            new OA\Response(response: 422, description: "Package already assigned or no drivers available")
        ]
    )]
    public function scanPackage(Request $request)
    {
        $request->validate([
            'package_reference' => 'required|string',
            'driver_id' => 'nullable|exists:drivers,id'
        ]);

        $package = $this->packageService->findByReference($request->package_reference);
        $mode = $this->settingService->getAssignmentMode();
        $coordinator = auth('api')->user()->coordinator;

        if (!$coordinator) {
            return response()->json(['status' => 'error', 'mensaje' => 'Only coordinators can scan packages', 'objeto' => null], 403);
        }

        $validationResponse = $this->validatePackageForScan($package, $mode);
        if ($validationResponse) return $validationResponse;

        $driverResult = $this->resolveDriverForScan($request, $mode, $coordinator, $package);

        if ($driverResult === null) {
            return $this->driverSelectionResponse($package, $mode, $coordinator);
        }

        if (is_array($driverResult) && isset($driverResult['error'])) {
            return response()->json([
                'status' => 'error',
                'mensaje' => $driverResult['error'],
                'objeto' => ['package' => $package, 'assignment_mode' => $mode]
            ], 422);
        }

        return $this->processPackageAssignment($package, $driverResult, $coordinator, $mode);
    }

    private function validatePackageForScan($package, $mode)
    {
        if (!$package) {
            return response()->json([
                'status' => 'success',
                'mensaje' => 'Package not found',
                'objeto' => ['package' => null, 'assignment_mode' => $mode]
            ]);
        }

        if ($package->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'mensaje' => "Package is already {$package->status}",
                'objeto' => $package
            ], 422);
        }

        return null; // Validado correctamente
    }

    private function resolveDriverForScan(Request $request, $mode, $coordinator, $package = null)
    {
        if ($mode === 'automatic') {
            $driver = $this->deliveryService->getAvailableDriver($coordinator, $package);
            return $driver ?: ['error' => 'No active drivers available for automatic assignment'];
        }

        // Modo manual, la app frontend podría haber enviado el identificador del delivery (driver_id)
        if ($request->filled('driver_id')) {
            $driver = Driver::find($request->driver_id);
            if (!$driver || $driver->coordinator_id !== $coordinator->id || !$driver->active) {
                return ['error' => 'Invalid or inactive driver selected'];
            }
            return $driver;
        }

        return null; // Indica que se necesita que el UI muestre y seleccione un conductor
    }

    private function driverSelectionResponse($package, $mode, $coordinator)
    {
        $drivers = $coordinator->drivers()->where('active', true)->with('user')->get();
        return response()->json([
            'status' => 'success',
            'mensaje' => 'Package identified. Please select a driver.',
            'objeto' => [
                'package' => $package,
                'assignment_mode' => $mode,
                'available_drivers' => $drivers
            ]
        ]);
    }

    private function processPackageAssignment($package, $driver, $coordinator, $mode)
    {
        $delivery = $this->deliveryService->createDelivery($package, $driver, $coordinator);

        $dayIdentifier = now()->format('Ymd');
        $orderNumber = $driver ? $this->calculateDriverOrderNumber($driver->id) : 0;
        
        $trackingUrl = $this->generateTrackingUrl($package->package_reference, $delivery->id);
        
        $labelImageBase64 = $this->generateCompositeLabelBase64($trackingUrl, $dayIdentifier, $orderNumber);

        $assignedName = 'ninguno (Pendiente de Asignación)';
        if ($driver && $driver->user) {
            $assignedName = $driver->user->name;
            $driver->user->notify(new \App\Notifications\NewPackageAssigned($package));
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Package successfully processed. Assigned to: ' . $assignedName,
            'objeto' => [
                'delivery' => $delivery,
                'assignment_mode' => $mode,
                'label_image' => $labelImageBase64,
                'day_identifier' => $dayIdentifier,
                'order_number' => $orderNumber
            ]
        ]);
    }

    private function calculateDriverOrderNumber($driverId)
    {
        return \App\Models\Delivery::where('driver_id', $driverId)
            ->whereDate('created_at', now())
            ->count();
    }

    private function generateTrackingUrl($packageReference, $deliveryId)
    {
        // Enlaza la referencia del paquete y el ID del delivery para armar el QR web
        return config('app.url') . "/delivery-info/{$packageReference}/{$deliveryId}";
    }

    private function generateCompositeLabelBase64($url, $dayIdentifier, $orderNumber)
    {
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(0)->generate($url);
        
        // Remove <?xml namespace header from inline svg if present
        $qrSvg = preg_replace('/<\?xml.*?\?>/i', '', $qrSvg);
        
        // Elemento SVG Contenedor
        $svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
        $svg .= '<svg width="350" height="150" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="350" height="150" fill="#ffffff" rx="10" ry="10" stroke="#cccccc" stroke-width="1" />';
        
        // Parte izquierda: el QR generado
        $svg .= '<g transform="translate(15, 15)">' . $qrSvg . '</g>';
        
        // Parte derecha: Textos y números
        $svg .= '<text x="150" y="45" font-family="Verdana, sans-serif" font-size="14" fill="#666666">Identificador del Día:</text>';
        $svg .= '<text x="150" y="70" font-family="Verdana, sans-serif" font-size="20" fill="#000000" font-weight="bold">' . $dayIdentifier . '</text>';
        
        $svg .= '<text x="150" y="110" font-family="Verdana, sans-serif" font-size="14" fill="#666666">Orden del Paquete:</text>';
        $svg .= '<text x="290" y="110" font-family="Verdana, sans-serif" font-size="20" fill="#e63946" font-weight="bold">' . $orderNumber . '</text>';
        
        $svg .= '</svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    #[OA\Post(
        path: "/api/v1/delivery/assign",
        summary: "Manually assign package to driver",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "package_id", type: "integer", example: 1),
                    new OA\Property(property: "driver_id", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Package assigned successfully"),
            new OA\Response(response: 422, description: "Constraints violated")
        ]
    )]
    public function assignPackage(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $package = Package::find($request->package_id);
        $driver = Driver::find($request->driver_id);
        $coordinator = auth('api')->user()->coordinator;

        if (!$coordinator) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized'], 403);
        }

        // Validate package status
        if ($package->status !== 'pending' && $package->status !== 'assigned') {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'The package is already delivered or in an invalid state',
                'objeto' => null
            ], 422);
        }

        // Validate driver belongs to coordinator and is active
        if ($driver->coordinator_id !== $coordinator->id || !$driver->active) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Invalid or inactive driver selected',
                'objeto' => null
            ], 422);
        }

        // If delivery already exists (was pending), update it instead of creating a new one
        $existingDelivery = \App\Models\Delivery::where('package_id', $package->id)->first();
        if ($existingDelivery) {
            $existingDelivery->update([
                'driver_id' => $driver->id,
                'status' => \App\Enums\DeliveryStatus::ASSIGNED->value,
                'assigned_at' => now(),
            ]);
            $package->update(['status' => 'assigned']);
            
            if ($driver && $driver->user) {
                $driver->user->notify(new \App\Notifications\NewPackageAssigned($package));
            }
            
            $impact = $this->deliveryService->evaluateDriverImpact($package, $driver, $coordinator);
            $impactMsg = $impact ? sprintf(' (Impacto: +%.2f km, +%.1f min)', $impact['increase_distance_km'], $impact['increase_time_min']) : '';

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Package successfully assigned to ' . $driver->user->name . $impactMsg,
                'objeto' => [
                    'delivery' => $existingDelivery->load(['package', 'driver.user', 'route']),
                    'assignment_mode' => 'manual',
                    'impact' => $impact
                ]
            ]);
        }

        $mode = $this->settingService->getAssignmentMode();
        return $this->processPackageAssignment($package, $driver, $coordinator, $mode);
    }

    #[OA\Post(
        path: "/api/v1/delivery/register-package",
        summary: "Register a new package and assign it",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "package_reference", type: "string", example: "PKG-12345"),
                    new OA\Property(property: "address", type: "string", example: "123 Main St"),
                    new OA\Property(property: "driver_id", type: "integer", example: 1, description: "Optional for automatic mode")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Package registered and assigned"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function registerPackage(Request $request)
    {
        $request->validate([
            'package_reference' => 'required|string|unique:packages,package_reference',
            'address' => 'required|string',
            'driver_id' => 'nullable|exists:drivers,id'
        ]);

        $coordinator = auth('api')->user()->coordinator;
        if (!$coordinator) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized'], 403);
        }

        // Create the package
        $package = Package::create([
            'package_reference' => $request->package_reference,
            'address' => $request->address,
            'zone' => 'Sin Zona',
            'status' => 'pending'
        ]);

        $mode = $this->settingService->getAssignmentMode();
        $driver = null;

        if ($request->driver_id) {
            $driver = Driver::find($request->driver_id);
        } elseif ($mode === 'automatic') {
            $driver = $this->deliveryService->getAvailableDriver($coordinator, $package);
        }

        // We now allow $driver to be null. processPackageAssignment will create the delivery in pending_assignment state.
        return $this->processPackageAssignment($package, $driver, $coordinator, $mode);
    }

    #[OA\Post(
        path: "/api/v1/delivery/{id}/delivered",
        summary: "Mark package as delivered",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "observation", type: "string", example: "Delivered to neighbor")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Package marked as delivered"),
            new OA\Response(response: 422, description: "Already delivered or error")
        ]
    )]
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
            'observation' => 'nullable|string',
        ]);

        $delivery = \App\Models\Delivery::findOrFail($id);

        if ($delivery->status === 'delivered') {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Esta entrega ya fue realizada',
                'objeto' => null
            ], 422);
        }

        if (in_array(strtolower($request->status), ['entregado', 'delivered', 'recibido'])) {
            $delivery->update([
                'status' => 'delivered',
                'observation' => $request->observation,
                'delivered_at' => now(),
            ]);
            // Ensure child package status is also updated
            if ($delivery->package) {
                $delivery->package->update(['status' => 'delivered']);
            }
        } else {
            // Treat as failure or other observation
            $delivery->update([
                'status' => \App\Enums\DeliveryStatus::FAILED->value,
                'observation' => $request->observation,
            ]);

            // Notify coordinator about the issue
            if ($delivery->coordinator && $delivery->coordinator->user) {
                $delivery->coordinator->user->notify(new \App\Notifications\DriverReportedIssue($delivery));
            }
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Estado actualizado correctamente',
            'objeto' => $delivery
        ]);
    }

    #[OA\Post(
        path: "/api/v1/delivery/{id}/requeue",
        summary: "Return a failed or unassigned delivery back to the pending pool",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Delivery requeued successfully")
        ]
    )]
    public function requeueDelivery($id)
    {
        $user = auth('api')->user();
        if (!in_array($user->role, ['coordinator', 'admin', 'company_admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized'], 403);
        }

        $delivery = \App\Models\Delivery::findOrFail($id);

        if ($delivery->status === 'delivered') {
            return response()->json(['status' => 'error', 'mensaje' => 'Cannot requeue a delivered package'], 422);
        }

        $delivery->update([
            'status' => \App\Enums\DeliveryStatus::PENDING_ASSIGNMENT->value,
            'driver_id' => null,
            'observation' => null,
            'assigned_at' => null,
        ]);

        if ($delivery->package) {
            $delivery->package->update(['status' => 'pending']);
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Paquete devuelto a la lista de pendientes exitosamente',
            'objeto' => $delivery
        ]);
    }

    public function getMyDeliveries()
    {
        $user = auth('api')->user();
        if (!$user->driver) {
            return response()->json(['status' => 'error', 'mensaje' => 'User is not a driver'], 403);
        }

        $deliveries = \App\Models\Delivery::with('package.geocodedAddress')
            ->where('driver_id', $user->driver->id)
            ->whereDate('created_at', now())
            ->get();

        $stats = [
            'total' => $deliveries->count(),
            'entregados' => $deliveries->where('status', 'delivered')->count(),
            'no_entregados' => $deliveries->where('status', 'assigned')->whereNotNull('observation')->count(),
            'pendientes' => $deliveries->where('status', 'assigned')->whereNull('observation')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Deliveries retrieved',
            'deliveries' => $deliveries,
            'stats' => $stats
        ]);
    }

    #[OA\Get(
        path: "/api/v1/delivery/info/{package_reference}/{delivery_id}",
        summary: "Get full details of a delivery by scanning",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "package_reference", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "delivery_id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Delivery details retrieved"),
            new OA\Response(response: 404, description: "Delivery not found")
        ]
    )]
    public function getDeliveryInfo($package_reference, $delivery_id)
    {
        $package = Package::where('package_reference', $package_reference)->first();
        if (!$package) {
            return response()->json(['status' => 'error', 'mensaje' => 'El paquete no existe', 'objeto' => null], 404);
        }

        $delivery = \App\Models\Delivery::with(['driver.user', 'coordinator.user', 'package'])
            ->where('id', $delivery_id)
            ->where('package_id', $package->id)
            ->first();

        if (!$delivery) {
            return response()->json(['status' => 'error', 'mensaje' => 'La entrega no coincide con el paquete', 'objeto' => null], 404);
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Información de la entrega recibida exitosamente',
            'objeto' => [
                'delivery' => $delivery,
                'package' => $package
            ]
        ]);
    }

    /**
     * Get history of deliveries for the authenticated driver.
     */
    #[OA\Get(
        path: "/api/v1/delivery/{id}/popup-details",
        summary: "Get lightweight delivery DTO for map popups",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Delivery Popup DTO")
        ]
    )]
    public function getPopupDetails($id)
    {
        $delivery = \App\Models\Delivery::with(['package', 'driver.user'])->find($id);

        if (!$delivery) {
            return response()->json(['status' => 'error', 'mensaje' => 'Entrega no encontrada'], 404);
        }

        // Lightweight DTO
        $dto = [
            'id' => $delivery->id,
            'status' => $delivery->status,
            'observation' => $delivery->observation,
            'assigned_at' => $delivery->assigned_at ? $delivery->assigned_at->format('Y-m-d H:i') : null,
            'delivered_at' => $delivery->delivered_at ? $delivery->delivered_at->format('Y-m-d H:i') : null,
            'package_reference' => $delivery->package ? $delivery->package->package_reference : 'N/A',
            'address' => $delivery->package ? $delivery->package->address : 'Sin dirección',
            'driver_name' => ($delivery->driver && $delivery->driver->user) ? $delivery->driver->user->name : 'Sin conductor'
        ];

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Detalles de la entrega recuperados',
            'objeto' => $dto
        ]);
    }

    public function getHistory()
    {
        $user = auth('api')->user();
        if (!$user->driver) {
            return response()->json(['status' => 'error', 'mensaje' => 'User is not a driver'], 403);
        }

        $deliveries = \App\Models\Delivery::with('package')
            ->where('driver_id', $user->driver->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform data to match frontend expectations (referencia_qr, estado, nombre_receptor)
        $mapped = $deliveries->map(function ($delivery) {
            $estado = 'Assigned';
            if ($delivery->status === 'delivered') {
                $estado = 'Delivered';
            } elseif ($delivery->observation) {
                $estado = 'Failed';
            }

            return [
                'id' => $delivery->id,
                'referencia_qr' => $delivery->package->package_reference,
                'nombre_receptor' => 'QR Client', // Fallback
                'direccion' => $delivery->package->address,
                'estado' => $estado,
                'fecha' => $delivery->created_at->format('Y-m-d'),
                'hora' => $delivery->delivered_at ? $delivery->delivered_at->format('H:i') : null,
                'objeto_original' => $delivery // Keep original for details if needed
            ];
        });

        return response()->json([
            'status' => 'success',
            'objeto' => $mapped
        ]);
    }

    /**
     * Get currently assigned (not delivered) packages for the driver.
     */
    public function getAssigned()
    {
        $user = auth('api')->user();
        if (!$user->driver) {
            return response()->json(['status' => 'error', 'mensaje' => 'User is not a driver'], 403);
        }

        $deliveries = \App\Models\Delivery::with('package')
            ->where('driver_id', $user->driver->id)
            ->where('status', 'assigned')
            ->get();

        return response()->json([
            'status' => 'success',
            'objeto' => $deliveries
        ]);
    }

    #[OA\Get(
        path: "/api/v1/delivery/active-routes",
        summary: "Get active routes for all drivers today",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Active routes grouped by driver")
        ]
    )]
    public function getActiveRoutes(Request $request)
    {
        $user = auth('api')->user();
        if (!in_array($user->role, ['coordinator', 'admin', 'company_admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized'], 403);
        }

        $coordinator = $user->coordinator;
        
        $deliveries = \App\Models\Delivery::with(['package.geocodedAddress', 'driver.user'])
            ->where('status', \App\Enums\DeliveryStatus::ASSIGNED->value)
            ->whereNotNull('driver_id')
            ->whereDate('assigned_at', today())
            ->when($coordinator, function ($query) use ($coordinator) {
                // If it's a coordinator, scope by their company
                return $query->whereHas('driver', function ($q) use ($coordinator) {
                    $q->where('company_id', $coordinator->company_id);
                });
            })
            ->orderBy('driver_id')
            ->orderBy('delivery_order')
            ->get();

        // Group by driver
        $grouped = $deliveries->groupBy('driver_id')->map(function ($driverDeliveries) {
            $first = $driverDeliveries->first();
            $driverData = $first->driver ? [
                'id' => $first->driver->id,
                'name' => $first->driver->user ? $first->driver->user->name : 'Unknown',
            ] : null;

            return [
                'driver' => $driverData,
                'route' => $driverDeliveries->map(function ($del) {
                    return [
                        'delivery_id' => $del->id,
                        'package_reference' => $del->package ? $del->package->package_reference : null,
                        'delivery_order' => $del->delivery_order,
                        'latitude' => $del->package && $del->package->geocodedAddress ? $del->package->geocodedAddress->latitude : null,
                        'longitude' => $del->package && $del->package->geocodedAddress ? $del->package->geocodedAddress->longitude : null,
                    ];
                })->filter(fn($item) => $item['latitude'] !== null)->values()
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Active routes retrieved',
            'objeto' => $grouped
        ]);
    }

    #[OA\Get(
        path: "/api/v1/delivery/failed",
        summary: "Get list of failed deliveries",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of failed deliveries")
        ]
    )]
    public function getFailed(Request $request)
    {
        $user = auth('api')->user();
        
        if (!in_array($user->role, ['coordinator', 'admin', 'company_admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized'], 403);
        }

        $coordinator = $user->coordinator;

        $deliveries = \App\Models\Delivery::with(['package.geocodedAddress', 'driver.user'])
            ->where(function ($query) {
                $query->where('status', \App\Enums\DeliveryStatus::FAILED->value)
                      ->orWhereNotNull('observation');
            })
            ->where('status', '!=', 'delivered')
            ->when($coordinator, function ($query) use ($coordinator) {
                return $query->whereHas('driver', function ($q) use ($coordinator) {
                    $q->where('company_id', $coordinator->company_id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Failed deliveries retrieved',
            'objeto' => $deliveries
        ]);
    }

    #[OA\Get(
        path: "/api/v1/delivery/pending",
        summary: "Get list of pending assignment deliveries",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of pending deliveries")
        ]
    )]
    public function getPending()
    {
        $user = auth('api')->user();
        
        if (!in_array($user->role, ['coordinator', 'admin', 'company_admin', 'super_admin'])) {
            return response()->json(['status' => 'error', 'mensaje' => 'Unauthorized to view pending deliveries'], 403);
        }

        $deliveries = \App\Models\Delivery::with(['package.geocodedAddress', 'route'])
            ->where(function ($query) {
                $query->where('status', \App\Enums\DeliveryStatus::PENDING_ASSIGNMENT->value)
                      ->orWhereNull('driver_id');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Pending deliveries retrieved',
            'objeto' => $deliveries
        ]);
    }

    #[OA\Post(
        path: "/api/v1/delivery/generate-batch",
        summary: "Generate and assign batch routes from a branch",
        tags: ["Delivery"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "branch_id", type: "integer", example: 1),
                    new OA\Property(property: "package_ids", type: "array", items: new OA\Items(type: "integer")),
                    new OA\Property(property: "driver_ids", type: "array", items: new OA\Items(type: "integer"))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Batch routes generated"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function generateBatchRoutes(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'package_ids' => 'required|array|min:1',
            'package_ids.*' => 'exists:packages,id',
            'driver_ids' => 'required|array|min:1',
            'driver_ids.*' => 'exists:drivers,id',
        ]);

        $coordinator = auth('api')->user()->coordinator;
        
        if (!$coordinator) {
            return response()->json(['status' => 'error', 'mensaje' => 'Only coordinators can generate routes'], 403);
        }

        try {
            $branch = \App\Models\Branch::with('address')->findOrFail($request->branch_id);
            $packages = \App\Models\Package::with('geocodedAddress')->whereIn('id', $request->package_ids)->get()->all();
            $drivers = \App\Models\Driver::whereIn('id', $request->driver_ids)->get()->all();

            $optimizedResults = $this->intelligentAssignmentService->generateOptimizedRoutes($branch, $packages, $drivers);
            
            if (empty($optimizedResults)) {
                return response()->json(['status' => 'error', 'mensaje' => 'Failed to generate optimized routes'], 500);
            }

            $this->deliveryService->assignBatchRoutes($optimizedResults, $coordinator, $branch->id);

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Rutas generadas y asignadas correctamente.',
                'objeto' => [
                    'total_packages_assigned' => count($packages),
                    'drivers_assigned' => count($drivers)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'mensaje' => $e->getMessage()], 500);
        }
    }
}
