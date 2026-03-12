<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\AdminController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CoordinatorController;
use App\Http\Controllers\V1\DriverController;
use App\Http\Controllers\V1\DeliveryController;

Route::prefix('v1')->group(function () {

    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('profile', [AuthController::class, 'updateProfile']);
            Route::post('profile-photo', [AuthController::class, 'updateProfilePhoto']);
        });
    });

    // Admin Routes
    Route::prefix('admin')->middleware(['auth:api'])->group(function () {
        Route::get('coordinators', [AdminController::class, 'listCoordinators']);
        Route::get('coordinators/{id}', [AdminController::class, 'getCoordinator']);
        Route::post('coordinators', [AdminController::class, 'createCoordinator']);
        Route::post('coordinators/{id}', [AdminController::class, 'updateCoordinator']);
        Route::get('stats', [AdminController::class, 'getStats']);
    });

    // Coordinator Routes
    Route::prefix('coordinator')->middleware(['auth:api'])->group(function () {
        Route::get('drivers', [DriverController::class, 'listDrivers']);
        Route::get('drivers/{id}', [DriverController::class, 'getDriver']);
        Route::post('drivers', [DriverController::class, 'createDriver']);
        Route::post('drivers/{id}', [DriverController::class, 'updateDriver']);
        Route::delete('drivers/{id}', [DriverController::class, 'deleteDriver']);
        Route::get('stats', [CoordinatorController::class, 'getStats']);
    });

    // Delivery Flow Routes
    Route::prefix('delivery')->middleware(['auth:api'])->group(function () {
        Route::get('my-deliveries', [DeliveryController::class, 'getMyDeliveries']);
        Route::post('scan', [DeliveryController::class, 'scanPackage']);
        Route::post('register-package', [DeliveryController::class, 'registerPackage']);
        Route::post('assign', [DeliveryController::class, 'assignPackage']);
        Route::get('info/{package_reference}/{delivery_id}', [DeliveryController::class, 'getDeliveryInfo']);
        Route::post('{id}/status', [DeliveryController::class, 'updateStatus']);
    });
});
