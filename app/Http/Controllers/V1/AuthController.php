<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{

    #[OA\Post(
        path: "/api/v1/auth/login",
        summary: "Authenticate user and get JWT token",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "secret")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "mensaje", type: "string", example: "Login exitoso"),
                        new OA\Property(property: "objeto", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'No autorizado',
                'objeto' => null
            ], 401);
        }

        $user = auth('api')->user();

        if (!$user->active) {
            auth('api')->logout();
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Su cuenta está inactiva. Por favor contacte al administrador.',
                'objeto' => null
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    #[OA\Get(
        path: "/api/v1/auth/me",
        summary: "Get the authenticated User",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "User data retrieved",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "objeto", type: "object")
                    ]
                )
            )
        ]
    )]
    public function me()
    {
        return response()->json([
            'status' => 'success',
            'mensaje' => 'Usuario recuperado',
            'objeto' => auth('api')->user()
        ]);
    }

    #[OA\Post(
        path: "/api/v1/auth/profile",
        summary: "Update authenticated user profile",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Updated"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john.updated@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "newsecret123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Profile updated successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Perfil actualizado correctamente',
            'objeto' => $user
        ]);
    }

    #[OA\Post(
        path: "/api/v1/auth/profile-photo",
        summary: "Update authenticated user profile photo",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "photo", type: "string", format: "binary", description: "Profile photo (max 15MB)")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Photo updated successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:15360', // 15MB = 15360KB
        ]);

        $user = auth('api')->user();

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->profile_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('photo')->store('profile', 'public');
            $user->profile_image = $path;
            $user->save();

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Foto de perfil actualizada correctamente',
                'objeto' => [
                    'profile_image' => $user->profile_image,
                    'profile_image_url' => asset('storage/' . $user->profile_image)
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'mensaje' => 'No se ha proporcionado ninguna imagen',
            'objeto' => null
        ], 400);
    }

    #[OA\Post(
        path: "/api/v1/auth/logout",
        summary: "Log the user out",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Successfully logged out")
        ]
    )]
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Sesión cerrada correctamente',
            'objeto' => null
        ]);
    }

    #[OA\Post(
        path: "/api/v1/auth/refresh",
        summary: "Refresh a token",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Token refreshed")
        ]
    )]
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => 'success',
            'mensaje' => 'Login exitoso',
            'objeto' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => auth('api')->user()
            ]
        ]);
    }
}
