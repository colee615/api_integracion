<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\ApiTokenContext;
use App\Support\PackageStatusCatalog;
use App\Support\PackageTrackingCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $packages = ApiTokenContext::scopeToToken(
            Package::query()->with('cn33Package')->where('company_id', $company->id),
            $apiToken
        )
            ->when($request->string('tracking_code')->isNotEmpty(), function ($query) use ($request) {
                $query->where('tracking_code', $request->string('tracking_code'));
            })
            ->latest()
            ->paginate(20);

        $packages->getCollection()->transform(fn (Package $package): array => [
            'tracking_code' => $package->tracking_code,
            'reference' => $package->reference,
            'origin' => $package->cn33Package?->origin,
            'destination' => $package->destination,
            'status' => $package->status,
            'status_label' => PackageStatusCatalog::labelForStatus($package->status),
            'registered_at' => $package->registered_at?->utc()->toIso8601String(),
            'last_movement_at' => $package->last_movement_at?->utc()->toIso8601String(),
        ]);

        return response()->json($packages);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $validated = $request->validate([
            'tracking_code' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! PackageTrackingCode::isValid((string) $value)) {
                        $fail(__('api.validation.tracking_code_format'));
                    }
                },
                Rule::unique('packages', 'tracking_code')->where(
                    fn ($query) => ApiTokenContext::applyCompanyScope($query, $company->id, $apiToken)
                ),
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_country' => ['nullable', 'string', 'max:100'],
            'sender_address' => ['nullable', 'string', 'max:255'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_document' => ['nullable', 'string', 'max:100'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'recipient_whatsapp' => ['nullable', 'string', 'max:50'],
            'recipient_city' => ['nullable', 'string', 'max:100'],
            'recipient_department' => ['nullable', 'string', 'max:100'],
            'recipient_address_reference' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'origin_office' => ['nullable', 'string', 'max:150'],
            'destination_office' => ['nullable', 'string', 'max:150'],
            'recipient_address' => ['nullable', 'string', 'max:1000'],
            'shipment_description' => ['nullable', 'string', 'max:1000'],
            'shipment_date' => ['nullable', 'date'],
            'gross_weight_grams' => ['nullable', 'integer', 'min:1'],
            'weight_kg' => ['nullable', 'numeric', 'gt:0'],
            'length_cm' => ['nullable', 'numeric', 'gt:0'],
            'width_cm' => ['nullable', 'numeric', 'gt:0'],
            'height_cm' => ['nullable', 'numeric', 'gt:0'],
            'value_fob_usd' => ['nullable', 'numeric', 'gt:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'pre_alert_at' => ['nullable', 'date'],
            'tracking_standard' => ['nullable', 'string', 'max:40'],
            'customs_items' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['registrado', 'pre_alerta_recibida', 'recibido_api'])],
            'registered_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $validated['tracking_standard'] = $validated['tracking_standard'] ?? PackageTrackingCode::detectStandard($validated['tracking_code']);
        $validated['recipient_whatsapp'] = $validated['recipient_whatsapp'] ?? ($validated['recipient_phone'] ?? null);
        $validated['weight_kg'] = $validated['weight_kg'] ?? (isset($validated['gross_weight_grams']) ? round($validated['gross_weight_grams'] / 1000, 3) : null);

        [$package, $movement] = DB::transaction(function () use ($validated, $company, $apiToken): array {
            $registeredAt = $validated['registered_at'] ?? now();
            $initialStatus = PackageStatusCatalog::normalize($validated['status'] ?? 'registrado');
            $package = Package::create([
                ...$validated,
                'company_id' => $company->id,
                ...ApiTokenContext::ownershipAttributes($apiToken),
                'status' => $initialStatus,
                'registered_at' => $registeredAt,
                'last_movement_at' => $registeredAt,
            ]);

            $movement = $package->movements()->create([
                'company_id' => $company->id,
                ...ApiTokenContext::ownershipAttributes($apiToken),
                'event_code' => PackageStatusCatalog::eventForStatus($initialStatus),
                'source_type' => 'company_api',
                'status' => $initialStatus,
                'description' => PackageStatusCatalog::labelForStatus($initialStatus),
                'occurred_at' => $registeredAt,
                'recorded_at' => now()->utc(),
                'meta' => ['source' => 'package_registration'],
            ]);

            return [$package, $movement];
        });

        return response()->json([
            'message' => __('api.messages.package_registered'),
            'data' => $this->transformPackage($package->load(['movements', 'cn33Package'])),
        ], 201);
    }

    public function show(Request $request, string $trackingCode): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $package = ApiTokenContext::scopeToToken(
            Package::query()
                ->with([
                    'cn33Package',
                    'movements' => fn ($query) => $query->where('is_public', true)->reorder('occurred_at', 'desc')->limit(20),
                ])
                ->withCount(['movements as public_movements_count' => fn ($query) => $query->where('is_public', true)])
                ->where('company_id', $company->id),
            $apiToken
        )
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();

        return response()->json([
            'data' => $this->transformPackage($package),
        ]);
    }

    private function transformPackage(Package $package): array
    {
        return [
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
            'recipient_address_reference' => $package->recipient_address_reference,
            'origin' => $package->cn33Package?->origin,
            'destination' => $package->destination,
            'origin_office' => $package->origin_office,
            'destination_office' => $package->destination_office,
            'recipient_address' => $package->recipient_address,
            'shipment_description' => $package->shipment_description,
            'shipment_date' => $package->shipment_date?->utc()->toIso8601String(),
            'gross_weight_grams' => $package->gross_weight_grams,
            'weight_kg' => $package->weight_kg !== null ? (float) $package->weight_kg : null,
            'dimensions_cm' => [
                'length' => $package->length_cm !== null ? (float) $package->length_cm : null,
                'width' => $package->width_cm !== null ? (float) $package->width_cm : null,
                'height' => $package->height_cm !== null ? (float) $package->height_cm : null,
            ],
            'value_fob_usd' => $package->value_fob_usd !== null ? (float) $package->value_fob_usd : null,
            'currency_code' => $package->currency_code,
            'pre_alert_at' => $package->pre_alert_at?->utc()->toIso8601String(),
            'tracking_standard' => $package->tracking_standard,
            'customs_items' => $package->customs_items ?? [],
            'status' => $package->status,
            'status_label' => PackageStatusCatalog::labelForStatus($package->status),
            'registered_at' => $package->registered_at?->utc()->toIso8601String(),
            'last_movement_at' => $package->last_movement_at?->utc()->toIso8601String(),
            'delivery_attempts' => (int) $package->delivery_attempts,
            'last_delivery_attempt_at' => $package->last_delivery_attempt_at?->utc()->toIso8601String(),
            'last_delivery_attempt' => [
                'attempt' => (int) $package->delivery_attempts,
                'location' => $package->latestDeliveryAttemptLocation(),
                'reason_code' => $package->latestDeliveryAttemptReasonCode(),
                'description' => $package->latestDeliveryAttemptDescription(),
            ],
            'meta' => $package->meta ?? [],
            'movements' => $package->movements->sortBy('occurred_at')->map(fn ($movement) => [
                'id' => $movement->id,
                'event_id' => $movement->event_id,
                'event_code' => $movement->event_code,
                'ips_operation_code' => $movement->ips_operation_code,
                'ips_event_type_cd' => $movement->ips_event_type_cd,
                'status' => $movement->status,
                'status_label' => PackageStatusCatalog::labelForStatus($movement->status),
                'location' => $movement->location,
                'location_code' => $movement->location_code,
                'reason_code' => $movement->reason_code,
                'description' => $movement->displayDescription(),
                'occurred_at' => $movement->occurred_at?->utc()->toIso8601String(),
                'recorded_at' => $movement->recorded_at?->utc()->toIso8601String(),
                'source' => $movement->source_type,
                'source_reference' => $movement->source_reference,
                'physical_confirmation' => $movement->physical_confirmation,
                'meta' => $movement->meta ?? [],
            ])->values(),
            'movement_history' => [
                'included' => $package->movements->count(),
                'total' => (int) ($package->public_movements_count ?? $package->movements->count()),
                'complete' => (int) ($package->public_movements_count ?? $package->movements->count()) <= $package->movements->count(),
                'endpoint' => '/api/v1/packages/'.$package->tracking_code.'/movements',
            ],
        ];
    }
}
