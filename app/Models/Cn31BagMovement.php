<?php

namespace App\Models;

use App\Support\IpsEventCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class Cn31BagMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cn31_bag_id',
        'created_by_api_token_id',
        'actor_user_id',
        'event_id',
        'event_code',
        'ips_operation_code',
        'ips_event_type_cd',
        'source_type',
        'source_reference',
        'location_code',
        'location',
        'status',
        'description',
        'occurred_at',
        'recorded_at',
        'physical_confirmation',
        'idempotency_key',
        'request_hash',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'recorded_at' => 'datetime',
            'physical_confirmation' => 'boolean',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Cn31BagMovement $movement): never {
            throw new LogicException('Tracking events are append-only. Record a correction as a new event.');
        });

        static::deleting(function (Cn31BagMovement $movement): never {
            throw new LogicException('Tracking events are append-only.');
        });

        static::creating(function (Cn31BagMovement $movement): void {
            $movement->event_id ??= (string) Str::uuid();
            $operation = IpsEventCatalog::bagEvent((string) $movement->ips_operation_code);
            $movement->ips_event_type_cd ??= $operation['event_type_cd'] ?? null;
            $movement->event_code ??= match ($movement->ips_operation_code) {
                'RECEPTACLE_RECEIVE' => 'receptacle.received',
                'RECEPTACLE_OPEN' => 'receptacle.opened',
                default => 'receptacle.event_recorded',
            };
            $movement->recorded_at ??= now()->utc();
        });
    }

    public function bag(): BelongsTo
    {
        return $this->belongsTo(Cn31Bag::class, 'cn31_bag_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
