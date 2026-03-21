<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
// In Laravel 11, there is no Kernel.php usually, but the contract still exists.
// However, the middleware list might be hidden.

echo "Middleware list:\n";
// This is trickier in Laravel 11.
