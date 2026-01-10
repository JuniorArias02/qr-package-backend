<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'message' => 'QR Package API Running',
        'version' => '1.0.0'
    ]);
});
