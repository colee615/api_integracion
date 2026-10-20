<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_movements', function (Blueprint $table): void {
            $table->string('ips_operation_code', 40)->nullable()->after('event_code');
            $table->unsignedSmallInteger('ips_event_type_cd')->nullable()->after('ips_operation_code');
            $table->foreignId('actor_user_id')->nullable()->after('created_by_api_token_id')->constrained('users')->nullOnDelete();
            $table->boolean('physical_confirmation')->nullable()->after('is_public');
            $table->boolean('notify_webhook')->default(true)->after('physical_confirmation');
            $table->index(['package_id', 'ips_event_type_cd'], 'package_movements_ips_event_index');
        });

        Schema::table('cn31_bags', function (Blueprint $table): void {
            $table->string('operational_status', 40)->default('pendiente_recepcion')->after('status');
            $table->string('delivery_status', 30)->default('pendiente')->after('operational_status');
            $table->timestamp('last_operational_movement_at')->nullable()->after('received_at');
        });

        Schema::table('cn33_packages', function (Blueprint $table): void {
            $table->string('delivery_status', 30)->default('pendiente')->after('status');
            $table->index(['cn31_bag_id', 'delivery_status'], 'cn33_bag_delivery_status_index');
        });

        Schema::table('cn31_manifests', function (Blueprint $table): void {
            $table->string('delivery_status', 30)->default('pendiente')->after('status');
        });

        Schema::create('cn31_bag_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cn31_bag_id')->constrained('cn31_bags')->restrictOnDelete();
            $table->foreignId('created_by_api_token_id')->nullable()->constrained('api_tokens')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('event_id')->unique();
            $table->string('event_code', 100);
            $table->string('ips_operation_code', 40);
            $table->unsignedSmallInteger('ips_event_type_cd');
            $table->string('source_type', 60);
            $table->string('source_reference', 120)->nullable();
            $table->string('location_code', 80);
            $table->string('location')->nullable();
            $table->string('status', 40);
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('recorded_at');
            $table->boolean('physical_confirmation')->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'cn31_bag_id', 'idempotency_key'], 'cn31_bag_movements_idempotency_unique');
            $table->index(['cn31_bag_id', 'occurred_at', 'id'], 'cn31_bag_movements_timeline_index');
        });

        // Existing received_at timestamps are API-registration times. They do not
        // prove that the physical receptacle was received, so no IPS event is backfilled.
        Schema::table('cn31_bags', function (Blueprint $table): void {
            $table->index(['operational_status', 'delivery_status'], 'cn31_bags_tracking_status_index');
        });

        Schema::table('cn31_manifests', function (Blueprint $table): void {
            $table->index('delivery_status', 'cn31_manifests_delivery_status_index');
        });

        DB::table('cn33_packages')
            ->whereIn('status', ['entregado', 'intentos_carteros'])
            ->orderBy('id')
            ->chunkById(250, function ($items): void {
                foreach ($items as $item) {
                    $meta = is_string($item->meta ?? null)
                        ? (array) (json_decode($item->meta, true) ?? [])
                        : (array) ($item->meta ?? []);
                    $legacyStatus = (string) $item->status;
                    $meta['_ips_mirror_legacy_tracking_status'] = $legacyStatus;

                    DB::table('cn33_packages')->where('id', $item->id)->update([
                        'status' => $item->package_id ? 'documentado_cn22' : 'pendiente_cn22',
                        'delivery_status' => $legacyStatus === 'entregado' ? 'completado' : 'en_proceso',
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

        DB::table('cn31_bags')->where('status', 'entregado')->orderBy('id')->chunkById(250, function ($bags): void {
            foreach ($bags as $bag) {
                $meta = $this->decodeMeta($bag->meta ?? null);
                $meta['_ips_mirror_legacy_document_status'] = (string) $bag->status;
                $hasMismatch = (int) ($meta['package_difference'] ?? 0) !== 0
                    || abs((float) ($meta['weight_difference_kg'] ?? 0)) > 0.001;

                DB::table('cn31_bags')->where('id', $bag->id)->update([
                    'status' => $hasMismatch ? 'observado' : 'conciliado',
                    'delivery_status' => 'completado',
                ]);
            }
        });

        DB::table('cn31_manifests')->where('status', 'entregado')->orderBy('id')->chunkById(250, function ($manifests): void {
            foreach ($manifests as $manifest) {
                $meta = $this->decodeMeta($manifest->meta ?? null);
                $meta['_ips_mirror_legacy_document_status'] = (string) $manifest->status;
                $bagStatuses = DB::table('cn31_bags')
                    ->where('cn31_manifest_id', $manifest->id)
                    ->pluck('status');
                $allBagsReconciled = $bagStatuses->isNotEmpty()
                    && $bagStatuses->every(fn ($status): bool => in_array($status, ['conciliado', 'observado'], true));
                $documentStatus = ! $allBagsReconciled
                    ? 'pendiente_cn33'
                    : ($bagStatuses->contains('observado') ? 'observado' : 'conciliado');

                DB::table('cn31_manifests')->where('id', $manifest->id)->update([
                    'status' => $documentStatus,
                    'delivery_status' => 'completado',
                    'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        });

        // Rebuild delivery progress from the linked package snapshot. Older code
        // marked a whole bag/manifest delivered after an individual delivery.
        DB::table('cn33_packages')->whereNotNull('package_id')->orderBy('id')->chunkById(250, function ($items): void {
            $packageStatuses = DB::table('packages')
                ->whereIn('id', $items->pluck('package_id')->filter()->unique()->values())
                ->pluck('status', 'id');

            foreach ($items as $item) {
                DB::table('cn33_packages')->where('id', $item->id)->update([
                    'delivery_status' => $this->deliveryStatusForPackage((string) $packageStatuses->get($item->package_id, '')),
                ]);
            }
        });

        DB::table('cn31_bags')->orderBy('id')->chunkById(250, function ($bags): void {
            $packageStatusesByBag = DB::table('cn33_packages')
                ->whereIn('cn31_bag_id', $bags->pluck('id'))
                ->get(['cn31_bag_id', 'delivery_status'])
                ->groupBy('cn31_bag_id');

            foreach ($bags as $bag) {
                $statuses = ($packageStatusesByBag->get($bag->id) ?? collect())->pluck('delivery_status');
                DB::table('cn31_bags')->where('id', $bag->id)->update([
                    'delivery_status' => $this->aggregateDeliveryStatuses($statuses),
                ]);
            }
        });

        DB::table('cn31_manifests')->orderBy('id')->chunkById(250, function ($manifests): void {
            $bagStatusesByManifest = DB::table('cn31_bags')
                ->whereIn('cn31_manifest_id', $manifests->pluck('id'))
                ->get(['cn31_manifest_id', 'delivery_status'])
                ->groupBy('cn31_manifest_id');

            foreach ($manifests as $manifest) {
                $statuses = ($bagStatusesByManifest->get($manifest->id) ?? collect())->pluck('delivery_status');
                DB::table('cn31_manifests')->where('id', $manifest->id)->update([
                    'delivery_status' => $this->aggregateDeliveryStatuses($statuses),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('cn33_packages')->orderBy('id')->chunkById(250, function ($items): void {
            foreach ($items as $item) {
                $meta = is_string($item->meta ?? null)
                    ? (array) (json_decode($item->meta, true) ?? [])
                    : (array) ($item->meta ?? []);
                $legacyStatus = $meta['_ips_mirror_legacy_tracking_status'] ?? null;
                if (! is_string($legacyStatus)) {
                    continue;
                }

                unset($meta['_ips_mirror_legacy_tracking_status']);
                DB::table('cn33_packages')->where('id', $item->id)->update([
                    'status' => $legacyStatus,
                    'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        });
        foreach (['cn31_bags', 'cn31_manifests'] as $table) {
            DB::table($table)->orderBy('id')->chunkById(250, function ($items) use ($table): void {
                foreach ($items as $item) {
                    $meta = $this->decodeMeta($item->meta ?? null);
                    $legacyStatus = $meta['_ips_mirror_legacy_document_status'] ?? null;
                    if (! is_string($legacyStatus)) {
                        continue;
                    }

                    unset($meta['_ips_mirror_legacy_document_status']);
                    DB::table($table)->where('id', $item->id)->update([
                        'status' => $legacyStatus,
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
        }

        Schema::dropIfExists('cn31_bag_movements');

        Schema::table('package_movements', function (Blueprint $table): void {
            $table->dropIndex('package_movements_ips_event_index');
            $table->dropConstrainedForeignId('actor_user_id');
            $table->dropColumn(['ips_operation_code', 'ips_event_type_cd', 'physical_confirmation', 'notify_webhook']);
        });

        Schema::table('cn31_bags', function (Blueprint $table): void {
            $table->dropIndex('cn31_bags_tracking_status_index');
            $table->dropColumn(['operational_status', 'delivery_status', 'last_operational_movement_at']);
        });

        Schema::table('cn33_packages', function (Blueprint $table): void {
            $table->dropIndex('cn33_bag_delivery_status_index');
            $table->dropColumn('delivery_status');
        });

        Schema::table('cn31_manifests', function (Blueprint $table): void {
            $table->dropIndex('cn31_manifests_delivery_status_index');
            $table->dropColumn('delivery_status');
        });
    }

    private function decodeMeta(mixed $meta): array
    {
        if (is_string($meta)) {
            return (array) (json_decode($meta, true) ?? []);
        }

        return (array) ($meta ?? []);
    }

    private function deliveryStatusForPackage(string $status): string
    {
        if ($status === 'entregado') {
            return 'completado';
        }

        return in_array($status, ['registrado', 'pre_alerta_recibida'], true)
            ? 'pendiente'
            : 'en_proceso';
    }

    private function aggregateDeliveryStatuses(\Illuminate\Support\Collection $statuses): string
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
};
