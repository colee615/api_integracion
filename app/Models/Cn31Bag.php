<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cn31Bag extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'created_by_api_token_id',
        'cn31_manifest_id',
        'bag_number',
        'declared_package_count',
        'declared_weight_kg',
        'status',
        'operational_status',
        'delivery_status',
        'received_at',
        'last_operational_movement_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'last_operational_movement_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Cn31Manifest::class, 'cn31_manifest_id');
    }

    public function cn33Packages(): HasMany
    {
        return $this->hasMany(Cn33Package::class)->latest();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Cn31BagMovement::class)->latest('occurred_at');
    }
}
