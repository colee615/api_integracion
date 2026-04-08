<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Models\Package;
use App\Models\PackageMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->attributes->get('companyPortalUser');
        $company = $user->company;

        $packagesCount = Package::where('company_id', $company->id)->count();
        $movementsCount = PackageMovement::where('company_id', $company->id)->count();
        $manifestsCount = Cn31Manifest::where('company_id', $company->id)->count();
        $bagsCount = Cn31Bag::where('company_id', $company->id)->count();
        $cn33PackagesCount = Cn33Package::where('company_id', $company->id)->count();

        $legacyTokens = ApiToken::query()
            ->where('company_id', $company->id)
            ->latest()
            ->get();

        $bagStatusCounts = Cn31Bag::query()
            ->where('company_id', $company->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $documentedPackagesCount = Cn33Package::query()
            ->where('company_id', $company->id)
            ->where('status', 'documentado_cn22')
            ->count();

        $pendingCn22Count = max($cn33PackagesCount - $documentedPackagesCount, 0);
        $observedBagsCount = (int) ($bagStatusCounts['observado'] ?? 0);
        $reconciledBagsCount = (int) ($bagStatusCounts['conciliado'] ?? 0);
        $pendingCn33BagsCount = (int) ($bagStatusCounts['pendiente_cn33'] ?? 0);
        $processedWeightKg = round((float) Cn31Manifest::where('company_id', $company->id)->sum('total_weight_kg'), 3);
        $loadedWeightKg = round((float) Cn31Bag::query()->where('company_id', $company->id)->get()->sum(fn ($bag) => (float) ($bag->meta['loaded_weight_kg'] ?? 0)), 3);
        $documentationCoverage = $cn33PackagesCount > 0
            ? round(($documentedPackagesCount / $cn33PackagesCount) * 100, 1)
            : 0.0;
        $bagReconciliationRate = $bagsCount > 0
            ? round(($reconciledBagsCount / $bagsCount) * 100, 1)
            : 0.0;
        $manifestReceptionRate = $manifestsCount > 0 && $bagsCount > 0
            ? 100.0
            : ($manifestsCount > 0 ? 50.0 : 0.0);

        $alerts = collect();

        $topDestinations = Package::query()
            ->where('company_id', $company->id)
            ->whereNotNull('destination')
            ->select('destination', DB::raw('COUNT(*) as total'))
            ->groupBy('destination')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'destination' => $row->destination,
                'total' => (int) $row->total,
            ])
            ->values();

        $lastApiReceptionAt = Package::query()
            ->where('company_id', $company->id)
            ->max('registered_at');

        $lastManifestAt = Cn31Manifest::query()
            ->where('company_id', $company->id)
            ->max('dispatch_date');

        if ($legacyTokens->filter(fn ($token) => $token->canUse())->isEmpty()) {
            $alerts->push([
                'level' => 'warning',
                'title' => 'Sin credencial activa',
                'description' => 'No existe ningun token activo para consumir las APIs de integracion.',
            ]);
        }

        if ($observedBagsCount > 0) {
            $alerts->push([
                'level' => 'danger',
                'title' => 'Sacas observadas',
                'description' => "Tienes {$observedBagsCount} saca(s) con diferencia entre lo declarado y lo cargado.",
            ]);
        }

        if ($pendingCn22Count > 0) {
            $alerts->push([
                'level' => 'info',
                'title' => 'CN22 pendiente',
                'description' => "Existen {$pendingCn22Count} paquete(s) declarados en CN33 que aun no tienen CN22.",
            ]);
        }

        return response()->json([
            'data' => [
                'summary' => [
                    'packages' => $packagesCount,
                    'movements' => $movementsCount,
                    'tokens' => $legacyTokens->count(),
                    'active_tokens' => $legacyTokens->filter(fn ($token) => $token->canUse())->count(),
                    'legacy_tokens' => $legacyTokens->count(),
                    'active_legacy_tokens' => $legacyTokens->filter(fn ($token) => $token->canUse())->count(),
                    'manifests' => $manifestsCount,
                    'bags' => $bagsCount,
                    'cn33_packages' => $cn33PackagesCount,
                    'documented_packages' => $documentedPackagesCount,
                    'pending_cn22_packages' => $pendingCn22Count,
                    'observed_bags' => $observedBagsCount,
                    'reconciled_bags' => $reconciledBagsCount,
                    'pending_cn33_bags' => $pendingCn33BagsCount,
                    'processed_weight_kg' => $processedWeightKg,
                    'loaded_weight_kg' => $loadedWeightKg,
                ],
                'analytics' => [
                    'documentation_coverage_pct' => $documentationCoverage,
                    'bag_reconciliation_pct' => $bagReconciliationRate,
                    'manifest_reception_pct' => $manifestReceptionRate,
                    'status_breakdown' => [
                        'observed_bags' => $observedBagsCount,
                        'reconciled_bags' => $reconciledBagsCount,
                        'pending_cn33_bags' => $pendingCn33BagsCount,
                        'documented_packages' => $documentedPackagesCount,
                        'pending_cn22_packages' => $pendingCn22Count,
                    ],
                ],
                'insights' => [
                    'top_destinations' => $topDestinations,
                    'last_api_reception_at' => $lastApiReceptionAt,
                    'last_manifest_at' => $lastManifestAt,
                ],
                'alerts' => $alerts->values(),
                'recent_manifests' => Cn31Manifest::query()
                    ->where('company_id', $company->id)
                    ->latest('dispatch_date')
                    ->limit(8)
                    ->get()
                    ->map(fn ($manifest) => [
                        'id' => $manifest->id,
                        'cn31_number' => $manifest->cn31_number,
                        'origin_office' => $manifest->origin_office,
                        'destination_office' => $manifest->destination_office,
                        'dispatch_date' => $manifest->dispatch_date?->toIso8601String(),
                        'total_bags' => $manifest->total_bags,
                        'total_packages' => $manifest->total_packages,
                        'total_weight_kg' => (float) $manifest->total_weight_kg,
                        'status' => $manifest->status,
                    ])
                    ->values(),
                'recent_bags' => Cn31Bag::query()
                    ->with(['manifest:id,cn31_number'])
                    ->where('company_id', $company->id)
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn ($bag) => [
                        'id' => $bag->id,
                        'bag_number' => $bag->bag_number,
                        'manifest_number' => $bag->manifest?->cn31_number,
                        'declared_package_count' => $bag->declared_package_count,
                        'declared_weight_kg' => (float) $bag->declared_weight_kg,
                        'loaded_package_count' => (int) ($bag->meta['loaded_package_count'] ?? 0),
                        'loaded_weight_kg' => (float) ($bag->meta['loaded_weight_kg'] ?? 0),
                        'documented_packages' => Cn33Package::where('cn31_bag_id', $bag->id)->where('status', 'documentado_cn22')->count(),
                        'status' => $bag->status,
                        'received_at' => $bag->received_at?->toIso8601String(),
                    ])
                    ->values(),
                'recent_movements' => PackageMovement::query()
                    ->with('package:id,tracking_code')
                    ->where('company_id', $company->id)
                    ->latest('occurred_at')
                    ->limit(8)
                    ->get()
                    ->map(fn ($movement) => [
                        'id' => $movement->id,
                        'status' => $movement->status,
                        'location' => $movement->location,
                        'description' => $movement->description,
                        'occurred_at' => $movement->occurred_at?->toIso8601String(),
                        'tracking_code' => $movement->package?->tracking_code,
                    ])
                    ->values(),
                'recent_packages' => Package::query()
                    ->where('company_id', $company->id)
                    ->latest('registered_at')
                    ->limit(10)
                    ->get()
                    ->map(fn ($package) => [
                        'id' => $package->id,
                        'tracking_code' => $package->tracking_code,
                        'reference' => $package->reference,
                        'recipient_name' => $package->recipient_name,
                        'destination' => $package->destination,
                        'status' => $package->status,
                        'manifest_number' => $package->meta['cn31_number'] ?? null,
                        'bag_number' => $package->meta['bag_number'] ?? null,
                        'api_result' => $package->meta['api_result'] ?? [],
                        'registered_at' => $package->registered_at?->toIso8601String(),
                        'last_movement_at' => $package->last_movement_at?->toIso8601String(),
                    ])
                    ->values(),
                'recent_bulk_integrations' => Cn31Manifest::query()
                    ->with([
                        'bags.cn33Packages.package.movements' => fn ($query) => $query->latest('occurred_at'),
                    ])
                    ->where('company_id', $company->id)
                    ->where('meta->source', 'api_bulk_integration')
                    ->latest('dispatch_date')
                    ->limit(10)
                    ->get()
                    ->map(fn ($manifest) => [
                        'id' => $manifest->id,
                        'cn31_number' => $manifest->cn31_number,
                        'origin_office' => $manifest->origin_office,
                        'destination_office' => $manifest->destination_office,
                        'dispatch_date' => $manifest->dispatch_date?->toIso8601String(),
                        'total_bags' => $manifest->total_bags,
                        'total_packages' => $manifest->total_packages,
                        'total_weight_kg' => (float) $manifest->total_weight_kg,
                        'status' => $manifest->status,
                        'bags' => $manifest->bags->map(fn ($bag) => [
                            'id' => $bag->id,
                            'bag_number' => $bag->bag_number,
                            'status' => $bag->status,
                            'declared_package_count' => (int) $bag->declared_package_count,
                            'declared_weight_kg' => (float) $bag->declared_weight_kg,
                            'loaded_package_count' => (int) ($bag->meta['loaded_package_count'] ?? $bag->cn33Packages->count()),
                            'loaded_weight_kg' => (float) ($bag->meta['loaded_weight_kg'] ?? $bag->cn33Packages->sum('weight_kg')),
                            'package_difference' => (int) ($bag->meta['package_difference'] ?? 0),
                            'weight_difference_kg' => (float) ($bag->meta['weight_difference_kg'] ?? 0),
                            'seal_number' => $bag->meta['seal_number'] ?? null,
                            'dispatch_note' => $bag->meta['dispatch_note'] ?? null,
                            'received_at' => $bag->received_at?->toIso8601String(),
                            'packages' => $bag->cn33Packages->map(fn ($cn33Package) => [
                                'id' => $cn33Package->id,
                                'tracking_code' => $cn33Package->tracking_code,
                                'reference' => $cn33Package->reference,
                                'recipient_name' => $cn33Package->recipient_name,
                                'destination' => $cn33Package->destination,
                                'weight_kg' => (float) $cn33Package->weight_kg,
                                'status' => $cn33Package->status,
                                'notes' => $cn33Package->meta['notes'] ?? null,
                                'cn22_received_at' => $cn33Package->meta['cn22_received_at'] ?? null,
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
                                    'shipment_description' => $cn33Package->package->shipment_description,
                                    'shipment_date' => $cn33Package->package->shipment_date?->toIso8601String(),
                                    'gross_weight_grams' => $cn33Package->package->gross_weight_grams,
                                    'weight_kg' => (float) $cn33Package->package->weight_kg,
                                    'length_cm' => (float) $cn33Package->package->length_cm,
                                    'width_cm' => (float) $cn33Package->package->width_cm,
                                    'height_cm' => (float) $cn33Package->package->height_cm,
                                    'value_fob_usd' => $cn33Package->package->value_fob_usd !== null
                                        ? (float) $cn33Package->package->value_fob_usd
                                        : null,
                                    'currency_code' => $cn33Package->package->currency_code,
                                    'customs_items' => $cn33Package->package->customs_items ?? [],
                                    'registered_at' => $cn33Package->package->registered_at?->toIso8601String(),
                                    'last_movement_at' => $cn33Package->package->last_movement_at?->toIso8601String(),
                                    'movements' => $cn33Package->package->movements->map(fn ($movement) => [
                                        'id' => $movement->id,
                                        'status' => $movement->status,
                                        'location' => $movement->location,
                                        'description' => $movement->description,
                                        'occurred_at' => $movement->occurred_at?->toIso8601String(),
                                    ])->values(),
                                ] : null,
                            ])->values(),
                        ])->values(),
                    ])
                    ->values(),
                'tokens' => [
                    'legacy_tokens' => $legacyTokens->map(fn ($token) => [
                        'id' => $token->id,
                        'name' => $token->name,
                        'token_value' => $token->token_secret,
                        'token_masked' => $token->maskedToken(),
                        'starts_at' => $token->starts_at?->toIso8601String(),
                        'expires_at' => $token->expires_at?->toIso8601String(),
                        'last_used_at' => $token->last_used_at?->toIso8601String(),
                        'status' => $token->revoked_at ? 'revocado' : ($token->isExpired() ? 'expirado' : (! $token->hasStarted() ? 'programado' : 'activo')),
                    ])->values(),
                ],
            ],
        ]);
    }
}
