<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: "QR Package API",
    version: "1.0.0",
    description: "API documentation for the QR Package Delivery System backend redesign."
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
abstract class Controller
{
    use AuthorizesRequests;
}
