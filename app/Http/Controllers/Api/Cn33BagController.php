<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsIntegrationResponses;
use App\Http\Controllers\Controller;
use App\Models\Cn31Bag;
use App\Models\Cn33Package;
use App\Models\Package;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Cn33BagController extends Controller
{
    use FormatsIntegrationResponses;

    public function store(Request $request, string $bagReference): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $bag = Cn31Bag::query()
            ->with('manifest')
            ->where('company_id', $company->id)
            ->where(function ($query) use ($bagReference) {
                $query->where('dispatch_number_bag', $bagReference)
                    ->orWhere('bag_number', $bagReference);
            })
            ->first();

        if (! $bag) {
            return $this->notFoundResponse(
                'CN33_BAG_NOT_FOUND',
                __('api.not_found.cn33_bag_register')
            );
        }

        $validator = Validator::make($request->all(), [
            'packages' => ['required', 'array', 'min:1'],
            'packages.*.tracking_code' => [
                'required',
                'string',
                'max:100',
                'distinct',
                Rule::unique('cn33_packages', 'tracking_code')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
            ],
            'packages.*.origin' => ['required', 'string', 'max:255'],
            'packages.*.destination' => ['required', 'string', 'max:255'],
            'packages.*.weight_kg' => ['required', 'numeric', 'gt:0'],
            'packages.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'packages.required' => __('api.validation.packages_required'),
            'packages.array' => __('api.validation.packages_array'),
            'packages.*.tracking_code.required' => __('api.validation.cn33_tracking_required'),
            'packages.*.tracking_code.distinct' => __('api.validation.cn33_tracking_distinct'),
            'packages.*.tracking_code.unique' => __('api.validation.cn33_tracking_unique'),
            'packages.*.origin.required' => __('api.validation.origin_office_required'),
            'packages.*.destination.required' => __('api.validation.cn33_destination_required'),
            'packages.*.weight_kg.required' => __('api.validation.cn33_weight_required'),
            'packages.*.weight_kg.numeric' => __('api.validation.cn33_weight_numeric'),
            'packages.*.weight_kg.gt' => __('api.validation.cn33_weight_gt'),
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator,
                'CN33_VALIDATION_ERROR',
                __('api.validation_messages.cn33_invalid')
            );
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated, $company, $bag): void {
            foreach ($validated['packages'] as $index => $record) {
                $package = Package::query()
                    ->where('company_id', $company->id)
                    ->where('tracking_code', $record['tracking_code'])
                    ->first();

                if ($package) {
                    $package->forceFill([
                        'meta' => array_merge($package->meta ?? [], [
                            'cn31_number' => $bag->manifest?->cn31_number,
                            'bag_number' => $bag->bag_number,
                            'dispatch_number_bag' => $bag->dispatch_number_bag,
                        ]),
                    ])->save();
                }

                Cn33Package::create([
                    'company_id' => $company->id,
                    'cn31_bag_id' => $bag->id,
                    'package_id' => $package?->id,
                    'tracking_code' => $record['tracking_code'],
                    'reference' => null,
                    'recipient_name' => null,
                    'origin' => $record['origin'],
                    'destination' => $record['destination'],
                    'weight_kg' => $record['weight_kg'],
                    'item_order' => $index + 1,
                    'status' => $package ? 'documentado_cn22' : 'pendiente_cn22',
                    'meta' => [
                        'notes' => $record['notes'] ?? null,
                        'source' => 'api_cn33',
                    ],
                ]);
            }
        });

        $this->reconcileBag($bag->fresh(['cn33Packages', 'manifest.bags.cn33Packages']));

        $bag = $bag->fresh(['cn33Packages', 'manifest']);
        $loadedPackages = $bag->cn33Packages->count();
        $loadedWeight = (float) $bag->cn33Packages->sum('weight_kg');
        $documentedPackages = $bag->cn33Packages->where('status', 'documentado_cn22')->count();

        return response()->json([
            'success' => true,
            'message' => __('api.messages.cn33_received'),
            'data' => [
                'bag_id' => $bag->id,
                'bag_number' => $bag->bag_number,
                'dispatch_number_bag' => $bag->dispatch_number_bag,
                'manifest_number' => $bag->manifest?->cn31_number,
                'status' => $bag->status,
                'status_label' => PackageStatusCatalog::labelForStatus($bag->status),
                'declared_package_count' => $bag->declared_package_count,
                'loaded_package_count' => $loadedPackages,
                'declared_weight_kg' => (float) $bag->declared_weight_kg,
                'loaded_weight_kg' => $loadedWeight,
                'documented_packages' => $documentedPackages,
                'pending_packages' => max($loadedPackages - $documentedPackages, 0),
            ],
        ], 201);
    }

    public function show(Request $request, string $bagReference): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $bag = Cn31Bag::query()
            ->with(['manifest', 'cn33Packages.package'])
            ->where('company_id', $company->id)
            ->where(function ($query) use ($bagReference) {
                $query->where('dispatch_number_bag', $bagReference)
                    ->orWhere('bag_number', $bagReference);
            })
            ->first();

        if (! $bag) {
            return $this->notFoundResponse(
                'CN33_BAG_NOT_FOUND',
                __('api.not_found.cn33_bag')
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bag->id,
                'bag_number' => $bag->bag_number,
                'dispatch_number_bag' => $bag->dispatch_number_bag,
                'manifest_number' => $bag->manifest?->cn31_number,
                'declared_package_count' => $bag->declared_package_count,
                'declared_weight_kg' => (float) $bag->declared_weight_kg,
                'status' => $bag->status,
                'status_label' => PackageStatusCatalog::labelForStatus($bag->status),
                'packages' => $bag->cn33Packages->map(fn ($item) => [
                    'id' => $item->id,
                    'tracking_code' => $item->tracking_code,
                    'origin' => $item->origin,
                    'destination' => $item->destination,
                    'weight_kg' => $item->weight_kg !== null ? (float) $item->weight_kg : null,
                    'status' => $item->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($item->status),
                    'package_registered' => $item->package_id !== null,
                ])->values(),
            ],
        ]);
    }

    private function reconcileBag(Cn31Bag $bag): void
    {
        $loadedCount = $bag->cn33Packages->count();
        $loadedWeight = round((float) $bag->cn33Packages->sum('weight_kg'), 3);
        $declaredWeight = round((float) $bag->declared_weight_kg, 3);

        $hasPackageMismatch = $loadedCount !== (int) $bag->declared_package_count;
        $hasWeightMismatch = abs($loadedWeight - $declaredWeight) > 0.001;

        $bag->forceFill([
            'status' => ($hasPackageMismatch || $hasWeightMismatch) ? 'observado' : 'conciliado',
            'meta' => array_merge($bag->meta ?? [], [
                'loaded_package_count' => $loadedCount,
                'loaded_weight_kg' => $loadedWeight,
                'package_difference' => $loadedCount - (int) $bag->declared_package_count,
                'weight_difference_kg' => round($loadedWeight - $declaredWeight, 3),
            ]),
        ])->save();

        $manifest = $bag->manifest->fresh(['bags.cn33Packages']);

        $statuses = $manifest->bags->pluck('status');
        $manifestStatus = 'pendiente_cn33';

        if ($statuses->isNotEmpty() && $statuses->every(fn ($status) => in_array($status, ['conciliado', 'observado'], true))) {
            $manifestStatus = $statuses->contains('observado') ? 'observado' : 'conciliado';
        }

        $manifest->forceFill([
            'status' => $manifestStatus,
        ])->save();
    }
}
