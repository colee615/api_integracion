<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->index(['tracking_code', 'created_by_api_token_id'], 'packages_internal_tracking_lookup_index');
        });

        Schema::table('package_movements', function (Blueprint $table): void {
            $table->uuid('event_id')->nullable()->unique();
            $table->string('event_code', 100)->nullable()->index();
            $table->string('source_type', 60)->nullable();
            $table->string('source_reference', 120)->nullable();
            $table->string('location_code', 80)->nullable();
            $table->string('reason_code', 80)->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->boolean('is_public')->default(true);

            $table->unique(['company_id', 'package_id', 'idempotency_key'], 'package_movements_company_package_idempotency_unique');
            $table->index(['company_id', 'package_id', 'occurred_at'], 'package_movements_timeline_index');
        });

        DB::table('package_movements')->orderBy('id')->chunkById(250, function ($movements): void {
            foreach ($movements as $movement) {
                $meta = is_string($movement->meta ?? null)
                    ? (array) (json_decode($movement->meta, true) ?? [])
                    : (array) ($movement->meta ?? []);
                $status = (string) $movement->status;
                $canonicalStatus = $status === 'clasificado' ? 'recibido_centro_clasificacion' : $status;

                DB::table('package_movements')->where('id', $movement->id)->update([
                    'event_id' => (string) Str::uuid(),
                    'event_code' => $this->eventCodeFor($canonicalStatus),
                    'source_type' => $this->sourceTypeFromMeta($meta),
                    'recorded_at' => $movement->created_at ?? $movement->occurred_at,
                    'is_public' => true,
                ]);
            }
        });

        DB::table('packages')->orderBy('id')->chunkById(250, function ($packages): void {
            foreach ($packages as $package) {
                $hasMovements = DB::table('package_movements')
                    ->where('package_id', $package->id)
                    ->exists();

                if ($hasMovements) {
                    continue;
                }

                $status = (string) ($package->status ?? 'registrado');
                $canonicalStatus = $status === 'clasificado' ? 'recibido_centro_clasificacion' : $status;
                $occurredAt = $package->last_movement_at
                    ?? $package->registered_at
                    ?? $package->created_at
                    ?? now();

                DB::table('package_movements')->insert([
                    'company_id' => $package->company_id,
                    'created_by_api_token_id' => $package->created_by_api_token_id ?? null,
                    'package_id' => $package->id,
                    'event_id' => (string) Str::uuid(),
                    'event_code' => $this->eventCodeFor($canonicalStatus),
                    'source_type' => 'legacy_backfill',
                    'status' => $canonicalStatus,
                    'location' => null,
                    'location_code' => null,
                    'description' => 'Initial tracking history imported from the existing shipment status.',
                    'occurred_at' => $occurredAt,
                    'recorded_at' => $package->created_at ?? now(),
                    'is_public' => true,
                    'meta' => json_encode(['source' => 'tracking_ledger_backfill']),
                    'created_at' => $package->created_at ?? now(),
                    'updated_at' => $package->updated_at ?? now(),
                ]);
            }
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('event_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unique(['webhook_endpoint_id', 'event_id'], 'webhook_endpoint_event_unique');
            $table->index(['status', 'next_attempt_at'], 'webhook_delivery_retry_index');
        });

        DB::table('webhook_deliveries')->orderBy('id')->chunkById(250, function ($deliveries): void {
            foreach ($deliveries as $delivery) {
                DB::table('webhook_deliveries')->where('id', $delivery->id)->update([
                    'event_id' => (string) Str::uuid(),
                    'status' => $delivery->success ? 'delivered' : 'failed',
                    'attempt_count' => $delivery->delivered_at ? 1 : 0,
                    'last_attempt_at' => $delivery->delivered_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropIndex('packages_internal_tracking_lookup_index');
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->dropUnique('webhook_endpoint_event_unique');
            $table->dropIndex('webhook_delivery_retry_index');
            $table->dropColumn([
                'event_id', 'status', 'attempt_count', 'last_attempt_at', 'next_attempt_at', 'error_message',
            ]);
        });

        Schema::table('package_movements', function (Blueprint $table): void {
            $table->dropUnique('package_movements_company_package_idempotency_unique');
            $table->dropIndex('package_movements_timeline_index');
            $table->dropIndex(['event_code']);
            $table->dropUnique(['event_id']);
            $table->dropColumn([
                'event_id', 'event_code', 'source_type', 'source_reference', 'location_code',
                'idempotency_key', 'request_hash', 'recorded_at', 'reason_code', 'is_public',
            ]);
        });
    }

    private function eventCodeFor(string $status): string
    {
        return match ($status) {
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
            default => 'shipment.status_updated',
        };
    }

    private function sourceTypeFromMeta(array $meta): string
    {
        $source = strtolower((string) ($meta['source'] ?? ''));

        if (str_contains($source, 'internal')) {
            return 'internal_api';
        }

        if (str_contains($source, 'api')) {
            return 'company_api';
        }

        return 'legacy';
    }
};
