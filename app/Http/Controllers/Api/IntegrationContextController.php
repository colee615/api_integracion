<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiTokenContext;
use App\Support\IpsEventCatalog;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationContextController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

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
                'api_token' => [
                    'name' => $apiToken?->name,
                    'abilities' => $apiToken?->abilities ?? [],
                    'expires_at' => $apiToken?->expires_at?->toIso8601String(),
                ],
                'sandbox' => [
                    'active' => $company->isSandboxActive(),
                    'starts_at' => $company->sandbox_starts_at?->toIso8601String(),
                    'ends_at' => $company->sandbox_ends_at?->toIso8601String(),
                    'max_shipments' => $company->sandbox_max_shipments,
                    'used_shipments' => $company->sandbox_shipments_used,
                ],
                'supported_statuses' => PackageStatusCatalog::allowedStatuses(),
                'supported_status_catalog' => collect(PackageStatusCatalog::allowedStatuses())
                    ->map(fn (string $status) => [
                        'code' => $status,
                        'label' => PackageStatusCatalog::labelForStatus($status),
                        'event_code' => PackageStatusCatalog::eventForStatus($status),
                        'terminal' => PackageStatusCatalog::isTerminal($status),
                        'next_statuses' => PackageStatusCatalog::nextStatuses($status),
                    ])
                    ->values(),
                'supported_webhook_events' => PackageStatusCatalog::webhookEvents(),
                'supported_ips_package_operations' => collect(IpsEventCatalog::packageEvents())
                    ->map(fn (array $operation, string $code) => [
                        'operation_code' => $code,
                        'event_type_cd' => $operation['event_type_cd'],
                        'name' => $operation['name'],
                        'local_status' => $operation['status'],
                        'applies_to_existing_package' => $operation['existing_package'],
                        'requires_physical_confirmation' => (bool) ($operation['requires_physical_confirmation'] ?? false),
                        'required_fields' => $operation['required_fields'] ?? [],
                    ])
                    ->values(),
                'supported_ips_bag_operations' => collect(IpsEventCatalog::bagEvents())
                    ->map(fn (array $operation, string $code) => [
                        'operation_code' => $code,
                        'event_type_cd' => $operation['event_type_cd'],
                        'name' => $operation['name'],
                        'local_status' => $operation['status'],
                        'requires_physical_confirmation' => (bool) ($operation['requires_physical_confirmation'] ?? false),
                    ])
                    ->values(),
                'recommended_reason_codes' => PackageStatusCatalog::recommendedReasonCodes(),
                'tracking' => [
                    'event_time_format' => 'RFC 3339 with timezone, for example 2026-10-13T14:30:00-04:00',
                    'idempotency_header' => 'Idempotency-Key',
                    'late_events' => 'Events older than the current shipment projection are stored in history without moving the current status backwards.',
                    'history_endpoint' => '/api/v1/packages/{trackingCode}/movements',
                    'bag_history_endpoint' => '/api/v1/cn33/bags/{bagReference}/movements',
                    'ips_operations' => 'IPS operation codes are recorded locally with their numeric event type. They are not forwarded to IPS.',
                    'ips_stage_policy' => 'The next package operation is limited by the most recent local IPS operation and package status; EMR also requires the linked CN33 bag to be received and opened at the same location.',
                    'ips_local_validation_limits' => 'This app has no IPS STATE_IND_CD, office authorization, routing catalog, country routing, or reference catalog, so SITRA validates those rules against IPS separately.',
                ],
            ],
        ]);
    }
}
