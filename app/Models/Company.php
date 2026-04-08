<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_key',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
        'environment',
        'sandbox_starts_at',
        'sandbox_ends_at',
        'sandbox_max_shipments',
        'sandbox_shipments_used',
        'production_enabled_at',
        'locale',
        'integration_settings',
    ];

    protected function casts(): array
    {
        return [
            'sandbox_starts_at' => 'datetime',
            'sandbox_ends_at' => 'datetime',
            'production_enabled_at' => 'datetime',
            'integration_settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (! $company->api_key) {
                $company->api_key = Str::upper(Str::random(40));
            }
        });
    }

    public function isSandboxActive(): bool
    {
        if ($this->environment !== 'sandbox') {
            return false;
        }

        $starts = $this->sandbox_starts_at;
        $ends = $this->sandbox_ends_at;

        return ($starts === null || ! $starts->isFuture())
            && ($ends === null || ! $ends->isPast())
            && $this->sandbox_shipments_used < $this->sandbox_max_shipments;
    }

    public function canUseProduction(): bool
    {
        return $this->environment === 'production' || $this->production_enabled_at !== null;
    }

    public function registerSandboxShipment(int $count = 1): void
    {
        if ($this->environment !== 'sandbox') {
            return;
        }

        $this->increment('sandbox_shipments_used', $count);
    }

    public function oauthClients(): HasMany
    {
        return $this->hasMany(OAuthClient::class);
    }

    public function oauthAccessTokens(): HasMany
    {
        return $this->hasMany(OAuthAccessToken::class);
    }

    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PackageMovement::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function cn31Manifests(): HasMany
    {
        return $this->hasMany(Cn31Manifest::class);
    }

    public function cn31Bags(): HasMany
    {
        return $this->hasMany(Cn31Bag::class);
    }

    public function cn33Packages(): HasMany
    {
        return $this->hasMany(Cn33Package::class);
    }
}
