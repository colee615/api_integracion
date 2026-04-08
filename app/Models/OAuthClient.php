<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OAuthClient extends Model
{
    use HasFactory;

    protected $table = 'oauth_clients';

    protected $fillable = [
        'company_id',
        'name',
        'client_id',
        'client_secret_hash',
        'client_secret',
        'abilities',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(OAuthAccessToken::class, 'oauth_client_id');
    }

    public function canUse(): bool
    {
        return $this->revoked_at === null && $this->company?->status === 'active';
    }

    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->client_secret_hash, hash('sha256', $secret));
    }

    public static function issue(Company $company, string $name, array $abilities = ['packages:read', 'packages:write']): array
    {
        $clientId = Str::upper(Str::random(24));
        $plainSecret = Str::random(64);

        $client = $company->oauthClients()->create([
            'name' => $name,
            'client_id' => $clientId,
            'client_secret_hash' => hash('sha256', $plainSecret),
            'client_secret' => $plainSecret,
            'abilities' => $abilities,
        ]);

        return [$client, $plainSecret];
    }

    public function maskedSecret(): string
    {
        if (! $this->client_secret) {
            return 'Credential not available';
        }

        return substr($this->client_secret, 0, 4).str_repeat('*', max(strlen($this->client_secret) - 8, 8)).substr($this->client_secret, -4);
    }
}
