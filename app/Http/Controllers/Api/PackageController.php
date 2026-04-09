<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\PackageStatusCatalog;
use App\Support\PackageTrackingCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $packages = Package::query()
            ->where('company_id', $company->id)
            ->when($request->string('tracking_code')->isNotEmpty(), function ($query) use ($request) {
                $query->where('tracking_code', $request->string('tracking_code'));
            })
            ->latest()
            ->paginate(20);

        return response()->json($packages);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

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
                    fn ($query) => $query->where('company_id', $company->id)
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
            'status' => ['nullable', Rule::in(PackageStatusCatalog::allowedStatuses())],
            'registered_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $validated['tracking_standard'] = $validated['tracking_standard'] ?? PackageTrackingCode::detectStandard($validated['tracking_code']);
        $validated['recipient_whatsapp'] = $validated['recipient_whatsapp'] ?? ($validated['recipient_phone'] ?? null);
        $validated['weight_kg'] = $validated['weight_kg'] ?? (isset($validated['gross_weight_grams']) ? round($validated['gross_weight_grams'] / 1000, 3) : null);

        $package = Package::create([
            ...$validated,
            'company_id' => $company->id,
            'status' => $validated['status'] ?? 'registrado',
            'registered_at' => $validated['registered_at'] ?? now(),
            'last_movement_at' => $validated['registered_at'] ?? now(),
        ]);

        return response()->json([
            'message' => __('api.messages.package_registered'),
            'data' => $this->transformPackage($package->load('movements')),
        ], 201);
    }

    public function show(Request $request, string $trackingCode): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $package = Package::with('movements')
            ->where('company_id', $company->id)
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
            'destination' => $package->destination,
            'origin_office' => $package->origin_office,
            'destination_office' => $package->destination_office,
            'recipient_address' => $package->recipient_address,
            'shipment_description' => $package->shipment_description,
            'shipment_date' => $package->shipment_date?->toIso8601String(),
            'gross_weight_grams' => $package->gross_weight_grams,
            'weight_kg' => $package->weight_kg !== null ? (float) $package->weight_kg : null,
            'dimensions_cm' => [
                'length' => $package->length_cm !== null ? (float) $package->length_cm : null,
                'width' => $package->width_cm !== null ? (float) $package->width_cm : null,
                'height' => $package->height_cm !== null ? (float) $package->height_cm : null,
            ],
            'value_fob_usd' => $package->value_fob_usd !== null ? (float) $package->value_fob_usd : null,
            'currency_code' => $package->currency_code,
            'pre_alert_at' => $package->pre_alert_at?->toIso8601String(),
            'tracking_standard' => $package->tracking_standard,
            'customs_items' => $package->customs_items ?? [],
            'status' => $package->status,
            'status_label' => PackageStatusCatalog::labelForStatus($package->status),
            'registered_at' => $package->registered_at?->toIso8601String(),
            'last_movement_at' => $package->last_movement_at?->toIso8601String(),
            'meta' => $package->meta ?? [],
            'movements' => $package->movements->map(fn ($movement) => [
                'id' => $movement->id,
                'status' => $movement->status,
                'status_label' => PackageStatusCatalog::labelForStatus($movement->status),
                'location' => $movement->location,
                'description' => $movement->description,
                'occurred_at' => $movement->occurred_at?->toIso8601String(),
                'meta' => $movement->meta ?? [],
            ])->values(),
        ];
    }
}
