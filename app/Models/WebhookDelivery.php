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
        'created_by_api_token_id',
        'event_id',
        'event',
        'tracking_code',
        'status',
        'attempt_count',
        'last_attempt_at',
        'next_attempt_at',
        'error_message',
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
            'last_attempt_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'attempt_count' => 'integer',
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
