<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\TrackingEventWriter;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalPackageDeliveryController extends Controller
{
    public function __invoke(Request $request, TrackingEventWriter $writer): JsonResponse
    {
        $validated = $request->validate([
            'tracking_code' => ['required', 'string', 'max:100'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'occurred_at' => [
                'nullable',
                'date',
                'regex:/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?(?:Z|[+-]\\d{2}:\\d{2})$/',
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'location_code' => ['nullable', 'string', 'max:80'],
            'reason_code' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_reference' => ['nullable', 'string', 'max:120'],
            'meta' => ['nullable', 'array'],
        ]);

        $idempotencyKey = $request->header('Idempotency-Key') ?: ($validated['source_reference'] ?? null);
        if ($idempotencyKey !== null && ! preg_match('/^[A-Za-z0-9._:-]{1,120}$/', $idempotencyKey)) {
            return response()->json([
                'code' => 'INVALID_IDEMPOTENCY_KEY',
                'message' => __('api.errors.invalid_idempotency_key'),
            ], 422);
        }

        $matches = Package::query()
            ->with(['company', 'cn33Package.bag.manifest'])
            ->where('tracking_code', $validated['tracking_code'])
            ->whereNull('created_by_api_token_id')
            ->when(isset($validated['company_id']), fn ($query) => $query->where('company_id', $validated['company_id']))
            ->limit(2)
            ->get();

        if ($matches->isEmpty()) {
            return response()->json([
                'message' => __('api.not_found.package', ['tracking_code' => $validated['tracking_code']]),
            ], 404);
        }

        if ($matches->count() > 1) {
            return response()->json([
                'code' => 'AMBIGUOUS_TRACKING_CODE',
                'message' => __('api.errors.ambiguous_tracking_code'),
            ], 409);
        }

        $package = $matches->first();
        $result = $writer->record($package, [
            ...$validated,
            'status' => 'entregado',
            'meta' => array_merge($validated['meta'] ?? [], ['source' => 'internal_delivery_api']),
        ], 'internal_api', $idempotencyKey);

        if ($result['conflict']) {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'message' => __('api.errors.idempotency_conflict'),
            ], 409);
        }

        $occurredAt = $result['movement']->occurred_at->utc();
        $package = $package->fresh(['company', 'cn33Package.bag.manifest']);
        $movement = $result['movement'];
        return response()->json([
            'message' => $result['late']
                ? __('api.messages.movement_registered')
                : __('api.messages.package_delivered_success'),
            'data' => [
                'tracking_code' => $package->tracking_code,
                'status' => $package->status,
                'status_label' => PackageStatusCatalog::labelForStatus($package->status),
                'delivery_attempts' => (int) $package->delivery_attempts,
                'last_delivery_attempt_at' => $package->last_delivery_attempt_at?->utc()->toIso8601String(),
                'delivered_at' => $occurredAt->toIso8601String(),
                'event_id' => $movement->event_id,
                'replayed' => $result['replayed'],
                'late_event' => $result['late'],
                'reason_code' => $movement->reason_code,
                'bag' => $package->cn33Package?->bag ? [
                    'bag_number' => $package->cn33Package->bag->bag_number,
                    'status' => $package->cn33Package->bag->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($package->cn33Package->bag->status),
                    'operational_status' => $package->cn33Package->bag->operational_status,
                    'operational_status_label' => __('api.statuses.'.$package->cn33Package->bag->operational_status),
                    'delivery_status' => $package->cn33Package->bag->delivery_status,
                    'delivery_status_label' => __('api.statuses.'.$package->cn33Package->bag->delivery_status),
                    'delivered_at' => $package->cn33Package->bag->meta['delivered_at'] ?? null,
                ] : null,
                'manifest' => $package->cn33Package?->bag?->manifest ? [
                    'cn31_number' => $package->cn33Package->bag->manifest->cn31_number,
                    'status' => $package->cn33Package->bag->manifest->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($package->cn33Package->bag->manifest->status),
                    'delivery_status' => $package->cn33Package->bag->manifest->delivery_status,
                    'delivery_status_label' => __('api.statuses.'.$package->cn33Package->bag->manifest->delivery_status),
                    'delivered_at' => $package->cn33Package->bag->manifest->meta['delivered_at'] ?? null,
                ] : null,
            ],
        ]);
    }
}
