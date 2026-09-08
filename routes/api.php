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
use App\Http\Controllers\V1\RouteController;

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

    // Notifications
    Route::group(['prefix' => 'notifications', 'middleware' => 'auth:api'], function () {
        Route::get('/', [\App\Http\Controllers\V1\NotificationController::class, 'index']);
        Route::post('read-all', [\App\Http\Controllers\V1\NotificationController::class, 'markAllAsRead']);
        Route::patch('{id}/read', [\App\Http\Controllers\V1\NotificationController::class, 'markAsRead']);
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

    // Routes Management
    Route::group(['prefix' => 'routes', 'middleware' => ['auth:api', 'company.active']], function () {
        Route::get('/', [RouteController::class, 'index']);
        Route::post('/', [RouteController::class, 'store']);
        Route::post('{id}/points', [RouteController::class, 'addPoint']);
    });

    // Delivery Flow Routes
    Route::group(['prefix' => 'delivery', 'middleware' => ['auth:api', 'company.active']], function () {
        Route::get('pending', [DeliveryController::class, 'getPending']);
        Route::get('failed', [DeliveryController::class, 'getFailed']);
        Route::get('active-routes', [DeliveryController::class, 'getActiveRoutes']);
        Route::get('my-deliveries', [DeliveryController::class, 'getMyDeliveries']);
        Route::get('assigned', [DeliveryController::class, 'getAssigned']);
        Route::get('history', [DeliveryController::class, 'getHistory']);
        Route::post('scan', [DeliveryController::class, 'scanPackage']);
        Route::post('register-package', [DeliveryController::class, 'registerPackage']);
        Route::post('assign', [DeliveryController::class, 'assignPackage']);
        Route::post('generate-batch', [DeliveryController::class, 'generateBatchRoutes']);
        Route::get('info/{package_reference}/{delivery_id}', [DeliveryController::class, 'getDeliveryInfo']);
        Route::post('{id}/status', [DeliveryController::class, 'updateStatus']);
        // Alias for frontend driver service
        Route::post('{id}/delivered', [DeliveryController::class, 'updateStatus']);
        Route::post('{id}/requeue', [DeliveryController::class, 'requeueDelivery']);
        Route::get('{id}/popup-details', [DeliveryController::class, 'getPopupDetails']);
    });

    // Master Data Routes
    Route::group(['middleware' => ['auth:api', 'company.active']], function () {
        // Geocoding
        Route::post('geocode', [\App\Http\Controllers\V1\LocationController::class, 'geocode']);

        // Branches
        Route::get('branches', [\App\Http\Controllers\V1\BranchController::class, 'index']);
        Route::post('branches', [\App\Http\Controllers\V1\BranchController::class, 'store']);
        Route::post('branches/{id}', [\App\Http\Controllers\V1\BranchController::class, 'update']);
        Route::delete('branches/{id}', [\App\Http\Controllers\V1\BranchController::class, 'destroy']);
    });
});
