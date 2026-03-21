<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sin usuario autenticado => auth:api lo maneja, dejar pasar
        if (!$user) {
            return $next($request);
        }

        // super_admin no pertenece a empresa => siempre puede pasar
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Si el usuario pertenece a una empresa, verificar que esté activa
        if ($user->company_id) {
            $company = $user->company;

            if (!$company || !$company->active) {
                return response()->json([
                    'status'  => 'error',
                    'mensaje' => 'Su empresa está inactiva. Contacte al administrador del sistema.',
                    'objeto'  => null,
                ], 403);
            }
        }

        return $next($request);
    }
}
