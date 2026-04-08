<?php

use App\Http\Controllers\Api\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Api\Company\DashboardController as CompanyDashboardController;
use App\Http\Controllers\Api\BulkIntegrationController;
use App\Http\Controllers\Api\Cn22ShipmentController;
use App\Http\Controllers\Api\Cn31ManifestController;
use App\Http\Controllers\Api\Cn33BagController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\IntegrationContextController;
use App\Http\Controllers\Api\InternalPackageDeliveryController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PackageMovementController;
use App\Http\Controllers\Api\WebhookEndpointController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/company')->group(function (): void {
    Route::post('/auth/login', [CompanyAuthController::class, 'login']);

    Route::middleware(['company.portal', 'company.locale'])->group(function (): void {
        Route::get('/auth/me', [CompanyAuthController::class, 'me']);
        Route::post('/auth/logout', [CompanyAuthController::class, 'logout']);
        Route::post('/auth/change-password', [CompanyAuthController::class, 'changePassword']);
        Route::get('/dashboard', CompanyDashboardController::class);
    });
});

Route::prefix('v1')
    ->middleware(['api.token', 'company.locale'])
    ->group(function (): void {
        Route::get('/me', MeController::class);
        Route::get('/integration/context', IntegrationContextController::class);
        Route::post('/integration/bulk', [BulkIntegrationController::class, 'store']);

        Route::get('/cn31/manifests', [Cn31ManifestController::class, 'index']);
        Route::post('/cn31/manifests', [Cn31ManifestController::class, 'store']);
        Route::get('/cn31/manifests/{cn31Number}', [Cn31ManifestController::class, 'show']);

        Route::post('/cn33/bags/{bagNumber}/packages', [Cn33BagController::class, 'store']);
        Route::get('/cn33/bags/{bagNumber}', [Cn33BagController::class, 'show']);

        Route::post('/cn22/shipments', [Cn22ShipmentController::class, 'store']);

        Route::get('/packages', [PackageController::class, 'index']);
        Route::post('/packages', [PackageController::class, 'store']);
        Route::get('/packages/{trackingCode}', [PackageController::class, 'show']);

        Route::get('/packages/{trackingCode}/movements', [PackageMovementController::class, 'index']);
        Route::post('/packages/{trackingCode}/movements', [PackageMovementController::class, 'store']);

        Route::get('/webhooks', [WebhookEndpointController::class, 'index']);
        Route::post('/webhooks', [WebhookEndpointController::class, 'store']);
        Route::post('/webhooks/{webhookEndpoint}/test', [WebhookEndpointController::class, 'test']);
    });

Route::prefix('v1/internal')
    ->group(function (): void {
        Route::post('/packages/deliver', InternalPackageDeliveryController::class);
    });
