<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreRouteRequest;
use App\Http\Requests\V1\StoreRoutePointRequest;
use App\Services\RouteService;
use App\Models\Route;
use App\Http\Resources\V1\RouteResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RouteController extends Controller
{
    public function __construct(
        protected RouteService $routeService
    ) {}

    #[OA\Get(
        path: "/api/v1/routes",
        summary: "List all routes for the current company",
        tags: ["Routes"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Routes retrieved successfully")
        ]
    )]
    public function index(): JsonResponse
    {
        $companyId = auth('api')->user()?->company_id;
        $routes = $this->routeService->getRoutes($companyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Routes retrieved successfully',
            'data' => RouteResource::collection($routes),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/routes/{id}",
        summary: "Get a specific route",
        tags: ["Routes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Route retrieved successfully"),
            new OA\Response(response: 404, description: "Route not found")
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $companyId = auth('api')->user()?->company_id;
        $route = $this->routeService->getRoute($id, $companyId);
        
        if (!$route) {
            return response()->json([
                'status' => 'error',
                'message' => 'Route not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Route retrieved successfully',
            'data' => new RouteResource($route),
        ]);
    }

    #[OA\Post(
        path: "/api/v1/routes",
        summary: "Create a new route with optional geofence points",
        tags: ["Routes"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Ruta Norte"),
                    new OA\Property(property: "description", type: "string", example: "Cobertura zona norte y alrededores"),
                    new OA\Property(property: "active", type: "boolean", example: true),
                    new OA\Property(
                        property: "points",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "latitude", type: "number", example: 4.60971),
                                new OA\Property(property: "longitude", type: "number", example: -74.08175),
                                new OA\Property(property: "radius_km", type: "number", example: 3.5)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Route created successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(StoreRouteRequest $request): JsonResponse
    {
        $companyId = auth('api')->user()?->company_id;
        $route = $this->routeService->createRoute($request->validated(), $companyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Route created successfully',
            'data' => new RouteResource($route),
        ], 201);
    }

    #[OA\Post(
        path: "/api/v1/routes/{id}/assign-drivers",
        summary: "Assign multiple drivers to a route",
        tags: ["Routes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "driver_ids",
                        type: "array",
                        items: new OA\Items(type: "integer", example: 1)
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Drivers assigned successfully"),
            new OA\Response(response: 404, description: "Route not found")
        ]
    )]
    public function assignDrivers(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $request->validate([
            'driver_ids' => 'required|array',
            'driver_ids.*' => 'exists:drivers,id'
        ]);

        $route = Route::findOrFail($id);
        
        if (!empty($request->driver_ids)) {
            $invalidDriversCount = \App\Models\Driver::whereIn('id', $request->driver_ids)
                ->where('branch_id', '!=', $route->branch_id)
                ->count();
            
            if ($invalidDriversCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede asignar conductores de otra sede a esta ruta.',
                ], 422);
            }

            // RESTRICCIÓN: Un conductor solo puede estar en UNA ruta a la vez.
            // Quitamos a los conductores seleccionados de cualquier otra ruta en la que estuvieran.
            \Illuminate\Support\Facades\DB::table('driver_route')
                ->whereIn('driver_id', $request->driver_ids)
                ->where('route_id', '!=', $route->id)
                ->delete();
        }

        // Sync drivers (adds new, removes unlisted from THIS route)
        $route->drivers()->sync($request->driver_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Conductores asignados a la ruta correctamente.',
            'data' => new RouteResource($route->load('drivers.user')),
        ]);
    }

    #[OA\Put(
        path: "/api/v1/routes/{id}",
        summary: "Update an existing route",
        tags: ["Routes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Route updated successfully")
        ]
    )]
    public function update(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        // Simple validation for update
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'sometimes|boolean',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'polygon_coordinates' => 'sometimes|required|array|min:3',
            'polygon_coordinates.*' => 'array|size:2',
            'polygon_coordinates.*.0' => 'required_with:polygon_coordinates|numeric',
            'polygon_coordinates.*.1' => 'required_with:polygon_coordinates|numeric',
        ]);

        $companyId = auth('api')->user()?->company_id;
        $route = $this->routeService->updateRoute($id, $data, $companyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Route updated successfully',
            'data' => new RouteResource($route),
        ]);
    }

    #[OA\Delete(
        path: "/api/v1/routes/{id}",
        summary: "Delete a route",
        tags: ["Routes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Route deleted successfully")
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $companyId = auth('api')->user()?->company_id;
        $this->routeService->deleteRoute($id, $companyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Route deleted successfully',
        ]);
    }
}
