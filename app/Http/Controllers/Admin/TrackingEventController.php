<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cn31Bag;
use App\Models\Package;
use App\Services\BagTrackingEventWriter;
use App\Services\TrackingEventWriter;
use App\Support\IpsEventCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrackingEventController extends Controller
{
    public function storePackage(Request $request, Package $package, TrackingEventWriter $writer): JsonResponse
    {
        $validated = $this->validatePackageInput($request);
        $operationCode = strtoupper(trim($validated['ips_operation_code']));
        $operation = IpsEventCatalog::existingPackageEvent($operationCode);

        if (! $operation) {
            throw ValidationException::withMessages([
                'ips_operation_code' => ['Este código IPS no corresponde a un evento de un paquete ya registrado.'],
            ]);
        }

        $ipsContext = collect($validated)->only($this->packageContextFields())->all();
        $missingContext = IpsEventCatalog::missingPackageContext(
            $operationCode,
            $ipsContext,
            (string) $package->status
        );

        if ($missingContext !== []) {
            throw ValidationException::withMessages(array_fill_keys(
                $missingContext,
                ['Complete los datos requeridos para el evento IPS seleccionado.']
            ));
        }

        if (! empty($operation['requires_physical_confirmation'])
            && filter_var($validated['physical_confirmation'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            throw ValidationException::withMessages([
                'physical_confirmation' => ['Confirme la recepción física para registrar este evento.'],
            ]);
        }

        $idempotencyKey = $this->idempotencyKey($request, $validated['source_reference'] ?? null);
        $ipsContext['physical_return_confirmed'] = isset($ipsContext['physical_return_confirmed'])
            ? filter_var($ipsContext['physical_return_confirmed'], FILTER_VALIDATE_BOOLEAN)
            : null;

        $result = $writer->record($package, [
            ...$validated,
            'status' => $operation['status'],
            'ips_operation_code' => $operationCode,
            'actor_user_id' => $request->user()->id,
            'source_reference' => $validated['source_reference'] ?? $idempotencyKey,
            'is_public' => true,
            'notify_webhook' => filter_var($validated['notify_webhook'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'physical_confirmation' => isset($validated['physical_confirmation'])
                ? filter_var($validated['physical_confirmation'], FILTER_VALIDATE_BOOLEAN)
                : null,
            'ips_context' => $ipsContext,
            'occurred_at' => $this->parseLocalDate($validated['occurred_at'] ?? null),
        ], 'internal_operator', $idempotencyKey);

        if ($result['conflict']) {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'message' => __('api.errors.idempotency_conflict'),
            ], 409);
        }

        return response()->json([
            'data' => [
                'event_id' => $result['movement']->event_id,
                'event_code' => $result['movement']->event_code,
                'ips_operation_code' => $result['movement']->ips_operation_code,
                'ips_event_type_cd' => $result['movement']->ips_event_type_cd,
                'status' => $result['movement']->status,
                'occurred_at' => $result['movement']->occurred_at?->utc()->toIso8601String(),
                'recorded_at' => $result['movement']->recorded_at?->utc()->toIso8601String(),
                'late_event' => $result['late'],
                'replayed' => $result['replayed'],
            ],
            'current_status' => $package->fresh()->status,
        ], $result['replayed'] ? 200 : 201);
    }

    public function storeBag(Request $request, Cn31Bag $bag, BagTrackingEventWriter $writer): JsonResponse
    {
        $validated = $request->validate([
            'ips_operation_code' => ['required', 'string', Rule::in(array_keys(IpsEventCatalog::bagEvents()))],
            'location_code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'occurred_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'physical_confirmation' => ['nullable', 'boolean'],
            'source_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $operationCode = strtoupper(trim($validated['ips_operation_code']));
        $operation = IpsEventCatalog::bagEvent($operationCode);
        if (! empty($operation['requires_physical_confirmation'])
            && filter_var($validated['physical_confirmation'] ?? false, FILTER_VALIDATE_BOOLEAN) !== true) {
            throw ValidationException::withMessages([
                'physical_confirmation' => ['Confirme que la saca llegó físicamente.'],
            ]);
        }

        $idempotencyKey = $this->idempotencyKey($request, $validated['source_reference'] ?? null);
        $result = $writer->record($bag, [
            ...$validated,
            'ips_operation_code' => $operationCode,
            'actor_user_id' => $request->user()->id,
            'source_reference' => $validated['source_reference'] ?? $idempotencyKey,
            'physical_confirmation' => isset($validated['physical_confirmation'])
                ? filter_var($validated['physical_confirmation'], FILTER_VALIDATE_BOOLEAN)
                : null,
            'occurred_at' => $this->parseLocalDate($validated['occurred_at']),
        ], 'internal_operator', $idempotencyKey);

        if ($result['conflict']) {
            return response()->json([
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'message' => __('api.errors.idempotency_conflict'),
            ], 409);
        }

        return response()->json([
            'data' => [
                'event_id' => $result['movement']->event_id,
                'event_code' => $result['movement']->event_code,
                'ips_operation_code' => $result['movement']->ips_operation_code,
                'ips_event_type_cd' => $result['movement']->ips_event_type_cd,
                'status' => $result['movement']->status,
                'location_code' => $result['movement']->location_code,
                'occurred_at' => $result['movement']->occurred_at?->utc()->toIso8601String(),
                'recorded_at' => $result['movement']->recorded_at?->utc()->toIso8601String(),
                'replayed' => $result['replayed'],
            ],
            'current_status' => $bag->fresh()->operational_status,
        ], $result['replayed'] ? 200 : 201);
    }

    private function validatePackageInput(Request $request): array
    {
        return $request->validate([
            'ips_operation_code' => ['required', 'string', Rule::in(array_keys(IpsEventCatalog::existingPackageEvents()))],
            'location_code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_reference' => ['nullable', 'string', 'max:120'],
            'occurred_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'physical_confirmation' => ['nullable', 'boolean'],
            'notify_webhook' => ['nullable', 'boolean'],
            'receptacle_reference' => ['nullable', 'string', 'max:100'],
            'condition_code' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'customs_office_code' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'customs_office_type_code' => ['nullable', 'string', 'max:3', 'regex:/^[A-Za-z0-9]+$/'],
            'customs_release_status_code' => ['nullable', 'string', 'max:3', 'regex:/^[A-Za-z0-9]+$/'],
            'next_office_code' => ['nullable', 'string', 'max:80'],
            'domestic_receptacle_reference' => ['nullable', 'string', 'max:120'],
            'delivery_agent_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z0-9]{2}$/'],
            'reason_code' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'non_delivery_measure' => ['nullable', 'string', 'size:1'],
            'physical_return_confirmed' => ['nullable', 'boolean'],
            'delivery_mode' => ['nullable', Rule::in(['DOMICILIARIA', 'NO_DOMICILIARIA'])],
            'signatory' => ['nullable', 'string', 'max:64'],
        ]);
    }

    /** @return list<string> */
    private function packageContextFields(): array
    {
        return [
            'receptacle_reference', 'condition_code', 'customs_office_code', 'customs_office_type_code',
            'customs_release_status_code', 'next_office_code', 'domestic_receptacle_reference',
            'delivery_agent_code', 'reason_code', 'non_delivery_measure', 'physical_return_confirmed',
            'delivery_mode', 'signatory',
        ];
    }

    private function idempotencyKey(Request $request, ?string $sourceReference): string
    {
        $key = $request->header('Idempotency-Key') ?: ($sourceReference ?: (string) \Illuminate\Support\Str::uuid());
        if (strlen($key) > 120 || ! preg_match('/^[A-Za-z0-9._:-]+$/', $key)) {
            throw ValidationException::withMessages([
                'idempotency_key' => ['La clave de idempotencia debe tener hasta 120 caracteres alfanuméricos.'],
            ]);
        }

        return $key;
    }

    private function parseLocalDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d\\TH:i', $value, config('ips.operation_timezone', 'America/La_Paz'))
            ->utc()
            ->toIso8601String();
    }
}
