<?php

namespace App\Support;

/**
 * The IPS event codes used by SITRA, mirrored locally for coordination.
 * Package statuses remain the API's stable business snapshot; the operation
 * and numeric event type keep the exact IPS meaning in each movement row.
 */
class IpsEventCatalog
{
    private const PACKAGE_EVENTS = [
        'EMA' => [
            'event_type_cd' => 1,
            'name' => 'Admisión del envío',
            'status' => 'aceptado_en_origen',
            'existing_package' => false,
        ],
        'EMD' => [
            'event_type_cd' => 30,
            'name' => 'Recepción en oficina de intercambio (alta)',
            'status' => 'recibido_centro_clasificacion',
            'existing_package' => false,
            'requires_physical_confirmation' => true,
        ],
        'EMR' => [
            'event_type_cd' => 30,
            'name' => 'Recibir en oficina de intercambio',
            'status' => 'recibido_centro_clasificacion',
            'existing_package' => true,
            'requires_physical_confirmation' => true,
            'required_fields' => ['receptacle_reference', 'condition_code'],
        ],
        'CUSTOMS_PRESENT' => [
            'event_type_cd' => 31,
            'name' => 'Enviar envío a Aduana',
            'status' => 'en_proceso_aduana',
            'existing_package' => true,
            'required_fields' => ['customs_office_code', 'customs_office_type_code'],
        ],
        'CUSTOMS_INFO' => [
            'event_type_cd' => 34,
            'name' => 'Registrar información aduanera',
            'status' => 'en_proceso_aduana',
            'existing_package' => true,
            'required_fields' => ['customs_release_status_code'],
        ],
        'EDN' => [
            'event_type_cd' => 35,
            'name' => 'Despacho a una ubicación nacional',
            'status' => 'despachado_origen',
            'existing_package' => true,
            'required_fields' => ['next_office_code'],
        ],
        'EIB' => [
            'event_type_cd' => 13,
            'name' => 'Incorporación a saca nacional',
            'status' => 'en_transito',
            'existing_package' => true,
            'required_fields' => ['domestic_receptacle_reference'],
        ],
        'EMG' => [
            'event_type_cd' => 32,
            'name' => 'Recepción en oficina de entrega',
            'status' => 'arribado_destino',
            'existing_package' => true,
            'requires_physical_confirmation' => true,
        ],
        'CUSTOMS_RETURN' => [
            'event_type_cd' => 38,
            'name' => 'Devolver envío desde Aduana',
            'status' => 'liberado_aduana',
            'existing_package' => true,
            'requires_physical_confirmation' => true,
        ],
        'HANDOVER_AGENT' => [
            'event_type_cd' => 39,
            'name' => 'Transferir envío al agente de entrega',
            'status' => 'en_ruta_entrega',
            'existing_package' => true,
            'required_fields' => ['delivery_agent_code'],
        ],
        'EDG' => [
            'event_type_cd' => 74,
            'name' => 'Salida a reparto',
            'status' => 'en_ruta_entrega',
            'existing_package' => true,
        ],
        'EDH' => [
            'event_type_cd' => 75,
            'name' => 'Disponible para retiro',
            'status' => 'disponible_retiro',
            'existing_package' => true,
        ],
        'EMH' => [
            'event_type_cd' => 36,
            'name' => 'Intento de entrega fallido',
            'status' => 'intentos_carteros',
            'existing_package' => true,
            'required_fields' => ['reason_code', 'non_delivery_measure'],
        ],
        'EMI' => [
            'event_type_cd' => 37,
            'name' => 'Entregado al destinatario',
            'status' => 'entregado',
            'existing_package' => true,
            'required_fields' => ['delivery_mode'],
        ],
    ];

    private const BAG_EVENTS = [
        'RECEPTACLE_RECEIVE' => [
            'event_type_cd' => 130,
            'name' => 'Recibir envase del extranjero',
            'status' => 'recibido',
            'requires_physical_confirmation' => true,
        ],
        'RECEPTACLE_OPEN' => [
            'event_type_cd' => 131,
            'name' => 'Abrir envase recibido',
            'status' => 'abierto',
        ],
    ];

    /**
     * The local mirror follows SITRA's operation stages. Office, IPS state,
     * country-routing and reference-catalog checks remain unavailable here.
     */
    private const NEXT_PACKAGE_OPERATIONS = [
        'EMA' => ['EMR'],
        'EMD' => ['EDN', 'CUSTOMS_PRESENT'],
        'EMR' => ['EDN', 'CUSTOMS_PRESENT'],
        'CUSTOMS_PRESENT' => ['CUSTOMS_INFO', 'CUSTOMS_RETURN'],
        'CUSTOMS_INFO' => ['CUSTOMS_RETURN'],
        'CUSTOMS_RETURN' => ['EDN', 'EDH', 'HANDOVER_AGENT', 'EMI'],
        'EDN' => ['EIB'],
        'EIB' => ['EMG'],
        'EMG' => ['EDH', 'CUSTOMS_PRESENT', 'HANDOVER_AGENT', 'EMI'],
        'HANDOVER_AGENT' => ['EDG'],
        'EDG' => ['EMH', 'EMI'],
        'EDH' => ['EMI'],
        'EMH' => ['HANDOVER_AGENT'],
    ];

