<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cn31Manifest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cn31_number',
        'origin_office',
        'destination_office',
        'dispatch_date',
        'total_bags',
        'total_packages',
        'total_weight_kg',
        'status',
        'received_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'dispatch_date' => 'datetime',
            'received_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bags(): HasMany
    {
        return $this->hasMany(Cn31Bag::class)->latest();
    }
}
