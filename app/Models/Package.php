<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'tracking_code',
        'reference',
        'sender_name',
        'sender_country',
        'sender_address',
        'sender_phone',
        'recipient_name',
        'recipient_document',
        'recipient_phone',
        'recipient_whatsapp',
        'recipient_city',
        'recipient_department',
        'recipient_address_reference',
        'destination',
        'origin_office',
        'destination_office',
        'recipient_address',
        'shipment_description',
        'shipment_date',
        'gross_weight_grams',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'value_fob_usd',
        'currency_code',
        'pre_alert_at',
        'tracking_standard',
        'customs_items',
        'status',
        'registered_at',
        'last_movement_at',
        'delivery_attempts',
        'last_delivery_attempt_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'last_movement_at' => 'datetime',
            'last_delivery_attempt_at' => 'datetime',
            'shipment_date' => 'datetime',
            'pre_alert_at' => 'datetime',
            'delivery_attempts' => 'integer',
            'gross_weight_grams' => 'integer',
            'weight_kg' => 'decimal:3',
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'value_fob_usd' => 'decimal:2',
            'customs_items' => 'array',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PackageMovement::class)->latest('occurred_at');
    }

    public function cn33Package(): HasOne
    {
        return $this->hasOne(Cn33Package::class);
    }

    public function latestDeliveryAttemptDescription(): ?string
    {
        return $this->meta['last_delivery_attempt']['description'] ?? null;
    }

    public function latestDeliveryAttemptLocation(): ?string
    {
        return $this->meta['last_delivery_attempt']['location'] ?? null;
    }
}
