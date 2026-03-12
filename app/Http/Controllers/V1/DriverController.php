<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class DriverController extends Controller
{
    #[OA\Get(
        path: "/api/v1/coordinator/drivers",
        summary: "List all drivers for the current coordinator",
        tags: ["Coordinator"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of drivers retrieved",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "objeto", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function listDrivers()
    {
        $coordinator = auth('api')->user()->coordinator;

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Drivers retrieved successfully',
            'objeto' => $coordinator->drivers()->with('user')->get()
        ]);
    }

    #[OA\Post(
        path: "/api/v1/coordinator/drivers",
        summary: "Create a new driver",
        tags: ["Coordinator"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "secret123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Driver created successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function createDriver(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'driver',
        ]);

        $driver = Driver::create([
            'user_id' => $user->id,
            'coordinator_id' => auth('api')->user()->coordinator->id,
            'active' => $request->active ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Driver created successfully',
            'objeto' => $driver->load('user')
        ], 201);
    }

    #[OA\Get(
        path: "/api/v1/coordinator/drivers/{id}",
        summary: "Get a specific driver",
        tags: ["Coordinator"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Driver details retrieved"),
            new OA\Response(response: 404, description: "Driver not found")
        ]
    )]
    public function getDriver($id)
    {
        $coordinator = auth('api')->user()->coordinator;
        $driver = $coordinator->drivers()->with('user')->find($id);

        if (!$driver) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Driver not found',
                'objeto' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Driver retrieved successfully',
            'objeto' => $driver
        ]);
    }

    #[OA\Post(
        path: "/api/v1/coordinator/drivers/{id}",
        summary: "Update a driver",
        tags: ["Coordinator"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "password", type: "string"),
                    new OA\Property(property: "active", type: "boolean")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Driver updated successfully"),
            new OA\Response(response: 404, description: "Driver not found")
        ]
    )]
    public function updateDriver(Request $request, $id)
    {
        $coordinator = auth('api')->user()->coordinator;
        $driver = $coordinator->drivers()->with('user')->find($id);

        if (!$driver) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Driver not found',
                'objeto' => null
            ], 404);
        }

        $user = $driver->user;

        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:6',
            'active' => 'sometimes|boolean'
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        if ($request->has('active')) {
            $user->active = $request->active;
            $driver->active = $request->active;
            $driver->save();
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Driver updated successfully',
            'objeto' => $driver->refresh()->load('user')
        ]);
    }

    #[OA\Delete(
        path: "/api/v1/coordinator/drivers/{id}",
        summary: "Delete a driver",
        tags: ["Coordinator"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Driver deleted successfully")
        ]
    )]
    public function deleteDriver($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->user->delete(); // Also deletes the user
        return response()->json([
            'status' => 'success',
            'mensaje' => 'Driver deleted successfully',
            'objeto' => null
        ]);
    }
}
