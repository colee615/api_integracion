<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsIntegrationResponses;
use App\Http\Controllers\Controller;
use App\Models\Cn31Manifest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Cn31ManifestController extends Controller
{
    use FormatsIntegrationResponses;

    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $manifests = Cn31Manifest::query()
            ->withCount('bags')
            ->where('company_id', $company->id)
            ->latest('dispatch_date')
            ->paginate(20);

        return response()->json($manifests);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $validator = Validator::make($request->all(), [
            'cn31_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cn31_manifests', 'cn31_number')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
            ],
            'origin_office' => ['required', 'string', 'max:150'],
            'destination_office' => ['required', 'string', 'max:150'],
            'dispatch_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'bags' => ['required', 'array', 'min:1'],
            'bags.*.bag_number' => [
                'required',
                'string',
                'max:100',
                'distinct',
                Rule::unique('cn31_bags', 'bag_number')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
            ],
            'bags.*.package_count' => ['required', 'integer', 'min:1'],
            'bags.*.total_weight_kg' => ['required', 'numeric', 'gt:0'],
            'bags.*.seal_number' => ['nullable', 'string', 'max:100'],
            'bags.*.dispatch_note' => ['nullable', 'string', 'max:255'],
        ], [
            'cn31_number.required' => __('api.validation.cn31_number_required'),
            'cn31_number.unique' => __('api.validation.cn31_number_unique'),
            'origin_office.required' => __('api.validation.origin_office_required'),
            'destination_office.required' => __('api.validation.destination_office_required'),
            'dispatch_date.required' => __('api.validation.dispatch_date_required'),
            'dispatch_date.date_format' => __('api.validation.dispatch_date_format'),
            'bags.required' => __('api.validation.bags_required'),
            'bags.array' => __('api.validation.bags_array'),
            'bags.*.bag_number.required' => __('api.validation.bag_number_required'),
            'bags.*.bag_number.distinct' => __('api.validation.bag_number_distinct'),
            'bags.*.bag_number.unique' => __('api.validation.bag_number_unique'),
            'bags.*.package_count.required' => __('api.validation.package_count_required'),
            'bags.*.package_count.integer' => __('api.validation.package_count_integer'),
            'bags.*.total_weight_kg.required' => __('api.validation.bag_total_weight_required'),
            'bags.*.total_weight_kg.numeric' => __('api.validation.bag_total_weight_numeric'),
            'bags.*.total_weight_kg.gt' => __('api.validation.bag_total_weight_gt'),
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator,
                'CN31_VALIDATION_ERROR',
                __('api.validation_messages.cn31_invalid')
            );
        }

        $validated = $validator->validated();
        $manifest = null;

        DB::transaction(function () use ($validated, $company, &$manifest): void {
            $manifest = Cn31Manifest::create([
                'company_id' => $company->id,
                'cn31_number' => $validated['cn31_number'],
                'origin_office' => $validated['origin_office'],
                'destination_office' => $validated['destination_office'],
                'dispatch_date' => $validated['dispatch_date'],
                'total_bags' => count($validated['bags']),
                'total_packages' => collect($validated['bags'])->sum('package_count'),
                'total_weight_kg' => collect($validated['bags'])->sum('total_weight_kg'),
                'status' => 'pendiente_cn33',
                'received_at' => now(),
                'meta' => [
                    'source' => 'api_cn31',
                ],
            ]);

            foreach ($validated['bags'] as $bag) {
                $manifest->bags()->create([
                    'company_id' => $company->id,
                    'bag_number' => $bag['bag_number'],
                    'declared_package_count' => $bag['package_count'],
                    'declared_weight_kg' => $bag['total_weight_kg'],
                    'status' => 'pendiente_cn33',
                    'received_at' => now(),
                    'meta' => [
                        'seal_number' => $bag['seal_number'] ?? null,
                        'dispatch_note' => $bag['dispatch_note'] ?? null,
                    ],
                ]);
            }
        });

        $manifest->load('bags');

        return response()->json([
            'success' => true,
            'message' => __('api.messages.cn31_received'),
            'data' => [
                'manifest_id' => $manifest->id,
                'cn31_number' => $manifest->cn31_number,
                'status' => $manifest->status,
                'total_bags' => $manifest->total_bags,
                'total_packages' => $manifest->total_packages,
                'total_weight_kg' => (float) $manifest->total_weight_kg,
                'bags' => $manifest->bags->map(fn ($bag) => [
                    'bag_id' => $bag->id,
                    'bag_number' => $bag->bag_number,
                    'declared_package_count' => $bag->declared_package_count,
                    'declared_weight_kg' => (float) $bag->declared_weight_kg,
                    'status' => $bag->status,
                ])->values(),
            ],
        ], 201);
    }

    public function show(Request $request, string $cn31Number): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $manifest = Cn31Manifest::query()
            ->with(['bags.cn33Packages'])
            ->where('company_id', $company->id)
            ->where('cn31_number', $cn31Number)
            ->first();

        if (! $manifest) {
            return $this->notFoundResponse(
                'CN31_NOT_FOUND',
                __('api.not_found.cn31')
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $manifest->id,
                'cn31_number' => $manifest->cn31_number,
                'origin_office' => $manifest->origin_office,
                'destination_office' => $manifest->destination_office,
                'dispatch_date' => $manifest->dispatch_date?->toIso8601String(),
                'status' => $manifest->status,
                'total_bags' => $manifest->total_bags,
                'total_packages' => $manifest->total_packages,
                'total_weight_kg' => (float) $manifest->total_weight_kg,
                'bags' => $manifest->bags->map(fn ($bag) => [
                    'id' => $bag->id,
                    'bag_number' => $bag->bag_number,
                    'declared_package_count' => $bag->declared_package_count,
                    'declared_weight_kg' => (float) $bag->declared_weight_kg,
                    'loaded_packages' => $bag->cn33Packages->count(),
                    'loaded_weight_kg' => (float) $bag->cn33Packages->sum('weight_kg'),
                    'documented_packages' => $bag->cn33Packages->where('status', 'documentado_cn22')->count(),
                    'status' => $bag->status,
                ])->values(),
            ],
        ]);
    }
}
