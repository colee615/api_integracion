<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PackageMovement;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Support\IpsEventCatalog;
use App\Support\PackageStatusCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrackingEventWriter
{
    /**
     * Append an immutable event and update the package's current-state snapshot.
     * Callers may supply an already-scoped package; it is reloaded under a row lock.
     *
     * @return array{movement: PackageMovement, replayed: bool, late: bool, conflict: bool}
     */
    public function record(
        Package $package,
        array $data,
        string $sourceType,
        ?string $idempotencyKey = null
    ): array {
        return DB::transaction(function () use ($package, $data, $sourceType, $idempotencyKey): array {
            $lockedPackage = Package::query()->lockForUpdate()->findOrFail($package->id);
            $status = PackageStatusCatalog::normalize((string) $data['status']);
            $operationCode = isset($data['ips_operation_code'])
                ? strtoupper(trim((string) $data['ips_operation_code']))
                : null;
            $ipsOperation = $operationCode !== null
                ? IpsEventCatalog::existingPackageEvent($operationCode)
                : null;

            if ($operationCode !== null && ! $ipsOperation) {
                throw ValidationException::withMessages([
                    'ips_operation_code' => ['El evento IPS no aplica a un paquete ya registrado.'],
                ]);
            }

            if ($ipsOperation && PackageStatusCatalog::normalize((string) $ipsOperation['status']) !== $status) {
                throw ValidationException::withMessages([
                    'status' => ['El estado no corresponde al evento IPS seleccionado.'],
                ]);
            }

            if ($ipsOperation && ! empty($ipsOperation['requires_physical_confirmation'])
                && ($data['physical_confirmation'] ?? false) !== true) {
                throw ValidationException::withMessages([
                    'physical_confirmation' => ['Confirme la recepción física para registrar este evento.'],
                ]);
            }

            if ($ipsOperation) {
                $missingContext = IpsEventCatalog::missingPackageContext(
                    $operationCode,
                    array_merge($data, $data['ips_context'] ?? []),
                    (string) $lockedPackage->status
                );

                if ($missingContext !== []) {
                    throw ValidationException::withMessages(array_fill_keys(
                        $missingContext,
                        ['Complete los datos requeridos para el evento IPS seleccionado.']
                    ));
                }
            }

            $occurredAt = isset($data['occurred_at'])
                ? Carbon::parse($data['occurred_at'])->utc()
                : now()->utc();

            if ($occurredAt->gt(now()->utc()->addMinutes($ipsOperation ? 0 : 5))) {
                throw ValidationException::withMessages([
                    'occurred_at' => [__('api.errors.future_tracking_event')],
                ]);
            }

            $requestHash = $this->requestHash($status, $data);

            if ($idempotencyKey !== null) {
                $existing = PackageMovement::query()
                    ->where('company_id', $lockedPackage->company_id)
                    ->where('package_id', $lockedPackage->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    $existingIsLate = $lockedPackage->last_movement_at !== null
                        && $existing->occurred_at?->lt($lockedPackage->last_movement_at);

                    return [
                        'movement' => $existing,
                        'replayed' => hash_equals((string) $existing->request_hash, $requestHash),
                        'late' => (bool) $existingIsLate,
                        'conflict' => ! hash_equals((string) $existing->request_hash, $requestHash),
                    ];
                }
            }

            if ($ipsOperation) {
                $lastIpsOperation = $lockedPackage->movements()
                    ->whereNotNull('ips_operation_code')
                    ->orderByDesc('occurred_at')
                    ->orderByDesc('id')
                    ->value('ips_operation_code');
                $allowedOperations = IpsEventCatalog::allowedPackageOperationCodes(
                    (string) $lockedPackage->status,
                    $lastIpsOperation !== null ? (string) $lastIpsOperation : null
                );

                if (! in_array($operationCode, $allowedOperations, true)) {
                    throw ValidationException::withMessages([
                        'ips_operation_code' => ['La etapa actual del paquete no habilita esta operacion IPS.'],
                    ]);
                }

                if ($operationCode === 'EMR') {
                    $this->validateLocalReceptacleReceipt($lockedPackage, array_merge(
                        $data,
                        $data['ips_context'] ?? []
                    ));
                }
            }

            $currentEventAtValue = $lockedPackage->movements()
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->value('occurred_at');
            $currentEventAt = $currentEventAtValue !== null
                ? Carbon::parse($currentEventAtValue)->utc()
                : $lockedPackage->last_movement_at?->utc();
            if ($lockedPackage->last_movement_at !== null
                && ($currentEventAt === null || $lockedPackage->last_movement_at->gt($currentEventAt))) {
                $currentEventAt = $lockedPackage->last_movement_at->copy()->utc();
            }
            $isLate = $currentEventAt !== null && $occurredAt->lt($currentEventAt);

            if ($ipsOperation && $currentEventAt !== null && $occurredAt->lte($currentEventAt)) {
                throw ValidationException::withMessages([
                    'occurred_at' => ['El evento debe ser posterior al último movimiento del paquete.'],
                ]);
            }

            if (! $isLate && (PackageStatusCatalog::isTerminal($lockedPackage->status)
                || ! PackageStatusCatalog::canTransition($lockedPackage->status, $status))) {
                throw ValidationException::withMessages([
                    'status' => [__('api.errors.invalid_status_transition')],
                ]);
            }

            $meta = $data['meta'] ?? [];
            if ($ipsOperation) {
                $meta = array_merge($meta, [
                    'ips_context' => $data['ips_context'] ?? [],
                ]);
            }
            if ($status === 'intentos_carteros') {
                $attemptNumber = ((int) $lockedPackage->delivery_attempts) + 1;
                $data['description'] ??= __('api.messages.package_delivery_attempt_internal', ['attempt' => $attemptNumber]);
                $meta['delivery_attempt'] = $attemptNumber;
            }

            $movement = $lockedPackage->movements()->create([
                'company_id' => $lockedPackage->company_id,
                'created_by_api_token_id' => $data['created_by_api_token_id'] ?? null,
                'event_code' => PackageStatusCatalog::eventForStatus($status),
                'ips_operation_code' => $operationCode,
                'ips_event_type_cd' => $ipsOperation['event_type_cd'] ?? null,
                'actor_user_id' => $data['actor_user_id'] ?? null,
                'source_type' => $sourceType,
                'source_reference' => $data['source_reference'] ?? null,
                'location_code' => $data['location_code'] ?? null,
                'reason_code' => $data['reason_code'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'status' => $status,
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
                'occurred_at' => $occurredAt,
                'recorded_at' => now()->utc(),
                'is_public' => $data['is_public'] ?? true,
                'notify_webhook' => $data['notify_webhook'] ?? ($data['is_public'] ?? true),
                'physical_confirmation' => isset($data['physical_confirmation'])
                    ? (bool) $data['physical_confirmation']
                    : null,
                'meta' => $meta,
            ]);

            $packageChanges = [];

            if (! $isLate) {
                $packageChanges = [
                    'status' => $status,
                    'last_movement_at' => $occurredAt,
                ];
            }

            if ($status === 'intentos_carteros') {
                $attemptNumber = ((int) $lockedPackage->delivery_attempts) + 1;
                $packageChanges['delivery_attempts'] = $attemptNumber;

                if ($lockedPackage->last_delivery_attempt_at === null || $occurredAt->gte($lockedPackage->last_delivery_attempt_at)) {
                    $packageChanges['last_delivery_attempt_at'] = $occurredAt;
                    $packageChanges['meta'] = array_merge($lockedPackage->meta ?? [], [
                        'last_delivery_attempt' => [
                            'attempt' => $attemptNumber,
                            'occurred_at' => $occurredAt->toIso8601String(),
                            'location' => $data['location'] ?? null,
                            'reason_code' => $data['reason_code'] ?? null,
                            'description' => $data['description'] ?? null,
                        ],
                    ]);
                }
            }

            if ($packageChanges !== []) {
                $lockedPackage->forceFill([
                    ...$packageChanges,
                ])->save();
            }

            if (! $isLate) {
                $this->syncCn33DeliveryStatus($lockedPackage, $status, $occurredAt);
            }

            return [
                'movement' => $movement,
                'replayed' => false,
                'late' => $isLate,
                'conflict' => false,
            ];
        });
    }

    private function requestHash(string $status, array $data): string
    {
        $payload = [
            'status' => $status,
            // An omitted event time is server-assigned; exclude it so retries
            // with the same idempotency key produce the same request fingerprint.
            'occurred_at' => isset($data['occurred_at'])
                ? Carbon::parse($data['occurred_at'])->utc()->toIso8601String()
                : null,
            'location' => $data['location'] ?? null,
            'location_code' => $data['location_code'] ?? null,
            'reason_code' => $data['reason_code'] ?? null,
            'description' => $data['description'] ?? null,
            'source_reference' => $data['source_reference'] ?? null,
            'ips_operation_code' => isset($data['ips_operation_code'])
                ? strtoupper(trim((string) $data['ips_operation_code']))
                : null,
            'physical_confirmation' => $data['physical_confirmation'] ?? null,
            'ips_context' => $data['ips_context'] ?? [],
            'meta' => $data['meta'] ?? [],
        ];

        $this->sortKeysRecursively($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    private function syncCn33DeliveryStatus(Package $package, string $status, Carbon $occurredAt): void
    {
        $cn33Package = Cn33Package::query()
            ->where('package_id', $package->id)
            ->lockForUpdate()
            ->first();

        if (! $cn33Package) {
            return;
        }

        $deliveryStatus = $status === 'entregado'
            ? 'completado'
            : (in_array($status, ['registrado', 'pre_alerta_recibida'], true) ? 'pendiente' : 'en_proceso');

        $cn33Package->forceFill([
            'delivery_status' => $deliveryStatus,
            'meta' => array_merge($cn33Package->meta ?? [], $deliveryStatus === 'completado' ? [
                'delivered_at' => $occurredAt->toIso8601String(),
            ] : []),
        ])->save();

        $bag = Cn31Bag::query()->lockForUpdate()->find($cn33Package->cn31_bag_id);
        if (! $bag) {
            return;
        }

        $packageDeliveryStatuses = Cn33Package::query()
            ->where('cn31_bag_id', $bag->id)
            ->pluck('delivery_status');
        $bagDeliveryStatus = $this->aggregateDeliveryStatus($packageDeliveryStatuses);
        $bagMeta = $bag->meta ?? [];
        if ($bagDeliveryStatus === 'completado') {
            $bagMeta['delivered_at'] = $occurredAt->toIso8601String();
            $bagMeta['delivery_source'] = 'package_tracking_event';
        }

        $bag->forceFill([
            'delivery_status' => $bagDeliveryStatus,
            'meta' => $bagMeta,
        ])->save();

        $manifest = Cn31Manifest::query()->lockForUpdate()->find($bag->cn31_manifest_id);
        if (! $manifest) {
            return;
        }

        $bagDeliveryStatuses = Cn31Bag::query()
            ->where('cn31_manifest_id', $manifest->id)
            ->pluck('delivery_status');
        $manifestDeliveryStatus = $this->aggregateDeliveryStatus($bagDeliveryStatuses);
        $manifestMeta = $manifest->meta ?? [];
        if ($manifestDeliveryStatus === 'completado') {
            $manifestMeta['delivered_at'] = $occurredAt->toIso8601String();
            $manifestMeta['delivery_source'] = 'package_tracking_event';
        }

        $manifest->forceFill([
            'delivery_status' => $manifestDeliveryStatus,
            'meta' => $manifestMeta,
        ])->save();
    }

    private function aggregateDeliveryStatus(\Illuminate\Support\Collection $statuses): string
    {
        if ($statuses->isEmpty()) {
            return 'pendiente';
        }

        if ($statuses->every(fn ($status): bool => $status === 'completado')) {
            return 'completado';
        }

        return $statuses->contains(fn ($status): bool => in_array($status, ['completado', 'en_proceso'], true))
            ? 'en_proceso'
            : 'pendiente';
    }

    private function validateLocalReceptacleReceipt(Package $package, array $context): void
    {
        $reference = trim((string) ($context['receptacle_reference'] ?? ''));
        $bag = Cn31Bag::query()
            ->where('company_id', $package->company_id)
            ->where(fn ($query) => $query
                ->where('bag_number', $reference)
                ->orWhere('dispatch_number_bag', $reference))
            ->first();

        if (! $bag || ! Cn33Package::query()
            ->where('package_id', $package->id)
            ->where('cn31_bag_id', $bag->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'receptacle_reference' => ['La saca indicada no contiene este paquete en el registro CN33 local.'],
            ]);
        }

        if ($bag->operational_status !== 'abierto') {
            throw ValidationException::withMessages([
                'receptacle_reference' => ['La saca del paquete debe tener recepción y apertura registradas antes de recibir el paquete.'],
            ]);
        }

        $opening = $bag->movements()
            ->where('ips_operation_code', 'RECEPTACLE_OPEN')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();
        $packageOffice = strtoupper(trim((string) ($context['location_code'] ?? '')));
        $openingOffice = strtoupper(trim((string) ($opening?->location_code ?? '')));

        if ($openingOffice === '' || $openingOffice !== $packageOffice) {
            throw ValidationException::withMessages([
                'location_code' => ['El paquete debe recibirse en la misma oficina donde se abrió su saca.'],
            ]);
        }

        $packageReceiptAt = isset($context['occurred_at'])
            ? Carbon::parse($context['occurred_at'])->utc()
            : now()->utc();
        if (! $opening?->occurred_at || $packageReceiptAt->lte($opening->occurred_at->utc())) {
            throw ValidationException::withMessages([
                'occurred_at' => ['La recepción del paquete debe ser posterior a la apertura de su saca.'],
            ]);
        }
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