    public static function packageEvents(): array
    {
        return self::PACKAGE_EVENTS;
    }

    public static function existingPackageEvents(): array
    {
        return array_filter(self::PACKAGE_EVENTS, fn (array $event): bool => $event['existing_package']);
    }

    public static function bagEvents(): array
    {
        return self::BAG_EVENTS;
    }

    public static function packageEvent(string $operationCode): ?array
    {
        return self::PACKAGE_EVENTS[strtoupper(trim($operationCode))] ?? null;
    }

    public static function existingPackageEvent(string $operationCode): ?array
    {
        return self::existingPackageEvents()[strtoupper(trim($operationCode))] ?? null;
    }

    public static function bagEvent(string $operationCode): ?array
    {
        return self::BAG_EVENTS[strtoupper(trim($operationCode))] ?? null;
    }

    public static function allowedPackageOperationCodes(string $status, ?string $lastOperationCode = null): array
    {
        $status = PackageStatusCatalog::normalize($status);
        if (PackageStatusCatalog::isTerminal($status)) {
            return [];
        }

        if ($lastOperationCode !== null) {
            return self::NEXT_PACKAGE_OPERATIONS[strtoupper(trim($lastOperationCode))] ?? [];
        }

        // Legacy local package histories may predate the IPS operation mirror.
        // Start from the closest SITRA stage represented by the local snapshot.
        return match ($status) {
            'registrado', 'pre_alerta_recibida', 'recibido_api', 'aceptado_en_origen' => ['EMR'],
            'recibido_centro_clasificacion' => ['EDN', 'CUSTOMS_PRESENT'],
            'despachado_origen' => ['EIB'],
            'en_transito' => ['EMG'],
            'arribado_destino' => ['EDH', 'CUSTOMS_PRESENT', 'HANDOVER_AGENT', 'EMI'],
            'en_proceso_aduana' => ['CUSTOMS_INFO', 'CUSTOMS_RETURN'],
            'liberado_aduana' => ['EDN', 'EDH', 'HANDOVER_AGENT', 'EMI'],
            'disponible_retiro' => ['EMI'],
            'en_ruta_entrega' => ['EDG', 'EMH', 'EMI'],
            'intentos_carteros' => ['HANDOVER_AGENT'],
            default => [],
        };
    }

    public static function packageEventOptions(string $status, ?string $lastOperationCode = null): array
    {
        $normalizedStatus = PackageStatusCatalog::normalize($status);
        $allowedCodes = self::allowedPackageOperationCodes($status, $lastOperationCode);

        return collect(self::existingPackageEvents())
            ->only($allowedCodes)
            ->map(fn (array $event, string $code): array => [
                'operation_code' => $code,
                'event_type_cd' => $event['event_type_cd'],
                'name' => $event['name'],
                'status' => $event['status'],
                'requires_physical_confirmation' => (bool) ($event['requires_physical_confirmation'] ?? false),
                'required_fields' => array_values(array_merge(
                    $event['required_fields'] ?? [],
                    $code === 'HANDOVER_AGENT' && $normalizedStatus === 'intentos_carteros'
                        ? ['physical_return_confirmed']
                        : []
                )),
            ])
            ->values()
            ->all();
    }

    public static function bagEventOptions(string $status): array
    {
        $available = match ($status) {
            'pendiente_recepcion' => ['RECEPTACLE_RECEIVE'],
            'recibido' => ['RECEPTACLE_OPEN'],
            default => [],
        };

        return collect(self::BAG_EVENTS)
            ->only($available)
            ->map(fn (array $event, string $code): array => [
                'operation_code' => $code,
                'event_type_cd' => $event['event_type_cd'],
                'name' => $event['name'],
                'status' => $event['status'],
                'requires_physical_confirmation' => (bool) ($event['requires_physical_confirmation'] ?? false),
            ])
            ->values()
            ->all();
    }

    /** @return list<string> */
    public static function missingPackageContext(string $operationCode, array $context, string $currentStatus): array
    {
        $event = self::existingPackageEvent($operationCode);
        if (! $event) {
            return ['operation_code'];
        }

        $required = $event['required_fields'] ?? [];
        if ($operationCode === 'HANDOVER_AGENT' && $currentStatus === 'intentos_carteros') {
            $required[] = 'physical_return_confirmed';
        }

        return array_values(array_filter($required, fn (string $field): bool =>
            ! isset($context[$field]) || $context[$field] === '' || $context[$field] === false
        ));
    }
}
