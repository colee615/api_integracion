<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Package;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->attributes->get('companyPortalUser');
        $company = $user->company;

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = trim($validated['q']);

        $buildManifestPayload = function (Cn31Manifest $manifest): array {
            return [
                'id' => $manifest->id,
                'cn31_number' => $manifest->cn31_number,
                'origin_office' => $manifest->origin_office,
                'destination_office' => $manifest->destination_office,
                'dispatch_date' => $manifest->dispatch_date?->toIso8601String(),
                'total_bags' => $manifest->total_bags,
                'total_packages' => $manifest->total_packages,
                'total_weight_kg' => (float) $manifest->total_weight_kg,
                'status' => $manifest->status,
                'delivered_at' => $manifest->meta['delivered_at'] ?? null,
                'bags' => $manifest->bags->map(function ($bag) {
                    return [
                        'id' => $bag->id,
                        'bag_number' => $bag->bag_number,
                        'dispatch_number_bag' => $bag->dispatch_number_bag,
                        'status' => $bag->status,
                        'delivered_at' => $bag->meta['delivered_at'] ?? null,
                        'declared_package_count' => (int) $bag->declared_package_count,
                        'declared_weight_kg' => (float) $bag->declared_weight_kg,
                        'loaded_package_count' => (int) ($bag->meta['loaded_package_count'] ?? $bag->cn33Packages->count()),
                        'loaded_weight_kg' => (float) ($bag->meta['loaded_weight_kg'] ?? $bag->cn33Packages->sum('weight_kg')),
                        'package_difference' => (int) ($bag->meta['package_difference'] ?? 0),
                        'weight_difference_kg' => (float) ($bag->meta['weight_difference_kg'] ?? 0),
                        'dispatch_note' => $bag->meta['dispatch_note'] ?? null,
                        'packages' => $bag->cn33Packages->map(function ($cn33Package) {
                            return [
                                'id' => $cn33Package->id,
                                'tracking_code' => $cn33Package->tracking_code,
                                'origin' => $cn33Package->origin,
                                'destination' => $cn33Package->destination,
                                'weight_kg' => (float) $cn33Package->weight_kg,
                                'status' => $cn33Package->status,
                                'delivered_at' => $cn33Package->meta['delivered_at'] ?? null,
                                'notes' => $cn33Package->meta['notes'] ?? null,
                                'package' => $cn33Package->package ? [
                                    'id' => $cn33Package->package->id,
                                    'tracking_code' => $cn33Package->package->tracking_code,
                                    'reference' => $cn33Package->package->reference,
                                    'status' => $cn33Package->package->status,
                                    'sender_name' => $cn33Package->package->sender_name,
                                    'sender_country' => $cn33Package->package->sender_country,
                                    'sender_address' => $cn33Package->package->sender_address,
                                    'sender_phone' => $cn33Package->package->sender_phone,
                                    'recipient_name' => $cn33Package->package->recipient_name,
                                    'recipient_document' => $cn33Package->package->recipient_document,
                                    'recipient_phone' => $cn33Package->package->recipient_phone,
                                    'recipient_whatsapp' => $cn33Package->package->recipient_whatsapp,
                                    'recipient_city' => $cn33Package->package->recipient_city,
                                    'recipient_department' => $cn33Package->package->recipient_department,
                                    'recipient_address' => $cn33Package->package->recipient_address,
                                    'recipient_address_reference' => $cn33Package->package->recipient_address_reference,
                                    'origin_office' => $cn33Package->package->origin_office,
                                    'destination_office' => $cn33Package->package->destination_office,
                                    'destination' => $cn33Package->package->destination,
                                    'shipment_description' => $cn33Package->package->shipment_description,
                                    'gross_weight_grams' => $cn33Package->package->gross_weight_grams,
                                    'weight_kg' => $cn33Package->package->weight_kg !== null ? (float) $cn33Package->package->weight_kg : null,
                                    'length_cm' => $cn33Package->package->length_cm !== null ? (float) $cn33Package->package->length_cm : null,
                                    'width_cm' => $cn33Package->package->width_cm !== null ? (float) $cn33Package->package->width_cm : null,
                                    'height_cm' => $cn33Package->package->height_cm !== null ? (float) $cn33Package->package->height_cm : null,
                                    'value_fob_usd' => $cn33Package->package->value_fob_usd !== null ? (float) $cn33Package->package->value_fob_usd : null,
                                    'currency_code' => $cn33Package->package->currency_code,
                                    'customs_items' => $cn33Package->package->customs_items ?? [],
                                    'registered_at' => $cn33Package->package->registered_at?->toIso8601String(),
                                    'last_movement_at' => $cn33Package->package->last_movement_at?->toIso8601String(),
                                    'delivery_attempts' => (int) $cn33Package->package->delivery_attempts,
                                    'last_delivery_attempt_at' => $cn33Package->package->last_delivery_attempt_at?->toIso8601String(),
                                    'last_delivery_attempt' => [
                                        'attempt' => (int) $cn33Package->package->delivery_attempts,
                                        'location' => $cn33Package->package->latestDeliveryAttemptLocation(),
                                        'description' => $cn33Package->package->latestDeliveryAttemptDescription(),
                                    ],
                                    'delivered_at' => $cn33Package->package->meta['delivered_at'] ?? null,
                                    'movements' => $cn33Package->package->movements->map(fn ($movement) => [
                                        'id' => $movement->id,
                                        'status' => $movement->status,
                                        'location' => $movement->location,
                                        'description' => $movement->description,
                                    ])->values(),
                                ] : null,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        };

        $packages = Package::query()
            ->with('movements')
            ->where('company_id', $company->id)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('tracking_code', 'ilike', "%{$query}%")
                    ->orWhere('reference', 'ilike', "%{$query}%")
                    ->orWhere('recipient_name', 'ilike', "%{$query}%")
                    ->orWhere('recipient_document', 'ilike', "%{$query}%")
                    ->orWhere('destination', 'ilike', "%{$query}%");
            })
            ->orderByRaw('CASE WHEN upper(tracking_code) = upper(?) THEN 0 ELSE 1 END', [$query])
            ->latest('registered_at')
            ->limit(100)
            ->get()
            ->map(fn ($package) => [
                'type' => 'package',
                'label' => $package->tracking_code,
                'subtitle' => trim(($package->sender_name ?? __('api.labels.no_data')).' | '.($package->recipient_name ?? __('api.labels.no_data'))),
                'meta' => trim(($package->origin_office ?? __('api.labels.no_data')).' -> '.($package->destination_office ?? $package->destination ?? __('api.labels.no_data'))),
                'status' => $package->status,
                'status_label' => PackageStatusCatalog::labelForStatus($package->status),
                'package' => [
                    'id' => $package->id,
                    'tracking_code' => $package->tracking_code,
                    'reference' => $package->reference,
                    'sender_name' => $package->sender_name,
                    'sender_country' => $package->sender_country,
                    'sender_address' => $package->sender_address,
                    'sender_phone' => $package->sender_phone,
                    'recipient_name' => $package->recipient_name,
                    'recipient_document' => $package->recipient_document,
                    'recipient_phone' => $package->recipient_phone,
                    'recipient_whatsapp' => $package->recipient_whatsapp,
                    'recipient_city' => $package->recipient_city,
                    'recipient_department' => $package->recipient_department,
                    'recipient_address' => $package->recipient_address,
                    'recipient_address_reference' => $package->recipient_address_reference,
                    'destination' => $package->destination,
                    'origin_office' => $package->origin_office,
                    'destination_office' => $package->destination_office,
                    'shipment_description' => $package->shipment_description,
                    'gross_weight_grams' => $package->gross_weight_grams,
                    'weight_kg' => $package->weight_kg !== null ? (float) $package->weight_kg : null,
                    'length_cm' => $package->length_cm !== null ? (float) $package->length_cm : null,
                    'width_cm' => $package->width_cm !== null ? (float) $package->width_cm : null,
                    'height_cm' => $package->height_cm !== null ? (float) $package->height_cm : null,
                    'value_fob_usd' => $package->value_fob_usd !== null ? (float) $package->value_fob_usd : null,
                    'currency_code' => $package->currency_code,
                    'status' => $package->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($package->status),
                    'manifest_number' => $package->meta['cn31_number'] ?? null,
                    'bag_number' => $package->meta['bag_number'] ?? null,
                    'dispatch_number_bag' => $package->meta['dispatch_number_bag'] ?? null,
                    'registered_at' => $package->registered_at?->toIso8601String(),
                    'last_movement_at' => $package->last_movement_at?->toIso8601String(),
                    'delivery_attempts' => (int) $package->delivery_attempts,
                    'last_delivery_attempt_at' => $package->last_delivery_attempt_at?->toIso8601String(),
                    'last_delivery_attempt' => [
                        'attempt' => (int) $package->delivery_attempts,
                        'location' => $package->latestDeliveryAttemptLocation(),
                        'description' => $package->latestDeliveryAttemptDescription(),
                    ],
                    'api_result' => $package->meta['api_result'] ?? [],
                    'delivered_at' => $package->meta['delivered_at'] ?? null,
                    'movements' => $package->movements->map(fn ($movement) => [
                        'id' => $movement->id,
                        'status' => $movement->status,
                        'status_label' => PackageStatusCatalog::labelForStatus($movement->status),
                        'location' => $movement->location,
                        'description' => $movement->description,
                    ])->values(),
                ],
            ]);

        $manifests = Cn31Manifest::query()
            ->with(['bags.cn33Packages.package.movements' => fn ($query) => $query->latest('occurred_at')])
            ->where('company_id', $company->id)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('cn31_number', 'ilike', "%{$query}%")
                    ->orWhere('origin_office', 'ilike', "%{$query}%")
                    ->orWhere('destination_office', 'ilike', "%{$query}%");
            })
            ->orderByRaw('CASE WHEN upper(cn31_number) = upper(?) THEN 0 ELSE 1 END', [$query])
            ->latest('dispatch_date')
            ->limit(50)
            ->get()
            ->map(fn ($manifest) => [
                'type' => 'manifest',
                'label' => $manifest->cn31_number,
                'subtitle' => trim(($manifest->origin_office ?? __('api.labels.no_data')).' -> '.($manifest->destination_office ?? __('api.labels.no_data'))),
                'meta' => "{$manifest->total_bags} sacas | {$manifest->total_packages} paquetes",
                'status' => $manifest->status,
                'status_label' => PackageStatusCatalog::labelForStatus($manifest->status),
                'manifest' => $buildManifestPayload($manifest),
            ]);

        $bags = Cn31Bag::query()
            ->with([
                'manifest',
                'cn33Packages.package.movements' => fn ($query) => $query->latest('occurred_at'),
            ])
            ->where('company_id', $company->id)
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('bag_number', 'ilike', "%{$query}%")
                    ->orWhere('dispatch_number_bag', 'ilike', "%{$query}%");
            })
            ->orderByRaw('CASE WHEN upper(coalesce(dispatch_number_bag, bag_number)) = upper(?) THEN 0 ELSE 1 END', [$query])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($bag) use ($buildManifestPayload) {
                $manifest = $bag->manifest;

                return [
                    'type' => 'bag',
                    'label' => $bag->dispatch_number_bag ?: $bag->bag_number,
                    'subtitle' => $bag->bag_number ?: 'Saca',
                    'meta' => ($manifest?->cn31_number ?: 'Sin CN31').' | '.((int) $bag->declared_package_count).' paquetes',
                    'status' => $bag->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($bag->status),
                    'selected_bag_id' => $bag->id,
                    'manifest' => $manifest ? $buildManifestPayload($manifest) : null,
                ];
            })
            ->filter(fn ($item) => $item['manifest'] !== null)
            ->values();

        $results = collect()
            ->concat($manifests)
            ->concat($bags)
            ->concat($packages)
            ->take(200)
            ->values();

        return response()->json([
            'data' => [
                'query' => $query,
                'total' => $packages->count(),
                'results' => $packages,
                'integration_results' => $results,
                'integration_total' => $results->count(),
            ],
        ]);
    }
}
