<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsIntegrationResponses;
use App\Http\Controllers\Controller;
use App\Models\Cn31Manifest;
use App\Support\ApiTokenContext;
use App\Support\DeliverySummary;
use App\Support\PackageStatusCatalog;
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
        $apiToken = ApiTokenContext::currentToken($request);

        $validator = Validator::make($request->query(), [
            'dispatch_number_manifest' => ['sometimes', 'nullable', 'string', 'max:10', 'regex:/^[0-9]{10}$/'],
            'dispatch_date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'dispatch_date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'received_at_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'received_at_to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            foreach ([['dispatch_date_from', 'dispatch_date_to'], ['received_at_from', 'received_at_to']] as [$from, $to]) {
                $fromDate = $request->query($from);
                $toDate = $request->query($to);

                if (is_string($fromDate) && is_string($toDate) && $fromDate > $toDate) {
                    $validator->errors()->add($to, __('api.validation.date_range_invalid'));
                }
            }
        });

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator,
                'CN31_FILTER_VALIDATION_ERROR',
                __('api.validation_messages.cn31_filters_invalid')
            );
        }

        $filters = $validator->validated();

        $manifests = ApiTokenContext::scopeToToken(
            Cn31Manifest::query()->withCount('bags')->where('company_id', $company->id),
            $apiToken
        )
            ->when($filters['dispatch_number_manifest'] ?? null, fn ($query, $number) => $query->where('dispatch_number_manifest', $number))
            ->when($filters['dispatch_date_from'] ?? null, fn ($query, $date) => $query->whereDate('dispatch_date', '>=', $date))
            ->when($filters['dispatch_date_to'] ?? null, fn ($query, $date) => $query->whereDate('dispatch_date', '<=', $date))
            ->when($filters['received_at_from'] ?? null, fn ($query, $date) => $query->whereDate('received_at', '>=', $date))
            ->when($filters['received_at_to'] ?? null, fn ($query, $date) => $query->whereDate('received_at', '<=', $date))
            ->orderByDesc('dispatch_date')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        $manifests->getCollection()->transform(fn (Cn31Manifest $manifest): array => [
            'cn31_number' => $manifest->cn31_number,
            'dispatch_number_manifest' => $manifest->dispatch_number_manifest,
            'origin_office' => $manifest->origin_office,
            'destination_office' => $manifest->destination_office,
            'dispatch_date' => $manifest->dispatch_date?->utc()->toIso8601String(),
            'received_at' => $manifest->received_at?->utc()->toIso8601String(),
            'status' => $manifest->status,
            'status_label' => PackageStatusCatalog::labelForStatus($manifest->status),
            'delivery_status' => $manifest->delivery_status,
            'delivery_status_label' => __('api.statuses.'.$manifest->delivery_status),
            'total_bags' => (int) $manifest->total_bags,
            'total_packages' => (int) $manifest->total_packages,
            'total_weight_kg' => (float) $manifest->total_weight_kg,
            'bags_count' => (int) $manifest->bags_count,
        ]);

        return response()->json($manifests);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $validator = Validator::make($request->all(), [
            'cn31_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cn31_manifests', 'cn31_number')->where(
                    fn ($query) => ApiTokenContext::applyCompanyScope($query, $company->id, $apiToken)
                ),
            ],
            'dispatch_number_manifest' => [
                'nullable',
                'regex:/^[0-9]{10}$/',
                Rule::unique('cn31_manifests', 'dispatch_number_manifest')->where(
                    fn ($query) => ApiTokenContext::applyCompanyScope($query, $company->id, $apiToken)
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
                    fn ($query) => ApiTokenContext::applyCompanyScope($query, $company->id, $apiToken)
                ),
            ],
            'bags.*.dispatch_number_bag' => [
                'nullable',
                'string',
                'max:30',
                'distinct',
                Rule::unique('cn31_bags', 'dispatch_number_bag')->where(
                    fn ($query) => ApiTokenContext::applyCompanyScope($query, $company->id, $apiToken)
                ),
            ],
            'bags.*.package_count' => ['required', 'integer', 'min:1'],
            'bags.*.total_weight_kg' => ['required', 'numeric', 'gt:0'],
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
            'bags.*.dispatch_number_bag.distinct' => __('api.validation.dispatch_number_bag_distinct'),
            'bags.*.dispatch_number_bag.unique' => __('api.validation.dispatch_number_bag_unique'),
            'dispatch_number_manifest.regex' => __('api.validation.dispatch_number_manifest_format'),
            'dispatch_number_manifest.unique' => __('api.validation.dispatch_number_manifest_unique'),
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

        DB::transaction(function () use ($validated, $company, $apiToken, &$manifest): void {
            $manifest = Cn31Manifest::create([
                'company_id' => $company->id,
                ...ApiTokenContext::ownershipAttributes($apiToken),
                'cn31_number' => $validated['cn31_number'],
                'dispatch_number_manifest' => $validated['dispatch_number_manifest'] ?? null,
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
                    ...ApiTokenContext::ownershipAttributes($apiToken),
                    'bag_number' => $bag['bag_number'],
                    'dispatch_number_bag' => $bag['dispatch_number_bag'] ?? null,
                    'declared_package_count' => $bag['package_count'],
                    'declared_weight_kg' => $bag['total_weight_kg'],
                    'status' => 'pendiente_cn33',
                    'received_at' => now(),
                    'meta' => [
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
                'dispatch_number_manifest' => $manifest->dispatch_number_manifest,
                'status' => $manifest->status,
                'status_label' => PackageStatusCatalog::labelForStatus($manifest->status),
                'delivery_status' => $manifest->delivery_status,
                'delivery_status_label' => __('api.statuses.'.$manifest->delivery_status),
                'total_bags' => $manifest->total_bags,
                'total_packages' => $manifest->total_packages,
                'total_weight_kg' => (float) $manifest->total_weight_kg,
                'bags' => $manifest->bags->map(fn ($bag) => [
                    'bag_id' => $bag->id,
                    'bag_number' => $bag->bag_number,
                    'dispatch_number_bag' => $bag->dispatch_number_bag,
                    'declared_package_count' => $bag->declared_package_count,
                    'declared_weight_kg' => (float) $bag->declared_weight_kg,
                    'status' => $bag->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($bag->status),
                    'operational_status' => $bag->operational_status,
                    'operational_status_label' => __('api.statuses.'.$bag->operational_status),
                    'delivery_status' => $bag->delivery_status,
                    'delivery_status_label' => __('api.statuses.'.$bag->delivery_status),
                ])->values(),
            ],
        ], 201);
    }

    public function show(Request $request, string $cn31Number): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $manifest = ApiTokenContext::scopeToToken(
            Cn31Manifest::query()->with(['bags.cn33Packages', 'bags.movements'])->where('company_id', $company->id),
            $apiToken
        )
            ->where('cn31_number', $cn31Number)
            ->first();

        if (! $manifest) {
            return $this->notFoundResponse(
                'CN31_NOT_FOUND',
                __('api.not_found.cn31')
            );
        }

        $bags = $manifest->bags;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $manifest->id,
                'cn31_number' => $manifest->cn31_number,
                'dispatch_number_manifest' => $manifest->dispatch_number_manifest,
                'origin_office' => $manifest->origin_office,
                'destination_office' => $manifest->destination_office,
                'dispatch_date' => $manifest->dispatch_date?->toIso8601String(),
                'status' => $manifest->status,
                'status_label' => PackageStatusCatalog::labelForStatus($manifest->status),
                'delivery_status' => $manifest->delivery_status,
                'delivery_status_label' => __('api.statuses.'.$manifest->delivery_status),
                'total_bags' => $manifest->total_bags,
                'total_packages' => $manifest->total_packages,
                'total_weight_kg' => (float) $manifest->total_weight_kg,
                'delivery_summary' => DeliverySummary::forDispatch($bags),
                'bags' => $bags->map(fn ($bag) => [
                    'id' => $bag->id,
                    'bag_number' => $bag->bag_number,
                    'dispatch_number_bag' => $bag->dispatch_number_bag,
                    'declared_package_count' => $bag->declared_package_count,
                    'declared_weight_kg' => (float) $bag->declared_weight_kg,
                    'loaded_packages' => $bag->cn33Packages->count(),
                    'loaded_weight_kg' => (float) $bag->cn33Packages->sum('weight_kg'),
                    'documented_packages' => $bag->cn33Packages->where('status', 'documentado_cn22')->count(),
                    'status' => $bag->status,
                    'status_label' => PackageStatusCatalog::labelForStatus($bag->status),
                    'operational_status' => $bag->operational_status,
                    'operational_status_label' => __('api.statuses.'.$bag->operational_status),
                    'delivery_status' => $bag->delivery_status,
                    'delivery_status_label' => __('api.statuses.'.$bag->delivery_status),
                    'delivery_summary' => DeliverySummary::forPackageRows($bag->cn33Packages),
                    'events' => $bag->movements->reverse()->values()->map(fn ($event) => [
                        'event_id' => $event->event_id,
                        'event_code' => $event->event_code,
                        'ips_operation_code' => $event->ips_operation_code,
                        'ips_event_type_cd' => $event->ips_event_type_cd,
                        'status' => $event->status,
                        'location_code' => $event->location_code,
                        'location' => $event->location,
                        'occurred_at' => $event->occurred_at?->utc()->toIso8601String(),
                        'recorded_at' => $event->recorded_at?->utc()->toIso8601String(),
                    ])->values(),
                ])->values(),
            ],
        ]);
    }
}
