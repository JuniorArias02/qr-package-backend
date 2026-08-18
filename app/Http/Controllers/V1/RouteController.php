<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreRouteRequest;
use App\Http\Requests\V1\StoreRoutePointRequest;
use App\Services\RouteService;
use App\Models\Route;
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
            'data' => $routes,
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
            'data' => $route,
        ], 201);
    }

    #[OA\Post(
        path: "/api/v1/routes/{id}/points",
        summary: "Add a geofence point to an existing route",
        tags: ["Routes"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "latitude", type: "number", example: 4.60971),
                    new OA\Property(property: "longitude", type: "number", example: -74.08175),
                    new OA\Property(property: "radius_km", type: "number", example: 2.0)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Point added to route successfully"),
            new OA\Response(response: 404, description: "Route not found")
        ]
    )]
    public function addPoint(StoreRoutePointRequest $request, int $id): JsonResponse
    {
        $route = Route::findOrFail($id);
        $point = $this->routeService->addPoint($route, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Point added to route successfully',
            'data' => $point,
        ], 201);
    }
}
