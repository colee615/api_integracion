<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationContextController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        return response()->json([
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'status' => $company->status,
                    'environment' => $company->environment,
                    'api_key_masked' => substr($company->api_key, 0, 4).'********'.substr($company->api_key, -4),
                ],
                'sandbox' => [
                    'active' => $company->isSandboxActive(),
                    'starts_at' => $company->sandbox_starts_at?->toIso8601String(),
                    'ends_at' => $company->sandbox_ends_at?->toIso8601String(),
                    'max_shipments' => $company->sandbox_max_shipments,
                    'used_shipments' => $company->sandbox_shipments_used,
                ],
                'supported_statuses' => PackageStatusCatalog::allowedStatuses(),
                'supported_webhook_events' => PackageStatusCatalog::webhookEvents(),
            ],
        ]);
    }
}
