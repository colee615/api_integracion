<?php

namespace App\Services;

use App\Models\Cn31Bag;
use App\Models\Cn31BagMovement;
use App\Support\IpsEventCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BagTrackingEventWriter
{
    /**
     * Add a local receptacle event without writing to IPS.
     *
     * @return array{movement:Cn31BagMovement,replayed:bool,conflict:bool}
     */
    public function record(Cn31Bag $bag, array $data, string $sourceType, ?string $idempotencyKey = null): array
    {
        return DB::transaction(function () use ($bag, $data, $sourceType, $idempotencyKey): array {
            $lockedBag = Cn31Bag::query()->lockForUpdate()->findOrFail($bag->id);
            $operationCode = strtoupper(trim((string) ($data['ips_operation_code'] ?? '')));
            $operation = IpsEventCatalog::bagEvent($operationCode);

            if (! $operation) {
                throw ValidationException::withMessages([
                    'ips_operation_code' => ['El evento de saca no pertenece al catálogo IPS configurado.'],
                ]);
            }

            $occurredAt = isset($data['occurred_at'])
                ? Carbon::parse($data['occurred_at'])->utc()
                : now()->utc();

            if ($occurredAt->gt(now()->utc())) {
                throw ValidationException::withMessages([
                    'occurred_at' => ['La fecha del evento no puede estar en el futuro.'],
                ]);
            }

            $requestHash = $this->requestHash($operationCode, $data, $occurredAt);

            if ($idempotencyKey !== null) {
                $existing = Cn31BagMovement::query()
                    ->where('company_id', $lockedBag->company_id)
                    ->where('cn31_bag_id', $lockedBag->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return [
                        'movement' => $existing,
                        'replayed' => hash_equals((string) $existing->request_hash, $requestHash),
                        'conflict' => ! hash_equals((string) $existing->request_hash, $requestHash),
                    ];
                }
            }

            $currentStatus = (string) ($lockedBag->operational_status ?: 'pendiente_recepcion');
            $lastMovementAt = $lockedBag->last_operational_movement_at;

            if ($lastMovementAt !== null && $occurredAt->lte($lastMovementAt)) {
                throw ValidationException::withMessages([
                    'occurred_at' => ['El evento debe ser posterior al último movimiento de la saca.'],
                ]);
            }

            if ($operationCode === 'RECEPTACLE_RECEIVE') {
                if ($currentStatus !== 'pendiente_recepcion') {
                    throw ValidationException::withMessages([
                        'ips_operation_code' => ['Esta saca ya tiene recepción registrada.'],
                    ]);
                }

                if (($data['physical_confirmation'] ?? false) !== true) {
                    throw ValidationException::withMessages([
                        'physical_confirmation' => ['Confirme que la saca llegó físicamente.'],
                    ]);
                }
            }

            if ($operationCode === 'RECEPTACLE_OPEN') {
                if ($currentStatus !== 'recibido') {
                    throw ValidationException::withMessages([
                        'ips_operation_code' => ['La saca debe estar recibida antes de abrirla.'],
                    ]);
                }

                $receipt = $lockedBag->movements()
                    ->where('ips_operation_code', 'RECEPTACLE_RECEIVE')
                    ->latest('occurred_at')
                    ->first();
                $receivedAtLocation = strtoupper(trim((string) ($receipt?->location_code ?? '')));
                $openAtLocation = strtoupper(trim((string) ($data['location_code'] ?? '')));

                if ($receivedAtLocation === '' || $receivedAtLocation !== $openAtLocation) {
                    throw ValidationException::withMessages([
                        'location_code' => ['La saca debe abrirse en la misma oficina donde se registró su recepción.'],
                    ]);
                }
            }

            $movement = $lockedBag->movements()->create([
                'company_id' => $lockedBag->company_id,
                'created_by_api_token_id' => $data['created_by_api_token_id'] ?? null,
                'actor_user_id' => $data['actor_user_id'] ?? null,
                'ips_operation_code' => $operationCode,
                'ips_event_type_cd' => $operation['event_type_cd'],
                'source_type' => $sourceType,
                'source_reference' => $data['source_reference'] ?? null,
                'location_code' => trim((string) $data['location_code']),
                'location' => $data['location'] ?? null,
                'status' => $operation['status'],
                'description' => $data['description'] ?? null,
                'occurred_at' => $occurredAt,
                'recorded_at' => now()->utc(),
                'physical_confirmation' => isset($data['physical_confirmation'])
                    ? (bool) $data['physical_confirmation']
                    : null,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'meta' => $data['meta'] ?? [],
            ]);

            $lockedBag->forceFill([
                'operational_status' => $operation['status'],
                'last_operational_movement_at' => $occurredAt,
            ])->save();

            return [
                'movement' => $movement,
                'replayed' => false,
                'conflict' => false,
            ];
        }, 3);
    }

    private function requestHash(string $operationCode, array $data, Carbon $occurredAt): string
    {
        $payload = [
            'ips_operation_code' => $operationCode,
            'occurred_at' => isset($data['occurred_at']) ? $occurredAt->toIso8601String() : null,
            'location_code' => strtoupper(trim((string) ($data['location_code'] ?? ''))),
            'location' => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
            'source_reference' => $data['source_reference'] ?? null,
            'physical_confirmation' => $data['physical_confirmation'] ?? null,
            'meta' => $data['meta'] ?? [],
        ];

        $this->sortKeysRecursively($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    private function sortKeysRecursively(array &$value): void
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->sortKeysRecursively($item);
            }
        }

        ksort($value);
    }
}
