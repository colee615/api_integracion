<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsIntegrationResponses;
use App\Http\Controllers\Controller;
use App\Models\Cn33Package;
use App\Models\Package;
use App\Support\PackageTrackingCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Cn22ShipmentController extends Controller
{
    use FormatsIntegrationResponses;

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $validator = Validator::make($request->all(), [
            'records' => ['required', 'array', 'min:1'],
            'records.*.tracking_code' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! PackageTrackingCode::isValid((string) $value)) {
                        $fail(__('api.validation.tracking_code_format'));
                    }
                },
                'distinct',
                Rule::unique('packages', 'tracking_code')->where(
                    fn ($query) => $query->where('company_id', $company->id)
                ),
            ],
            'records.*.reference' => ['nullable', 'string', 'max:100'],
            'records.*.origin_office' => ['required', 'string', 'max:150'],
            'records.*.destination_office' => ['required', 'string', 'max:150'],
            'records.*.sender_name' => ['required', 'string', 'max:255'],
            'records.*.sender_country' => ['required', 'string', 'max:100'],
            'records.*.sender_address' => ['required', 'string', 'max:255'],
            'records.*.sender_phone' => ['required', 'string', 'max:50'],
            'records.*.recipient_name' => ['required', 'string', 'max:255'],
            'records.*.recipient_document' => ['required', 'string', 'max:100'],
            'records.*.recipient_address' => ['required', 'string', 'max:255'],
            'records.*.recipient_address_reference' => ['required', 'string', 'max:255'],
            'records.*.recipient_city' => ['required', 'string', 'max:100'],
            'records.*.recipient_department' => ['required', 'string', 'max:100'],
            'records.*.recipient_phone' => ['required', 'string', 'max:50'],
            'records.*.recipient_whatsapp' => ['nullable', 'string', 'max:50'],
            'records.*.destination' => ['nullable', 'string', 'max:255'],
            'records.*.description' => ['required', 'string', 'max:500'],
            'records.*.shipment_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'records.*.gross_weight_grams' => ['required', 'integer', 'min:1'],
            'records.*.weight_kg' => ['nullable', 'numeric', 'gt:0'],
            'records.*.length_cm' => ['required', 'numeric', 'gt:0'],
            'records.*.width_cm' => ['required', 'numeric', 'gt:0'],
            'records.*.height_cm' => ['required', 'numeric', 'gt:0'],
            'records.*.value_fob_usd' => ['nullable', 'numeric', 'gt:0'],
            'records.*.declared_amount' => ['nullable', 'numeric', 'gt:0'],
            'records.*.customs_items' => ['nullable', 'array'],
            'records.*.customs_items.*.description' => ['required_with:records.*.customs_items', 'string', 'max:255'],
            'records.*.customs_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'records.*.customs_items.*.value' => ['nullable', 'numeric', 'min:0'],
            'records.*.customs_items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'records.*.customs_items.*.hs_code' => ['nullable', 'string', 'max:50'],
            'records.*.customs_items.*.origin_country' => ['nullable', 'string', 'max:100'],
        ], [
            'records.required' => __('api.validation.records_required'),
            'records.array' => __('api.validation.records_array'),
            'records.min' => __('api.validation.records_min'),
            'records.*.tracking_code.required' => __('api.validation.tracking_required'),
            'records.*.tracking_code.distinct' => __('api.validation.tracking_distinct'),
            'records.*.tracking_code.unique' => __('api.validation.tracking_unique'),
            'records.*.origin_office.required' => __('api.validation.origin_office_required'),
            'records.*.destination_office.required' => __('api.validation.destination_office_required'),
            'records.*.sender_name.required' => __('api.validation.sender_name_required'),
            'records.*.sender_country.required' => __('api.validation.sender_country_required'),
            'records.*.sender_address.required' => __('api.validation.sender_address_required'),
            'records.*.sender_phone.required' => __('api.validation.sender_phone_required'),
            'records.*.recipient_name.required' => __('api.validation.recipient_name_required'),
            'records.*.recipient_document.required' => __('api.validation.recipient_document_required'),
            'records.*.recipient_address.required' => __('api.validation.recipient_address_required'),
            'records.*.recipient_address_reference.required' => __('api.validation.recipient_address_reference_required'),
            'records.*.recipient_city.required' => __('api.validation.recipient_city_required'),
            'records.*.recipient_department.required' => __('api.validation.recipient_department_required'),
            'records.*.recipient_phone.required' => __('api.validation.recipient_phone_required'),
            'records.*.description.required' => __('api.validation.description_required'),
            'records.*.shipment_date.required' => __('api.validation.shipment_date_required'),
            'records.*.shipment_date.date_format' => __('api.validation.shipment_date_format'),
            'records.*.gross_weight_grams.required' => __('api.validation.gross_weight_required'),
            'records.*.gross_weight_grams.integer' => __('api.validation.gross_weight_integer'),
            'records.*.gross_weight_grams.min' => __('api.validation.gross_weight_min'),
            'records.*.length_cm.required' => __('api.validation.length_required'),
            'records.*.width_cm.required' => __('api.validation.width_required'),
            'records.*.height_cm.required' => __('api.validation.height_required'),
            'records.*.value_fob_usd.gt' => __('api.validation.value_fob_gt'),
            'records.*.weight_kg.numeric' => __('api.validation.weight_numeric'),
            'records.*.weight_kg.gt' => __('api.validation.weight_gt'),
            'records.*.customs_items.array' => __('api.validation.customs_items_array'),
            'records.*.customs_items.*.description.required_with' => __('api.validation.customs_item_description_required'),
        ], [
            'records.*.tracking_code' => 'codigo de rastreo',
            'records.*.origin_office' => 'oficina de origen',
            'records.*.destination_office' => 'oficina de destino',
            'records.*.sender_name' => 'nombre remitente',
            'records.*.sender_country' => 'pais remitente',
            'records.*.sender_address' => 'direccion remitente',
            'records.*.sender_phone' => 'telefono remitente',
            'records.*.recipient_name' => 'nombre destinatario',
            'records.*.recipient_document' => 'documento destinatario',
            'records.*.recipient_address' => 'direccion destinatario',
            'records.*.recipient_address_reference' => 'referencias direccion',
            'records.*.recipient_city' => 'ciudad destinatario',
            'records.*.recipient_department' => 'departamento destinatario',
            'records.*.recipient_phone' => 'telefono destinatario',
            'records.*.description' => 'descripcion',
            'records.*.shipment_date' => 'fecha y hora',
            'records.*.gross_weight_grams' => 'peso bruto',
            'records.*.weight_kg' => 'peso',
        ]);

        $validator->after(function ($validator) use ($request): void {
            foreach ((array) $request->input('records', []) as $index => $record) {
                if (! isset($record['value_fob_usd']) && ! isset($record['declared_amount'])) {
                    $validator->errors()->add(
                        "records.$index.value_fob_usd",
                        __('api.validation.value_fob_required')
                    );
                }
            }
        });

        if ($validator->fails()) {
            return $this->validationErrorResponse(
                $validator,
                'CN22_VALIDATION_ERROR',
                __('api.validation_messages.cn22_invalid')
            );
        }

        $validated = $validator->validated();
        $recordCount = count($validated['records']);

        if ($company->environment === 'sandbox' && $company->sandbox_shipments_used + $recordCount > $company->sandbox_max_shipments) {
            return response()->json([
                'success' => false,
                'code' => 'SANDBOX_SHIPMENT_LIMIT_REACHED',
                'message' => __('api.errors.sandbox_limit_reached'),
            ], 422);
        }

        $createdPackages = collect();

        DB::transaction(function () use ($validated, $company, $createdPackages, $recordCount): void {
            foreach ($validated['records'] as $record) {
                $registeredAt = $record['shipment_date'] ?? now();
                $grossWeightGrams = (int) $record['gross_weight_grams'];
                $weightKg = $record['weight_kg'] ?? round($grossWeightGrams / 1000, 3);
                $valueFobUsd = $record['value_fob_usd'] ?? $record['declared_amount'] ?? null;
                $cn33Item = Cn33Package::query()
                    ->with(['bag.manifest'])
                    ->where('company_id', $company->id)
                    ->where('tracking_code', $record['tracking_code'])
                    ->first();

                $package = Package::create([
                    'company_id' => $company->id,
                    'tracking_code' => $record['tracking_code'],
                    'reference' => $record['reference'] ?? null,
                    'sender_name' => $record['sender_name'],
                    'sender_country' => $record['sender_country'],
                    'sender_address' => $record['sender_address'],
                    'sender_phone' => $record['sender_phone'],
                    'recipient_name' => $record['recipient_name'],
                    'recipient_document' => $record['recipient_document'],
                    'recipient_phone' => $record['recipient_phone'],
                    'recipient_whatsapp' => $record['recipient_whatsapp'] ?? $record['recipient_phone'],
                    'recipient_city' => $record['recipient_city'],
                    'recipient_department' => $record['recipient_department'],
                    'recipient_address_reference' => $record['recipient_address_reference'],
                    'destination' => $record['destination'] ?? $record['destination_office'],
                    'origin_office' => $record['origin_office'],
                    'destination_office' => $record['destination_office'],
                    'recipient_address' => $record['recipient_address'],
                    'shipment_description' => $record['description'],
                    'shipment_date' => $registeredAt,
                    'gross_weight_grams' => $grossWeightGrams,
                    'weight_kg' => $weightKg,
                    'length_cm' => $record['length_cm'],
                    'width_cm' => $record['width_cm'],
                    'height_cm' => $record['height_cm'],
                    'value_fob_usd' => $valueFobUsd,
                    'currency_code' => 'USD',
                    'pre_alert_at' => $registeredAt,
                    'tracking_standard' => PackageTrackingCode::detectStandard($record['tracking_code']),
                    'customs_items' => $record['customs_items'] ?? [],
                    'status' => 'pre_alerta_recibida',
                    'registered_at' => $registeredAt,
                    'last_movement_at' => $registeredAt,
                    'meta' => [
                        'integration_type' => 'cn22',
                        'cn31_number' => $cn33Item?->bag?->manifest?->cn31_number,
                        'bag_number' => $cn33Item?->bag?->bag_number,
                        'sender' => [
                            'name' => $record['sender_name'],
                            'country' => $record['sender_country'],
                            'address' => $record['sender_address'],
                            'phone' => $record['sender_phone'],
                        ],
                        'recipient' => [
                            'address' => $record['recipient_address'],
                            'address_reference' => $record['recipient_address_reference'],
                            'city' => $record['recipient_city'],
                            'department' => $record['recipient_department'],
                            'phone' => $record['recipient_phone'],
                            'whatsapp' => $record['recipient_whatsapp'] ?? $record['recipient_phone'],
                        ],
                        'shipment_description' => $record['description'],
                        'gross_weight_grams' => $grossWeightGrams,
                        'weight_kg' => $weightKg,
                        'value_fob_usd' => $valueFobUsd,
                        'dimensions_cm' => [
                            'length' => (float) $record['length_cm'],
                            'width' => (float) $record['width_cm'],
                            'height' => (float) $record['height_cm'],
                        ],
                        'customs_items' => $record['customs_items'] ?? [],
                        'api_result' => [
                            'received' => true,
                            'message' => __('api.messages.cn22_record_received'),
                            'linked_to_cn33' => $cn33Item !== null,
                        ],
                    ],
                ]);

                if ($cn33Item) {
                    $cn33Item->forceFill([
                        'package_id' => $package->id,
                        'status' => 'documentado_cn22',
                        'meta' => array_merge($cn33Item->meta ?? [], [
                            'cn22_received_at' => $registeredAt,
                        ]),
                    ])->save();
                }

                $package->movements()->create([
                    'company_id' => $company->id,
                    'status' => 'pre_alerta_recibida',
                    'location' => $record['origin_office'],
                    'description' => __('api.messages.cn22_pre_alert_received'),
                    'occurred_at' => $registeredAt,
                    'meta' => [
                        'source' => 'api_cn22',
                    ],
                ]);

                $createdPackages->push($package->fresh('movements'));
            }

            $company->registerSandboxShipment($recordCount);
        });

        return response()->json([
            'success' => true,
            'message' => __('api.messages.cn22_received'),
            'data' => [
                'received' => $createdPackages->count(),
                'results' => $createdPackages->map(fn (Package $package) => [
                    'package_id' => $package->id,
                    'tracking_code' => $package->tracking_code,
                    'status' => $package->status,
                    'received_at' => $package->registered_at?->toIso8601String(),
                    'tracking_standard' => $package->tracking_standard,
                    'message' => __('api.messages.cn22_record_received'),
                ])->values(),
            ],
        ], 201);
    }
}
