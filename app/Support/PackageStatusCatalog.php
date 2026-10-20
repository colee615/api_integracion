<?php

namespace App\Support;

/**
 * Canonical business statuses and transitions for shipment tracking.
 * Human labels are translated separately; API status values remain stable.
 */
class PackageStatusCatalog
{
    private const TRANSITIONS = [
        'registrado' => [
            'pre_alerta_recibida', 'recibido_api', 'aceptado_en_origen', 'recibido_centro_clasificacion',
            'despachado_origen', 'en_transito', 'arribado_destino', 'en_proceso_aduana',
            'retenido_aduana', 'disponible_retiro', 'en_ruta_entrega', 'intentos_carteros',
            'incidencia_entrega', 'entregado', 'perdido',
        ],
        'pre_alerta_recibida' => [
            'recibido_api', 'aceptado_en_origen', 'recibido_centro_clasificacion', 'despachado_origen',
            'en_transito', 'arribado_destino', 'en_proceso_aduana', 'retenido_aduana',
            'disponible_retiro', 'en_ruta_entrega', 'intentos_carteros', 'incidencia_entrega',
            'entregado', 'perdido',
        ],
        'recibido_api' => [
            'aceptado_en_origen', 'recibido_centro_clasificacion', 'despachado_origen', 'en_transito',
            'arribado_destino', 'en_proceso_aduana', 'retenido_aduana', 'liberado_aduana',
            'disponible_retiro', 'en_ruta_entrega', 'intentos_carteros', 'incidencia_entrega',
            'entregado', 'perdido',
        ],
        'aceptado_en_origen' => [
            'recibido_centro_clasificacion', 'despachado_origen', 'en_transito', 'incidencia_entrega',
            'entregado', 'perdido',
        ],
        'recibido_centro_clasificacion' => [
            'despachado_origen', 'en_transito', 'arribado_destino', 'en_proceso_aduana', 'retenido_aduana',
            'liberado_aduana', 'disponible_retiro', 'en_ruta_entrega', 'intentos_carteros',
            'incidencia_entrega', 'en_devolucion', 'entregado', 'perdido',
        ],
        'despachado_origen' => [
            'en_transito', 'recibido_centro_clasificacion', 'arribado_destino', 'en_proceso_aduana',
            'retenido_aduana', 'incidencia_entrega', 'en_devolucion', 'entregado', 'perdido',
        ],
        'en_transito' => [
            'recibido_api', 'aceptado_en_origen', 'recibido_centro_clasificacion', 'despachado_origen',
            'arribado_destino', 'en_proceso_aduana', 'retenido_aduana', 'liberado_aduana',
            'disponible_retiro', 'en_ruta_entrega', 'intentos_carteros', 'incidencia_entrega',
            'en_devolucion', 'entregado', 'perdido',
        ],
        'arribado_destino' => [
            'recibido_centro_clasificacion', 'despachado_origen', 'en_proceso_aduana', 'retenido_aduana', 'liberado_aduana',
            'disponible_retiro', 'en_ruta_entrega', 'incidencia_entrega', 'en_devolucion', 'entregado', 'perdido',
        ],
        'en_proceso_aduana' => [
            'retenido_aduana', 'liberado_aduana', 'despachado_origen', 'en_transito', 'incidencia_entrega',
            'en_devolucion', 'entregado', 'perdido',
        ],
        'retenido_aduana' => [
            'en_proceso_aduana', 'liberado_aduana', 'en_transito', 'incidencia_entrega', 'en_devolucion', 'perdido',
        ],
        'liberado_aduana' => [
            'en_transito', 'recibido_centro_clasificacion', 'despachado_origen', 'en_ruta_entrega',
            'disponible_retiro', 'intentos_carteros', 'incidencia_entrega', 'en_devolucion', 'entregado', 'perdido',
        ],
        'disponible_retiro' => ['en_ruta_entrega', 'entregado', 'incidencia_entrega', 'en_devolucion', 'perdido'],
        'en_ruta_entrega' => [
            'entregado', 'intentos_carteros', 'incidencia_entrega', 'en_transito', 'en_devolucion', 'perdido',
        ],
        'intentos_carteros' => [
            'en_ruta_entrega', 'incidencia_entrega', 'en_transito', 'en_devolucion', 'entregado', 'perdido',
        ],
        'incidencia_entrega' => [
            'en_transito', 'en_proceso_aduana', 'en_ruta_entrega', 'intentos_carteros', 'en_devolucion',
            'entregado', 'perdido',
        ],
        'en_devolucion' => ['devuelto_remitente', 'perdido'],
    ];

