<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\WebhookNotifier;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalPackageDeliveryAttemptController extends Controller
{
    public function __invoke(Request $request, WebhookNotifier $notifier): JsonResponse
    {
        $validated = $request->validate([
            'tracking_code' => ['required', 'string', 'max:100'],
            'occurred_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
        ]);

        $package = Package::query()
            ->with(['company', 'cn33Package'])
            ->where('tracking_code', $validated['tracking_code'])
            ->first();

        if (! $package) {
            return response()->json([
                'message' => __('api.not_found.package', [
                    'tracking_code' => $validated['tracking_code'],
                ]),
            ], 404);
        }

        $occurredAt = now()->parse($validated['occurred_at'] ?? now());
        $movement = null;

        DB::transaction(function () use ($package, $validated, $occurredAt, &$movement): void {
            $attemptNumber = ((int) $package->delivery_attempts) + 1;
            $description = $validated['description'] ?? __('api.messages.package_delivery_attempt_internal', ['attempt' => $attemptNumber]);

            $movement = $package->movements()->create([
                'company_id' => $package->company_id,
                'status' => 'intentos_carteros',
                'location' => $validated['location'] ?? null,
                'description' => $description,
                'occurred_at' => $occurredAt,
                'meta' => array_merge($validated['meta'] ?? [], [
                    'source' => 'internal_delivery_attempt_api',
                    'delivery_attempt' => $attemptNumber,
                ]),
            ]);

            $packageMeta = array_merge($package->meta ?? [], [
                'last_delivery_attempt' => [
                    'attempt' => $attemptNumber,
                    'occurred_at' => $occurredAt->toIso8601String(),
                    'location' => $validated['location'] ?? null,
                    'description' => $description,
                ],
            ]);

            $package->forceFill([
                'status' => $package->status === 'entregado' ? 'entregado' : 'intentos_carteros',
                'delivery_attempts' => $attemptNumber,
                'last_delivery_attempt_at' => $occurredAt,
                'last_movement_at' => $occurredAt,
                'meta' => $packageMeta,
            ])->save();

            if ($package->cn33Package && $package->cn33Package->status !== 'entregado') {
                $package->cn33Package->forceFill([
                    'status' => 'intentos_carteros',
                    'meta' => array_merge($package->cn33Package->meta ?? [], [
                        'delivery_attempts' => $attemptNumber,
                        'last_delivery_attempt_at' => $occurredAt->toIso8601String(),
                    ]),
                ])->save();
            }
        });

        $notifier->dispatchForPackage($package->fresh('company'), 'intentos_carteros', [
            'location' => $movement->location,
            'description' => $movement->description,
            'movement_id' => $movement->id,
            'delivery_attempt' => $package->fresh()->delivery_attempts,
        ]);

        $package->refresh();

        return response()->json([
            'message' => __('api.messages.package_delivery_attempt_success'),
            'data' => [
                'tracking_code' => $package->tracking_code,
                'status' => $package->status,
                'status_label' => PackageStatusCatalog::labelForStatus($package->status),
                'delivery_attempts' => (int) $package->delivery_attempts,
                'last_delivery_attempt_at' => $package->last_delivery_attempt_at?->toIso8601String(),
                'last_delivery_attempt' => [
                    'attempt' => (int) $package->delivery_attempts,
                    'location' => $package->latestDeliveryAttemptLocation(),
                    'description' => $package->latestDeliveryAttemptDescription(),
                ],
            ],
        ]);
    }
}
