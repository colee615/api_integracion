<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Package;
use App\Services\WebhookNotifier;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalPackageDeliveryController extends Controller
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
            ->with(['company', 'cn33Package.bag.manifest'])
            ->where('tracking_code', $validated['tracking_code'])
            ->firstOrFail();

        $occurredAt = now()->parse($validated['occurred_at'] ?? now());
        $movement = null;

        DB::transaction(function () use ($package, $validated, $occurredAt, &$movement): void {
            $movement = $package->movements()->create([
                'company_id' => $package->company_id,
                'status' => 'entregado',
                'location' => $validated['location'] ?? null,
                'description' => $validated['description'] ?? __('api.messages.package_delivered_internal'),
                'occurred_at' => $occurredAt,
                'meta' => array_merge($validated['meta'] ?? [], [
                    'source' => 'internal_delivery_api',
                ]),
            ]);

            $package->forceFill([
                'status' => 'entregado',
                'last_movement_at' => $occurredAt,
                'meta' => array_merge($package->meta ?? [], [
                    'delivered_at' => $occurredAt->toIso8601String(),
                    'delivery_source' => 'internal_delivery_api',
                ]),
            ])->save();

            $cn33Package = $package->cn33Package;

            if (! $cn33Package) {
                return;
            }

            $cn33Package->forceFill([
                'status' => 'entregado',
                'meta' => array_merge($cn33Package->meta ?? [], [
                    'delivered_at' => $occurredAt->toIso8601String(),
                    'delivery_source' => 'internal_delivery_api',
                ]),
            ])->save();

            $bag = $cn33Package->bag?->fresh(['cn33Packages', 'manifest.bags']);

            if (! $bag) {
                return;
            }

            $allDelivered = $bag->cn33Packages->isNotEmpty()
                && $bag->cn33Packages->every(fn ($item) => $item->status === 'entregado');

            if ($allDelivered) {
                $bag->forceFill([
                    'status' => 'entregado',
                    'meta' => array_merge($bag->meta ?? [], [
                        'delivered_at' => $occurredAt->toIso8601String(),
                        'delivery_source' => 'internal_delivery_api',
                    ]),
                ])->save();
            }

            $manifest = $bag->manifest?->fresh(['bags']);

            if (! $manifest) {
                return;
            }

            $allBagsDelivered = $manifest->bags->isNotEmpty()
                && $manifest->bags->every(fn ($item) => $item->status === 'entregado');

            if ($allBagsDelivered) {
                $manifest->forceFill([
                    'status' => 'entregado',
                    'meta' => array_merge($manifest->meta ?? [], [
                        'delivered_at' => $occurredAt->toIso8601String(),
                        'delivery_source' => 'internal_delivery_api',
                    ]),
                ])->save();
            }
        });

        $notifier->dispatchForPackage($package->fresh('company'), 'entregado', [
            'location' => $movement->location,
            'description' => $movement->description,
            'movement_id' => $movement->id,
        ]);

        $package->load('cn33Package.bag.manifest');

        return response()->json([
            'message' => __('api.messages.package_delivered_success'),
            'data' => [
                'tracking_code' => $package->tracking_code,
                'status' => $package->status,
                'status_label' => PackageStatusCatalog::labelForStatus($package->status),
                'delivered_at' => $occurredAt->toIso8601String(),
                'bag' => $package->cn33Package?->bag ? [
                    'bag_number' => $package->cn33Package->bag->bag_number,
                    'status' => $package->cn33Package->bag->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($package->cn33Package->bag->status),
                    'delivered_at' => $package->cn33Package->bag->meta['delivered_at'] ?? null,
                ] : null,
                'manifest' => $package->cn33Package?->bag?->manifest ? [
                    'cn31_number' => $package->cn33Package->bag->manifest->cn31_number,
                    'status' => $package->cn33Package->bag->manifest->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($package->cn33Package->bag->manifest->status),
                    'delivered_at' => $package->cn33Package->bag->manifest->meta['delivered_at'] ?? null,
                ] : null,
            ],
        ]);
    }
}