    private const EVENT_CODES = [
        'registrado' => 'shipment.registered',
        'pre_alerta_recibida' => 'shipment.pre_alert_received',
        'recibido_api' => 'shipment.received_by_operator',
        'aceptado_en_origen' => 'shipment.accepted_at_origin',
        'recibido_centro_clasificacion' => 'shipment.received_sorting_center',
        'despachado_origen' => 'shipment.departed_origin',
        'en_transito' => 'shipment.in_transit',
        'arribado_destino' => 'shipment.arrived_destination',
        'en_proceso_aduana' => 'shipment.customs_in_progress',
        'retenido_aduana' => 'shipment.customs_held',
        'liberado_aduana' => 'shipment.customs_released',
        'disponible_retiro' => 'shipment.available_for_pickup',
        'en_ruta_entrega' => 'shipment.out_for_delivery',
        'intentos_carteros' => 'shipment.delivery_attempt',
        'incidencia_entrega' => 'shipment.delivery_incident',
        'en_devolucion' => 'shipment.return_started',
        'devuelto_remitente' => 'shipment.returned_to_sender',
        'entregado' => 'shipment.delivered',
        'perdido' => 'shipment.lost',
    ];

    public static function allowedStatuses(): array
    {
        return [
            'registrado',
            'pre_alerta_recibida',
            'recibido_api',
            'aceptado_en_origen',
            'recibido_centro_clasificacion',
            'clasificado', // Legacy alias retained for existing integrations.
            'en_transito',
            'despachado_origen',
            'arribado_destino',
            'en_proceso_aduana',
            'retenido_aduana',
            'liberado_aduana',
            'disponible_retiro',
            'en_ruta_entrega',
            'entregado',
            'intentos_carteros',
            'incidencia_entrega',
            'en_devolucion',
            'devuelto_remitente',
            'perdido',
        ];
    }

    public static function webhookEvents(): array
    {
        return array_values(array_unique(array_values(self::EVENT_CODES)));
    }

    public static function recommendedReasonCodes(): array
    {
        return [
            'recipient_unavailable',
            'address_incomplete',
            'address_not_found',
            'access_restricted',
            'recipient_refused',
            'customs_documents_required',
            'customs_duties_due',
            'customs_inspection',
            'weather_disruption',
            'shipment_damaged',
            'other',
        ];
    }

    public static function normalize(string $status): string
    {
        return match ($status) {
            'clasificado' => 'recibido_centro_clasificacion',
            default => $status,
        };
    }

    public static function eventForStatus(string $status): ?string
    {
        return self::EVENT_CODES[self::normalize($status)] ?? null;
    }

    public static function statusForEvent(string $eventCode): ?string
    {
        $status = array_search($eventCode, self::EVENT_CODES, true);

        return $status === false ? null : $status;
    }

    public static function canTransition(string $from, string $to): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return true;
        }

        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function nextStatuses(string $status): array
    {
        return self::TRANSITIONS[self::normalize($status)] ?? [];
    }

    public static function isTerminal(string $status): bool
    {
        return in_array(self::normalize($status), ['entregado', 'devuelto_remitente', 'perdido'], true);
    }

    public static function labelForStatus(string $status): string
    {
        $normalized = self::normalize($status);
        $translated = __('api.statuses.'.$normalized);

        if ($translated !== 'api.statuses.'.$normalized) {
            return $translated;
        }

        return ucfirst(str_replace('_', ' ', $normalized));
    }
}
