<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AdminController extends Controller
{

    #[OA\Get(
        path: "/api/v1/admin/coordinators",
        summary: "List all coordinators",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of coordinators retrieved")
        ]
    )]
    public function listCoordinators()
    {
        $coordinators = Coordinator::with('user')->get();
        return response()->json([
            'status' => 'success',
            'mensaje' => 'Coordinators retrieved successfully',
            'objeto' => $coordinators
        ]);
    }

    #[OA\Post(
        path: "/api/v1/admin/coordinators",
        summary: "Create a new coordinator",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "password", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Coordinator created successfully")
        ]
    )]
    public function createCoordinator(Request $request)
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
            'role' => 'coordinator',
            'active' => $request->active ?? true,
        ]);

        $coordinator = Coordinator::create([
            'user_id' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Coordinator created successfully',
            'objeto' => $coordinator->load('user')
        ], 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/coordinators/{id}",
        summary: "Get a specific coordinator",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Coordinator details retrieved"),
            new OA\Response(response: 404, description: "Coordinator not found")
        ]
    )]
    public function getCoordinator($id)
    {
        $coordinator = Coordinator::with('user')->find($id);

        if (!$coordinator) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Coordinator not found',
                'objeto' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Coordinator retrieved successfully',
            'objeto' => $coordinator
        ]);
    }

    #[OA\Post(
        path: "/api/v1/admin/coordinators/{id}",
        summary: "Update a coordinator",
        tags: ["Admin"],
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
            new OA\Response(response: 200, description: "Coordinator updated successfully"),
            new OA\Response(response: 404, description: "Coordinator not found")
        ]
    )]
    public function updateCoordinator(Request $request, $id)
    {
        $coordinator = Coordinator::with('user')->find($id);

        if (!$coordinator) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Coordinator not found',
                'objeto' => null
            ], 404);
        }

        $user = $coordinator->user;

        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:6',
            'active' => 'sometimes|boolean'
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        if ($request->has('active')) $user->active = $request->active;

        $user->save();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Coordinator updated successfully',
            'objeto' => $coordinator->refresh()->load('user')
        ]);
    }

    #[OA\Get(
        path: "/api/v1/admin/stats",
        summary: "Get coordinator productivity stats",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Stats retrieved")
        ]
    )]
    public function getStats()
    {
        $coordinators = Coordinator::with('user')->get();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Statistics retrieved successfully',
            'objeto' => [
                'total_coordinators' => $coordinators->count(),
                'total_packages' => \App\Models\Package::count(),
                'coordinator_productivity' => $coordinators->map(function ($c) {
                    return [
                        'name' => $c->user->name,
                        'count' => \App\Models\Delivery::where('coordinator_id', $c->id)->count()
                    ];
                })
            ]
        ]);
    }
}
