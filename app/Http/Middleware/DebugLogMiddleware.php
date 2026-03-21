<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        file_put_contents(storage_path('logs/debug_requests.log'), 
            date('Y-m-d H:i:s') . ' [' . $request->method() . '] ' . $request->fullUrl() . " Origin: " . $request->header('Origin') . "\n", 
            FILE_APPEND);
        return $next($request);
    }
}
