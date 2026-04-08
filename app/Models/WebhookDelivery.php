<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'company_id',
        'event',
        'tracking_code',
        'response_status',
        'success',
        'delivered_at',
        'response_body',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'delivered_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
