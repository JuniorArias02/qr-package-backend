<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\AdminController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CoordinatorController;
use App\Http\Controllers\V1\DriverController;
use App\Http\Controllers\V1\DeliveryController;
use App\Http\Controllers\V1\CoordinatorReportController;

use App\Http\Controllers\V1\DriverAnalyticsController;
use App\Http\Controllers\V1\SuperAdminCompanyController;
use App\Http\Controllers\V1\SuperAdminUserController;
use App\Http\Controllers\V1\SuperAdminController;

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

    // ─── Super Admin Routes ───
    Route::group(['prefix' => 'super-admin', 'middleware' => ['auth:api', 'super_admin']], function () {
        // Companies
        Route::get('companies',                [SuperAdminCompanyController::class, 'index']);
        Route::post('companies',               [SuperAdminCompanyController::class, 'store']);
        Route::get('companies/{id}',           [SuperAdminCompanyController::class, 'show']);
        Route::get('companies/{id}/details',   [SuperAdminCompanyController::class, 'getDetails']);
        Route::post('companies/{id}',          [SuperAdminCompanyController::class, 'update']);
        Route::post('companies/{id}/toggle',   [SuperAdminCompanyController::class, 'toggle']);
        // Users
        Route::get('users',                    [SuperAdminUserController::class, 'index']);
        Route::post('users',                   [SuperAdminUserController::class, 'store']);
        Route::post('users/{id}/toggle',       [SuperAdminUserController::class, 'toggle']);
        // Global system
        Route::get('dashboard',                [SuperAdminController::class, 'getDashboardStats']);
        Route::get('deliveries',               [SuperAdminController::class, 'getAllDeliveries']);
    });

    // Driver Specific Analytics
    Route::group(['prefix' => 'driver', 'middleware' => ['auth:api', 'company.active']], function () {
        Route::get('analytics/dashboard', [DriverAnalyticsController::class, 'getDashboardData']);
    });

    // Admin Routes
    Route::group(['prefix' => 'admin', 'middleware' => ['auth:api', 'company.active']], function () {
        Route::get('coordinators', [AdminController::class, 'listCoordinators']);
        Route::get('coordinators/{id}', [AdminController::class, 'getCoordinator']);
        Route::post('coordinators', [AdminController::class, 'createCoordinator']);
        Route::post('coordinators/{id}', [AdminController::class, 'updateCoordinator']);
        Route::get('stats', [AdminController::class, 'getStats']);
    });

    // Coordinator Routes
    Route::group(['prefix' => 'coordinator', 'middleware' => ['auth:api', 'company.active']], function () {
        Route::get('drivers', [DriverController::class, 'listDrivers']);
        Route::get('drivers/{id}', [DriverController::class, 'getDriver']);
        Route::post('drivers', [DriverController::class, 'createDriver']);
        Route::post('drivers/{id}', [DriverController::class, 'updateDriver']);
        Route::delete('drivers/{id}', [DriverController::class, 'deleteDriver']);
        Route::get('stats', [CoordinatorController::class, 'getStats']);

        // Reporting Routes
        Route::group(['prefix' => 'reports'], function () {
            Route::get('metrics', [CoordinatorReportController::class, 'metrics']);
            Route::get('deliveries', [CoordinatorReportController::class, 'deliveries']);
            Route::get('drivers', [CoordinatorReportController::class, 'drivers']);
            Route::get('chart', [CoordinatorReportController::class, 'chart']);
        });
    });

    // Delivery Flow Routes
    Route::group(['prefix' => 'delivery', 'middleware' => ['auth:api', 'company.active']], function () {
        Route::get('my-deliveries', [DeliveryController::class, 'getMyDeliveries']);
        Route::get('assigned', [DeliveryController::class, 'getAssigned']);
        Route::get('history', [DeliveryController::class, 'getHistory']);
        Route::post('scan', [DeliveryController::class, 'scanPackage']);
        Route::post('register-package', [DeliveryController::class, 'registerPackage']);
        Route::post('assign', [DeliveryController::class, 'assignPackage']);
        Route::get('info/{package_reference}/{delivery_id}', [DeliveryController::class, 'getDeliveryInfo']);
        Route::post('{id}/status', [DeliveryController::class, 'updateStatus']);
        // Alias for frontend driver service
        Route::post('{id}/delivered', [DeliveryController::class, 'updateStatus']);
    });
});
