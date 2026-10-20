<?php

use App\Http\Controllers\Api\Company\AuthController as CompanyAuthController;
use App\Http\Controllers\Api\Company\DashboardController as CompanyDashboardController;
use App\Http\Controllers\Api\Company\PackageSearchController as CompanyPackageSearchController;
use App\Http\Controllers\Api\BulkIntegrationController;
use App\Http\Controllers\Api\Cn22ShipmentController;
use App\Http\Controllers\Api\Cn31ManifestController;
use App\Http\Controllers\Api\Cn33BagController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\BagMovementController;
use App\Http\Controllers\Api\IntegrationContextController;
use App\Http\Controllers\Api\InternalPackageDeliveryAttemptController;
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
        Route::get('/packages/search', CompanyPackageSearchController::class);
    });
});

Route::prefix('v1')
    ->middleware(['api.token', 'company.locale'])
    ->group(function (): void {
        Route::get('/me', MeController::class)->middleware('api.ability:packages:read');
        Route::get('/integration/context', IntegrationContextController::class)->middleware('api.ability:packages:read');
        Route::post('/integration/bulk', [BulkIntegrationController::class, 'store'])->middleware('api.ability:documents:write');

        Route::get('/cn31/manifests', [Cn31ManifestController::class, 'index'])->middleware('api.ability:documents:read');
        Route::post('/cn31/manifests', [Cn31ManifestController::class, 'store'])->middleware('api.ability:documents:write');
        Route::get('/cn31/manifests/{cn31Number}', [Cn31ManifestController::class, 'show'])->middleware('api.ability:documents:read');

        Route::post('/cn33/bags/{bagNumber}/packages', [Cn33BagController::class, 'store'])->middleware('api.ability:documents:write');
        Route::get('/cn33/bags/{bagNumber}', [Cn33BagController::class, 'show'])->middleware('api.ability:documents:read');
        Route::get('/cn33/bags/{bagNumber}/movements', [BagMovementController::class, 'index'])->middleware('api.ability:tracking:read');
        Route::post('/cn33/bags/{bagNumber}/movements', [BagMovementController::class, 'store'])->middleware('api.ability:tracking:events:write');

        Route::post('/cn22/shipments', [Cn22ShipmentController::class, 'store'])->middleware('api.ability:documents:write');

        Route::get('/packages', [PackageController::class, 'index'])->middleware('api.ability:tracking:read');
        Route::post('/packages', [PackageController::class, 'store'])->middleware('api.ability:tracking:write');
        Route::get('/packages/{trackingCode}', [PackageController::class, 'show'])->middleware('api.ability:tracking:read');

        Route::get('/packages/{trackingCode}/movements', [PackageMovementController::class, 'index'])->middleware('api.ability:tracking:read');
        Route::post('/packages/{trackingCode}/movements', [PackageMovementController::class, 'store'])->middleware('api.ability:tracking:events:write');

        Route::get('/webhooks', [WebhookEndpointController::class, 'index'])->middleware('api.ability:webhooks:manage');
        Route::post('/webhooks', [WebhookEndpointController::class, 'store'])->middleware('api.ability:webhooks:manage');
        Route::patch('/webhooks/{webhookEndpoint}', [WebhookEndpointController::class, 'update'])->middleware('api.ability:webhooks:manage');
        Route::get('/webhooks/{webhookEndpoint}/deliveries', [WebhookEndpointController::class, 'deliveries'])->middleware('api.ability:webhooks:manage');
        Route::post('/webhooks/{webhookEndpoint}/deliveries/{webhookDelivery}/retry', [WebhookEndpointController::class, 'retry'])->middleware('api.ability:webhooks:manage');
        Route::post('/webhooks/{webhookEndpoint}/test', [WebhookEndpointController::class, 'test'])->middleware('api.ability:webhooks:manage');
        Route::delete('/webhooks/{webhookEndpoint}', [WebhookEndpointController::class, 'destroy'])->middleware('api.ability:webhooks:manage');
    });

Route::prefix('v1/internal')
    ->middleware('internal.api')
    ->group(function (): void {
        Route::post('/packages/delivery-attempt', InternalPackageDeliveryAttemptController::class);
        Route::post('/packages/deliver', InternalPackageDeliveryController::class);
    });
