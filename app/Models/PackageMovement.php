<?php

namespace App\Models;

use App\Support\PackageStatusCatalog;
use App\Support\IpsEventCatalog;
use App\Services\WebhookNotifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class PackageMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'created_by_api_token_id',
        'package_id',
        'event_id',
        'event_code',
        'ips_operation_code',
        'ips_event_type_cd',
        'actor_user_id',
        'source_type',
        'source_reference',
        'location_code',
        'reason_code',
        'idempotency_key',
        'request_hash',
        'status',
        'location',
        'description',
        'occurred_at',
        'recorded_at',
        'is_public',
        'physical_confirmation',
        'notify_webhook',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'recorded_at' => 'datetime',
            'is_public' => 'boolean',
            'physical_confirmation' => 'boolean',
            'notify_webhook' => 'boolean',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (PackageMovement $movement): never {
            throw new LogicException('Tracking events are append-only. Record a correction as a new event.');
        });

        static::deleting(function (PackageMovement $movement): never {
            throw new LogicException('Tracking events are append-only.');
        });

        static::creating(function (PackageMovement $movement): void {
            $movement->event_id ??= (string) Str::uuid();
            $movement->event_code ??= PackageStatusCatalog::eventForStatus((string) $movement->status)
                ?? 'shipment.status_updated';
            $movement->recorded_at ??= now()->utc();
            $movement->is_public ??= true;
            $movement->notify_webhook ??= $movement->is_public;

            if ($movement->ips_operation_code !== null) {
                $operation = IpsEventCatalog::existingPackageEvent((string) $movement->ips_operation_code);
                $movement->ips_event_type_cd ??= $operation['event_type_cd'] ?? null;
            }

            if ($movement->source_type === null) {
                $source = strtolower((string) (($movement->meta ?? [])['source'] ?? ''));
                $movement->source_type = $movement->created_by_api_token_id
                    ? 'company_api'
                    : (str_contains($source, 'internal')
                        ? 'internal_api'
                        : (str_contains($source, 'api') ? 'company_api' : 'system'));
            }
        });

        static::created(function (PackageMovement $movement): void {
            if (! $movement->is_public || ! $movement->notify_webhook) {
                return;
            }

            $package = $movement->package()->with('company')->first();
            if (! $package) {
                return;
            }

            app(WebhookNotifier::class)->dispatchForPackage($package, $movement->status, [
                'event_id' => $movement->event_id,
                'movement_id' => $movement->id,
                'occurred_at' => $movement->occurred_at?->utc()->toIso8601String(),
                'location' => $movement->location,
                'reason_code' => $movement->reason_code,
                'description' => $movement->displayDescription(),
                'source_reference' => $movement->source_reference,
                'is_public' => $movement->is_public,
                'ips_operation_code' => $movement->ips_operation_code,
                'ips_event_type_cd' => $movement->ips_event_type_cd,
                'physical_confirmation' => $movement->physical_confirmation,
                'notify_webhook' => $movement->notify_webhook,
            ]);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function displayDescription(): ?string
    {
        if (
            $this->event_code === 'shipment.pre_alert_received'
            && strcasecmp(trim((string) ($this->company?->name ?? '')), '360 Lions') === 0
        ) {
            return 'Aviso del envío recibido de 360 Lions';
        }

        return $this->description;
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
