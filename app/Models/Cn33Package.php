<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cn33Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'cn31_bag_id',
        'package_id',
        'tracking_code',
        'reference',
        'recipient_name',
        'origin',
        'destination',
        'weight_kg',
        'item_order',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bag(): BelongsTo
    {
        return $this->belongsTo(Cn31Bag::class, 'cn31_bag_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
