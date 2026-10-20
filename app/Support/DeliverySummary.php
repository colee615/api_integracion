<?php

namespace App\Support;

use Illuminate\Support\Collection;

class DeliverySummary
{
    public static function forPackageRows(Collection $packages): array
    {
        $counts = $packages->countBy('delivery_status');

        return [
            'registered_packages' => $packages->count(),
            'delivered_packages' => (int) $counts->get('completado', 0),
            'in_process_packages' => (int) $counts->get('en_proceso', 0),
            'pending_packages' => (int) $counts->get('pendiente', 0),
        ];
    }

    public static function forDispatch(Collection $bags): array
    {
        $bagCounts = $bags->countBy('delivery_status');
        $packages = $bags->flatMap(fn ($bag) => $bag->cn33Packages);

        return [
            'registered_bags' => $bags->count(),
            'completed_bags' => (int) $bagCounts->get('completado', 0),
            'in_process_bags' => (int) $bagCounts->get('en_proceso', 0),
            'pending_bags' => (int) $bagCounts->get('pendiente', 0),
            ...self::forPackageRows($packages),
        ];
    }
}
