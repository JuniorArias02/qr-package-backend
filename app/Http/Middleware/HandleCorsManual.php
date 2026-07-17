<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCorsManual
{
    public function handle(Request $request, Closure $next): Response
    {
        // Leer orígenes permitidos desde el .env
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', '*'));
        $requestOrigin = $request->headers->get('origin');
        
        $originToSet = '*';
        
        // Validar el origen de la petición contra los permitidos en .env
        if (in_array('*', $allowedOrigins)) {
            // Si está '*', usamos el origin entrante para no romper las peticiones con credentials (cookies/auth)
            $originToSet = $requestOrigin ?: '*';
        } elseif ($requestOrigin && in_array($requestOrigin, $allowedOrigins)) {
            $originToSet = $requestOrigin;
        } else {
            $originToSet = $allowedOrigins[0] ?? '*';
        }

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $originToSet)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        $response = $next($request);

        // Add headers to the final response
        if (property_exists($response, 'headers') || method_exists($response, 'header')) {
            $response->headers->set('Access-Control-Allow-Origin', $originToSet);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
