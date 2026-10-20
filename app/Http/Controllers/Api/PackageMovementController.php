<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageMovement;
use App\Services\TrackingEventWriter;
use App\Support\ApiTokenContext;
use App\Support\IpsEventCatalog;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageMovementController extends Controller
{
    public function index(Request $request, string $trackingCode): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $package = $this->resolvePackage($request, $trackingCode);
        $movements = $package->movements()
            ->where('is_public', true)
            ->reorder('occurred_at')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'data' => $movements->getCollection()->map(fn (PackageMovement $movement) => $this->transformMovement($movement))->values(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'last_page' => $movements->lastPage(),
            ],
        ]);
    }

    public function store(
        Request $request,
        string $trackingCode,
        TrackingEventWriter $writer
    ): JsonResponse {
        $apiToken = ApiTokenContext::currentToken($request);
        $package = $this->resolvePackage($request, $trackingCode);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:100', 'required_without_all:event_code,ips_operation_code', Rule::in(PackageStatusCatalog::allowedStatuses())],
            'event_code' => ['nullable', 'string', 'required_without_all:status,ips_operation_code', Rule::in(PackageStatusCatalog::webhookEvents())],
            'ips_operation_code' => ['nullable', 'string', 'required_without_all:status,event_code', Rule::in(array_keys(IpsEventCatalog::existingPackageEvents()))],
            'location' => ['nullable', 'string', 'max:255'],
            'location_code' => ['nullable', 'string', 'max:80'],
            'reason_code' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'physical_confirmation' => ['nullable', 'boolean'],
            'receptacle_reference' => ['nullable', 'string', 'max:100'],
            'condition_code' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'customs_office_code' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'customs_office_type_code' => ['nullable', 'string', 'max:3', 'regex:/^[A-Za-z0-9]+$/'],
            'customs_release_status_code' => ['nullable', 'string', 'max:3', 'regex:/^[A-Za-z0-9]+$/'],
            'next_office_code' => ['nullable', 'string', 'max:80'],
            'domestic_receptacle_reference' => ['nullable', 'string', 'max:120'],
            'delivery_agent_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z0-9]{2}$/'],
            'non_delivery_measure' => ['nullable', 'string', 'size:1'],
            'physical_return_confirmed' => ['nullable', 'boolean'],
            'delivery_mode' => ['nullable', Rule::in(['DOMICILIARIA', 'NO_DOMICILIARIA'])],
            'signatory' => ['nullable', 'string', 'max:64'],
            'occurred_at' => [
                'nullable',
                'date',
                'regex:/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?(?:Z|[+-]\\d{2}:\\d{2})$/',
            ],
            'source_reference' => ['nullable', 'string', 'max:120'],
            'meta' => ['nullable', 'array'],
        ]);

        $operationCode = isset($validated['ips_operation_code'])
            ? strtoupper(trim($validated['ips_operation_code']))
            : null;
        $ipsOperation = $operationCode !== null ? IpsEventCatalog::existingPackageEvent($operationCode) : null;
        $status = $validated['status']
            ?? ($ipsOperation['status'] ?? PackageStatusCatalog::statusForEvent($validated['event_code'] ?? ''));
        $normalizedStatus = PackageStatusCatalog::normalize((string) $status);
        if ((isset($validated['event_code']) && PackageStatusCatalog::eventForStatus($normalizedStatus) !== $validated['event_code'])
            || ($ipsOperation && PackageStatusCatalog::normalize((string) $ipsOperation['status']) !== $normalizedStatus)) {
            return response()->json([
                'code' => 'EVENT_STATUS_MISMATCH',
                'message' => __('api.errors.event_status_mismatch'),
            ], 422);
        }

        if ($ipsOperation && ! empty($ipsOperation['requires_physical_confirmation'])
            && filter_var($validated['physical_confirmation'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'physical_confirmation' => ['Confirme la recepción física para registrar este evento.'],
            ]);
        }

        if ($ipsOperation) {
            if (empty($validated['location_code'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'location_code' => ['El código de oficina es obligatorio para los eventos IPS.'],
                ]);
            }

            $missingContext = IpsEventCatalog::missingPackageContext($operationCode, $validated, (string) $package->status);
            if ($missingContext !== []) {
                throw \Illuminate\Validation\ValidationException::withMessages(array_fill_keys(
                    $missingContext,
                    ['Complete los datos requeridos para el evento IPS seleccionado.']
                ));
            }
        }

        $idempotencyKey = $request->header('Idempotency-Key') ?: ($validated['source_reference'] ?? null);

        if ($idempotencyKey !== null && (strlen($idempotencyKey) > 120 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $idempotencyKey))) {
            return response()->json([
                'code' => 'INVALID_IDEMPOTENCY_KEY',
                'message' => __('api.errors.invalid_idempotency_key'),
            ], 422);
        }

        $result = $writer->record($package, [
            ...$validated,
            'status' => $normalizedStatus,
            'ips_operation_code' => $operationCode,
            'physical_confirmation' => isset($validated['physical_confirmation'])
                ? filter_var($validated['physical_confirmation'], FILTER_VALIDATE_BOOLEAN)
                : null,
            'ips_context' => collect($validated)->only([
                'receptacle_reference', 'condition_code', 'customs_office_code', 'customs_office_type_code',
                'customs_release_status_code', 'next_office_code', 'domestic_receptacle_reference',
                'delivery_agent_code', 'non_delivery_measure', 'physical_return_confirmed', 'delivery_mode', 'signatory',
            ])->all(),
            'created_by_api_token_id' => ApiTokenContext::ownershipAttributes($apiToken)['created_by_api_token_id'],
        ], 'company_api', $idempotencyKey);

        if ($result['conflict']) {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'message' => __('api.errors.idempotency_conflict'),
            ], 409);
        }

        $movement = $result['movement'];
        $package = $package->fresh('company');

        return response()->json([
            'message' => $result['replayed'] ? __('api.messages.movement_replayed') : __('api.messages.movement_registered'),
            'data' => $this->transformMovement($movement),
            'replayed' => $result['replayed'],
            'late_event' => $result['late'],
            'current_status' => $package->status,
            'current_status_label' => PackageStatusCatalog::labelForStatus($package->status),
        ], $result['replayed'] ? 200 : 201);
    }

    private function resolvePackage(Request $request, string $trackingCode): Package
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        return ApiTokenContext::scopeToToken(
            Package::query()->where('company_id', $company->id),
            $apiToken
        )
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();
    }

    private function transformMovement(PackageMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'event_id' => $movement->event_id,
            'event_code' => $movement->event_code,
            'ips_operation_code' => $movement->ips_operation_code,
            'ips_event_type_cd' => $movement->ips_event_type_cd,
            'physical_confirmation' => $movement->physical_confirmation,
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
            'meta' => $movement->meta ?? [],
        ];
    }
}
