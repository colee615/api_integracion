<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsIntegrationResponses;
use App\Http\Controllers\Controller;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Models\Package;
use App\Support\PackageTrackingCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BulkIntegrationController extends Controller
{
    use FormatsIntegrationResponses;

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $validator = Validator::make($request->all(), [
            'manifest' => ['required', 'array'],
            'manifest.cn31_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cn31_manifests', 'cn31_number')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
            ],
            'manifest.origin_office' => ['required', 'string', 'max:150'],
            'manifest.destination_office' => ['required', 'string', 'max:150'],
            'manifest.dispatch_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'manifest.bags' => ['required', 'array', 'min:1'],
            'manifest.bags.*.bag_number' => [
                'required',
                'string',
                'max:100',
                'distinct',
                Rule::unique('cn31_bags', 'bag_number')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
            ],
            'manifest.bags.*.package_count' => ['required', 'integer', 'min:1'],
            'manifest.bags.*.total_weight_kg' => ['required', 'numeric', 'gt:0'],
            'manifest.bags.*.seal_number' => ['nullable', 'string', 'max:100'],
            'manifest.bags.*.dispatch_note' => ['nullable', 'string', 'max:255'],
            'manifest.bags.*.packages' => ['required', 'array', 'min:1'],
            'manifest.bags.*.packages.*.tracking_code' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! PackageTrackingCode::isValid((string) $value)) {
                        $fail(__('api.validation.tracking_code_format'));
                    }
                },
                Rule::unique('packages', 'tracking_code')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
                Rule::unique('cn33_packages', 'tracking_code')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
            ],
            'manifest.bags.*.packages.*.reference' => ['nullable', 'string', 'max:100'],
            'manifest.bags.*.packages.*.recipient_name' => ['required', 'string', 'max:255'],
            'manifest.bags.*.packages.*.destination' => ['required', 'string', 'max:255'],
            'manifest.bags.*.packages.*.weight_kg' => ['required', 'numeric', 'gt:0'],
            'manifest.bags.*.packages.*.notes' => ['nullable', 'string', 'max:255'],
            'manifest.bags.*.packages.*.cn22' => ['required', 'array'],
            'manifest.bags.*.packages.*.cn22.origin_office' => ['required', 'string', 'max:150'],
            'manifest.bags.*.packages.*.cn22.destination_office' => ['required', 'string', 'max:150'],
            'manifest.bags.*.packages.*.cn22.sender_name' => ['required', 'string', 'max:255'],
            'manifest.bags.*.packages.*.cn22.sender_country' => ['required', 'string', 'max:100'],
            'manifest.bags.*.packages.*.cn22.sender_address' => ['required', 'string', 'max:255'],
            'manifest.bags.*.packages.*.cn22.sender_phone' => ['required', 'string', 'max:50'],
            'manifest.bags.*.packages.*.cn22.recipient_name' => ['required', 'string', 'max:255'],
            'manifest.bags.*.packages.*.cn22.recipient_document' => ['required', 'string', 'max:100'],
            'manifest.bags.*.packages.*.cn22.recipient_address' => ['required', 'string', 'max:255'],
            'manifest.bags.*.packages.*.cn22.recipient_address_reference' => ['required', 'string', 'max:255'],
            'manifest.bags.*.packages.*.cn22.recipient_city' => ['required', 'string', 'max:100'],
            'manifest.bags.*.packages.*.cn22.recipient_department' => ['required', 'string', 'max:100'],
            'manifest.bags.*.packages.*.cn22.recipient_phone' => ['required', 'string', 'max:50'],
            'manifest.bags.*.packages.*.cn22.recipient_whatsapp' => ['nullable', 'string', 'max:50'],
            'manifest.bags.*.packages.*.cn22.description' => ['required', 'string', 'max:500'],
            'manifest.bags.*.packages.*.cn22.shipment_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'manifest.bags.*.packages.*.cn22.gross_weight_grams' => ['required', 'integer', 'min:1'],
            'manifest.bags.*.packages.*.cn22.length_cm' => ['required', 'numeric', 'gt:0'],
            'manifest.bags.*.packages.*.cn22.width_cm' => ['required', 'numeric', 'gt:0'],
            'manifest.bags.*.packages.*.cn22.height_cm' => ['required', 'numeric', 'gt:0'],
            'manifest.bags.*.packages.*.cn22.value_fob_usd' => ['nullable', 'numeric', 'gt:0'],
            'manifest.bags.*.packages.*.cn22.declared_amount' => ['nullable', 'numeric', 'gt:0'],
            'manifest.bags.*.packages.*.cn22.customs_items' => ['nullable', 'array'],
        ], [
            'manifest.required' => __('api.validation.bulk_manifest_required'),
            'manifest.array' => __('api.validation.bulk_manifest_array'),
            'manifest.cn31_number.required' => __('api.validation.cn31_number_required'),
            'manifest.cn31_number.unique' => __('api.validation.cn31_number_unique'),
            'manifest.origin_office.required' => __('api.validation.origin_office_required'),
            'manifest.destination_office.required' => __('api.validation.destination_office_required'),
            'manifest.dispatch_date.required' => __('api.validation.dispatch_date_required'),
            'manifest.dispatch_date.date_format' => __('api.validation.dispatch_date_format'),
            'manifest.bags.required' => __('api.validation.bags_required'),
            'manifest.bags.array' => __('api.validation.bags_array'),
            'manifest.bags.*.bag_number.required' => __('api.validation.bag_number_required'),
            'manifest.bags.*.bag_number.distinct' => __('api.validation.bag_number_distinct'),
            'manifest.bags.*.bag_number.unique' => __('api.validation.bag_number_unique'),
            'manifest.bags.*.package_count.required' => __('api.validation.package_count_required'),
            'manifest.bags.*.package_count.integer' => __('api.validation.package_count_integer'),
            'manifest.bags.*.total_weight_kg.required' => __('api.validation.bag_total_weight_required'),
            'manifest.bags.*.total_weight_kg.numeric' => __('api.validation.bag_total_weight_numeric'),
            'manifest.bags.*.total_weight_kg.gt' => __('api.validation.bag_total_weight_gt'),
            'manifest.bags.*.packages.required' => __('api.validation.bulk_packages_required'),
            'manifest.bags.*.packages.array' => __('api.validation.packages_array'),
            'manifest.bags.*.packages.*.tracking_code.required' => __('api.validation.cn33_tracking_required'),
            'manifest.bags.*.packages.*.recipient_name.required' => __('api.validation.cn33_recipient_required'),
            'manifest.bags.*.packages.*.destination.required' => __('api.validation.cn33_destination_required'),
            'manifest.bags.*.packages.*.weight_kg.required' => __('api.validation.cn33_weight_required'),
            'manifest.bags.*.packages.*.weight_kg.numeric' => __('api.validation.cn33_weight_numeric'),
            'manifest.bags.*.packages.*.weight_kg.gt' => __('api.validation.cn33_weight_gt'),
            'manifest.bags.*.packages.*.cn22.required' => __('api.validation.bulk_cn22_required'),
            'manifest.bags.*.packages.*.cn22.origin_office.required' => __('api.validation.origin_office_required'),
            'manifest.bags.*.packages.*.cn22.destination_office.required' => __('api.validation.destination_office_required'),
            'manifest.bags.*.packages.*.cn22.sender_name.required' => __('api.validation.sender_name_required'),
            'manifest.bags.*.packages.*.cn22.sender_country.required' => __('api.validation.sender_country_required'),
            'manifest.bags.*.packages.*.cn22.sender_address.required' => __('api.validation.sender_address_required'),
            'manifest.bags.*.packages.*.cn22.sender_phone.required' => __('api.validation.sender_phone_required'),
            'manifest.bags.*.packages.*.cn22.recipient_name.required' => __('api.validation.recipient_name_required'),
            'manifest.bags.*.packages.*.cn22.recipient_document.required' => __('api.validation.recipient_document_required'),
            'manifest.bags.*.packages.*.cn22.recipient_address.required' => __('api.validation.recipient_address_required'),
            'manifest.bags.*.packages.*.cn22.recipient_address_reference.required' => __('api.validation.recipient_address_reference_required'),
            'manifest.bags.*.packages.*.cn22.recipient_city.required' => __('api.validation.recipient_city_required'),
            'manifest.bags.*.packages.*.cn22.recipient_department.required' => __('api.validation.recipient_department_required'),
            'manifest.bags.*.packages.*.cn22.recipient_phone.required' => __('api.validation.recipient_phone_required'),
            'manifest.bags.*.packages.*.cn22.description.required' => __('api.validation.description_required'),
            'manifest.bags.*.packages.*.cn22.shipment_date.required' => __('api.validation.shipment_date_required'),
            'manifest.bags.*.packages.*.cn22.shipment_date.date_format' => __('api.validation.shipment_date_format'),
            'manifest.bags.*.packages.*.cn22.gross_weight_grams.required' => __('api.validation.gross_weight_required'),
            'manifest.bags.*.packages.*.cn22.gross_weight_grams.integer' => __('api.validation.gross_weight_integer'),
            'manifest.bags.*.packages.*.cn22.gross_weight_grams.min' => __('api.validation.gross_weight_min'),
            'manifest.bags.*.packages.*.cn22.length_cm.required' => __('api.validation.length_required'),
            'manifest.bags.*.packages.*.cn22.width_cm.required' => __('api.validation.width_required'),
            'manifest.bags.*.packages.*.cn22.height_cm.required' => __('api.validation.height_required'),
        ]);

        $validator->after(function ($validator) use ($request): void {
            $trackingCodes = [];

            foreach ((array) data_get($request->all(), 'manifest.bags', []) as $bagIndex => $bag) {
                $declaredCount = (int) ($bag['package_count'] ?? 0);
                $loadedCount = count((array) ($bag['packages'] ?? []));

                if ($declaredCount > 0 && $loadedCount !== $declaredCount) {
                    $validator->errors()->add(
                        "manifest.bags.$bagIndex.packages",
                        __('api.validation.bulk_bag_package_count_mismatch')
                    );
                }

                foreach ((array) ($bag['packages'] ?? []) as $packageIndex => $package) {
                    $trackingCode = (string) ($package['tracking_code'] ?? '');

                    if ($trackingCode !== '') {
                        if (in_array($trackingCode, $trackingCodes, true)) {
                            $validator->errors()->add(
                                "manifest.bags.$bagIndex.packages.$packageIndex.tracking_code",
                                __('api.validation.tracking_distinct')
                            );
                        }

                        $trackingCodes[] = $trackingCode;
                    }

                    if (! isset($package['cn22']['value_fob_usd']) && ! isset($package['cn22']['declared_amount'])) {
                        $validator->errors()->add(
                            "manifest.bags.$bagIndex.packages.$packageIndex.cn22.value_fob_usd",
                            __('api.validation.value_fob_required')
                        );
                    }
                }
            }
        });

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator,
                'BULK_INTEGRATION_VALIDATION_ERROR',
                __('api.validation_messages.bulk_invalid')
            );
        }

        $validated = $validator->validated();
        $bagsPayload = $validated['manifest']['bags'];
        $recordCount = collect($bagsPayload)->sum(fn ($bag) => count($bag['packages']));

        if ($company->environment === 'sandbox' && $company->sandbox_shipments_used + $recordCount > $company->sandbox_max_shipments) {
            return response()->json([
                'success' => false,
                'code' => 'SANDBOX_SHIPMENT_LIMIT_REACHED',
                'message' => __('api.errors.sandbox_limit_reached'),
            ], 422);
        }

        $result = DB::transaction(function () use ($company, $validated, $bagsPayload, $recordCount): array {
            $manifestData = $validated['manifest'];

            $manifest = Cn31Manifest::create([
                'company_id' => $company->id,
                'cn31_number' => $manifestData['cn31_number'],
                'origin_office' => $manifestData['origin_office'],
                'destination_office' => $manifestData['destination_office'],
                'dispatch_date' => $manifestData['dispatch_date'],
                'total_bags' => count($bagsPayload),
                'total_packages' => $recordCount,
                'total_weight_kg' => collect($bagsPayload)->sum('total_weight_kg'),
                'status' => 'pendiente_cn33',
                'received_at' => now(),
                'meta' => [
                    'source' => 'api_bulk_integration',
                    'bulk_payload' => $validated,
                ],
            ]);

            $createdBags = collect();
            $createdPackages = collect();

            foreach ($bagsPayload as $bagIndex => $bagPayload) {
                $bag = $manifest->bags()->create([
                    'company_id' => $company->id,
                    'bag_number' => $bagPayload['bag_number'],
                    'declared_package_count' => $bagPayload['package_count'],
                    'declared_weight_kg' => $bagPayload['total_weight_kg'],
                    'status' => 'pendiente_cn33',
                    'received_at' => now(),
                    'meta' => [
                        'seal_number' => $bagPayload['seal_number'] ?? null,
                        'dispatch_note' => $bagPayload['dispatch_note'] ?? null,
                    ],
                ]);

                foreach ($bagPayload['packages'] as $packageIndex => $packagePayload) {
                    $cn22 = $packagePayload['cn22'];
                    $registeredAt = $cn22['shipment_date'];
                    $grossWeightGrams = (int) $cn22['gross_weight_grams'];
                    $weightKg = $packagePayload['weight_kg'] ?? $cn22['weight_kg'] ?? round($grossWeightGrams / 1000, 3);
                    $valueFobUsd = $cn22['value_fob_usd'] ?? $cn22['declared_amount'] ?? null;

                    $package = Package::create([
                        'company_id' => $company->id,
                        'tracking_code' => $packagePayload['tracking_code'],
                        'reference' => $packagePayload['reference'] ?? null,
                        'sender_name' => $cn22['sender_name'],
                        'sender_country' => $cn22['sender_country'],
                        'sender_address' => $cn22['sender_address'],
                        'sender_phone' => $cn22['sender_phone'],
                        'recipient_name' => $cn22['recipient_name'],
                        'recipient_document' => $cn22['recipient_document'],
                        'recipient_phone' => $cn22['recipient_phone'],
                        'recipient_whatsapp' => $cn22['recipient_whatsapp'] ?? $cn22['recipient_phone'],
                        'recipient_city' => $cn22['recipient_city'],
                        'recipient_department' => $cn22['recipient_department'],
                        'recipient_address_reference' => $cn22['recipient_address_reference'],
                        'destination' => $packagePayload['destination'] ?? $cn22['destination_office'],
                        'origin_office' => $cn22['origin_office'],
                        'destination_office' => $cn22['destination_office'],
                        'recipient_address' => $cn22['recipient_address'],
                        'shipment_description' => $cn22['description'],
                        'shipment_date' => $registeredAt,
                        'gross_weight_grams' => $grossWeightGrams,
                        'weight_kg' => $weightKg,
                        'length_cm' => $cn22['length_cm'],
                        'width_cm' => $cn22['width_cm'],
                        'height_cm' => $cn22['height_cm'],
                        'value_fob_usd' => $valueFobUsd,
                        'currency_code' => 'USD',
                        'pre_alert_at' => $registeredAt,
                        'tracking_standard' => PackageTrackingCode::detectStandard($packagePayload['tracking_code']),
                        'customs_items' => $cn22['customs_items'] ?? [],
                        'status' => 'pre_alerta_recibida',
                        'registered_at' => $registeredAt,
                        'last_movement_at' => $registeredAt,
                        'meta' => [
                            'integration_type' => 'bulk_cn31_cn33_cn22',
                            'cn31_number' => $manifest->cn31_number,
                            'bag_number' => $bag->bag_number,
                            'sender' => [
                                'name' => $cn22['sender_name'],
                                'country' => $cn22['sender_country'],
                                'address' => $cn22['sender_address'],
                                'phone' => $cn22['sender_phone'],
                            ],
                            'recipient' => [
                                'address' => $cn22['recipient_address'],
                                'address_reference' => $cn22['recipient_address_reference'],
                                'city' => $cn22['recipient_city'],
                                'department' => $cn22['recipient_department'],
                                'phone' => $cn22['recipient_phone'],
                                'whatsapp' => $cn22['recipient_whatsapp'] ?? $cn22['recipient_phone'],
                            ],
                            'shipment_description' => $cn22['description'],
                            'gross_weight_grams' => $grossWeightGrams,
                            'weight_kg' => $weightKg,
                            'value_fob_usd' => $valueFobUsd,
                            'dimensions_cm' => [
                                'length' => (float) $cn22['length_cm'],
                                'width' => (float) $cn22['width_cm'],
                                'height' => (float) $cn22['height_cm'],
                            ],
                            'customs_items' => $cn22['customs_items'] ?? [],
                            'api_result' => [
                                'received' => true,
                                'message' => __('api.messages.cn22_record_received'),
                                'linked_to_cn33' => true,
                                'bulk_mode' => true,
                            ],
                        ],
                    ]);

                    Cn33Package::create([
                        'company_id' => $company->id,
                        'cn31_bag_id' => $bag->id,
                        'package_id' => $package->id,
                        'tracking_code' => $packagePayload['tracking_code'],
                        'reference' => $packagePayload['reference'] ?? null,
                        'recipient_name' => $packagePayload['recipient_name'],
                        'destination' => $packagePayload['destination'],
                        'weight_kg' => $packagePayload['weight_kg'],
                        'item_order' => $packageIndex + 1,
                        'status' => 'documentado_cn22',
                        'meta' => [
                            'notes' => $packagePayload['notes'] ?? null,
                            'source' => 'api_bulk_cn33',
                            'cn22_received_at' => $registeredAt,
                        ],
                    ]);

                    $package->movements()->create([
                        'company_id' => $company->id,
                        'status' => 'pre_alerta_recibida',
                        'location' => $cn22['origin_office'],
                        'description' => __('api.messages.cn22_pre_alert_received'),
                        'occurred_at' => $registeredAt,
                        'meta' => [
                            'source' => 'api_bulk_cn22',
                        ],
                    ]);

                    $createdPackages->push([
                        'tracking_code' => $package->tracking_code,
                        'bag_number' => $bag->bag_number,
                        'status' => $package->status,
                    ]);
                }

                $this->reconcileBag($bag->fresh(['cn33Packages', 'manifest.bags.cn33Packages']));

                $createdBags->push($bag->fresh(['cn33Packages']));
            }

            $company->registerSandboxShipment($recordCount);

            $manifest = $manifest->fresh(['bags.cn33Packages']);

            return [
                'manifest' => $manifest,
                'bags' => $createdBags,
                'packages' => $createdPackages,
            ];
        });

        /** @var Cn31Manifest $manifest */
        $manifest = $result['manifest'];

        return response()->json([
            'success' => true,
            'message' => __('api.messages.bulk_received'),
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
                    'status' => $bag->status,
                    'declared_package_count' => $bag->declared_package_count,
                    'loaded_package_count' => $bag->cn33Packages->count(),
                    'documented_packages' => $bag->cn33Packages->where('status', 'documentado_cn22')->count(),
                ])->values(),
                'packages' => $result['packages']->values(),
            ],
        ], 201);
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
