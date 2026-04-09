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
        'cn31_manifest_id',
        'bag_number',
        'dispatch_number_bag',
        'declared_package_count',
        'declared_weight_kg',
        'status',
        'received_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
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
}
