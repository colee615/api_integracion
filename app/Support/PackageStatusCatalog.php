<?php

namespace App\Support;

class PackageStatusCatalog
{
    public static function allowedStatuses(): array
    {
        return [
            'registrado',
            'pre_alerta_recibida',
            'recibido_api',
            'recibido_centro_clasificacion',
            'clasificado',
            'en_proceso_aduana',
            'liberado_aduana',
            'en_ruta_entrega',
            'en_transito',
            'entregado',
            'incidencia_entrega',
        ];
    }

    public static function webhookEvents(): array
    {
        return [
            'shipment.received_sorting_center',
            'shipment.customs_in_progress',
            'shipment.customs_released',
            'shipment.out_for_delivery',
            'shipment.delivered',
            'shipment.delivery_incident',
        ];
    }

    public static function normalize(string $status): string
    {
        return match ($status) {
            'clasificado' => 'recibido_centro_clasificacion',
            'en_transito' => 'en_ruta_entrega',
            default => $status,
        };
    }

    public static function eventForStatus(string $status): ?string
    {
        return match (self::normalize($status)) {
            'recibido_centro_clasificacion' => 'shipment.received_sorting_center',
            'en_proceso_aduana' => 'shipment.customs_in_progress',
            'liberado_aduana' => 'shipment.customs_released',
            'en_ruta_entrega' => 'shipment.out_for_delivery',
            'entregado' => 'shipment.delivered',
            'incidencia_entrega' => 'shipment.delivery_incident',
            default => null,
        };
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
