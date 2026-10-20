<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\TrackingEventWriter;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalPackageDeliveryAttemptController extends Controller
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
            ->with(['company', 'cn33Package'])
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
            'status' => 'intentos_carteros',
            'meta' => array_merge($validated['meta'] ?? [], ['source' => 'internal_delivery_attempt_api']),
        ], 'internal_api', $idempotencyKey);

        if ($result['conflict']) {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'message' => __('api.errors.idempotency_conflict'),
            ], 409);
        }

        $package = $package->fresh();
        $movement = $result['movement'];
        return response()->json([
            'message' => __('api.messages.package_delivery_attempt_success'),
            'data' => [
                'tracking_code' => $package->tracking_code,
                'status' => $package->status,
                'status_label' => PackageStatusCatalog::labelForStatus($package->status),
                'delivery_attempts' => (int) $package->delivery_attempts,
                'last_delivery_attempt_at' => $package->last_delivery_attempt_at?->utc()->toIso8601String(),
                'last_delivery_attempt' => [
                    'attempt' => (int) $package->delivery_attempts,
                    'location' => $package->latestDeliveryAttemptLocation(),
                    'reason_code' => $package->latestDeliveryAttemptReasonCode(),
                    'description' => $package->latestDeliveryAttemptDescription(),
                ],
                'event_id' => $movement->event_id,
                'replayed' => $result['replayed'],
                'late_event' => $result['late'],
            ],
        ], $result['replayed'] ? 200 : 201);
    }
}
