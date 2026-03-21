<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'Acceso denegado. Se requiere rol de Super Administrador.',
                'objeto'  => null,
            ], 403);
        }

        return $next($request);
    }
}
