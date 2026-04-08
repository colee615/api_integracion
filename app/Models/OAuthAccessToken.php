<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OAuthAccessToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_access_tokens';

    protected $fillable = [
        'company_id',
        'oauth_client_id',
        'token_hash',
        'token_secret',
        'abilities',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'token_secret' => 'encrypted',
            'abilities' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(OAuthClient::class, 'oauth_client_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof CarbonInterface && $this->expires_at->isPast();
    }

    public function canUse(): bool
    {
        return ! $this->isExpired()
            && $this->revoked_at === null
            && $this->client?->canUse()
            && $this->company?->status === 'active';
    }

    public function touchLastUsed(): void
    {
        $this->forceFill([
            'last_used_at' => now(),
        ])->save();
    }

    public static function issue(OAuthClient $client, CarbonInterface $expiresAt, array $meta = []): array
    {
        $plainToken = Str::random(80);

        $token = $client->accessTokens()->create([
            'company_id' => $client->company_id,
            'token_hash' => hash('sha256', $plainToken),
            'token_secret' => $plainToken,
            'abilities' => $client->abilities ?? [],
            'expires_at' => $expiresAt,
            'meta' => $meta,
        ]);

        return [$token, $plainToken];
    }

    public function maskedToken(): string
    {
        if (! $this->token_secret) {
            return 'Token not available';
        }

        return substr($this->token_secret, 0, 4).str_repeat('*', max(strlen($this->token_secret) - 8, 8)).substr($this->token_secret, -4);
    }
}
