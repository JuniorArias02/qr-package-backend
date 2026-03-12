<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auth\PermissionService;
use App\Enums\UserRole;
use App\Enums\UserPermission;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Use the new DB-based permission check
        // We pass the string directly (e.g. 'scan_packages')
        // The User model helper will check against the user's roles' permissions in DB
        if (!$user->hasPermission($permission)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
