<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'token_hash',
        'token_secret',
        'last_used_at',
        'starts_at',
        'expires_at',
        'revoked_at',
        'abilities',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'abilities' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getTokenSecretAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $this->looksLikeEncryptedPayload($value) ? null : $value;
        }
    }

    public function setTokenSecretAttribute(?string $value): void
    {
        $this->attributes['token_secret'] = $value === null || $value === ''
            ? null
            : Crypt::encryptString($value);
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof CarbonInterface
            && $this->expires_at->isPast();
    }

    public function hasStarted(): bool
    {
        return ! $this->starts_at instanceof CarbonInterface
            || ! $this->starts_at->isFuture();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function canUse(): bool
    {
        return ! $this->isExpired()
            && $this->hasStarted()
            && ! $this->isRevoked()
            && $this->company?->status === 'active';
    }

    public function touchLastUsed(): void
    {
        $this->forceFill([
            'last_used_at' => now(),
        ])->save();
    }

    public static function issue(
        Company $company,
        string $name,
        CarbonInterface $startsAt,
        CarbonInterface $expiresAt,
        array $abilities = ['packages:write', 'packages:read']
    ): array {
        $plainTextToken = Str::random(64);

        $token = $company->apiTokens()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plainTextToken),
            'token_secret' => $plainTextToken,
            'starts_at' => Carbon::instance($startsAt),
            'expires_at' => Carbon::instance($expiresAt),
            'abilities' => $abilities,
        ]);

        return [$token, $plainTextToken];
    }

    public function maskedToken(): string
    {
        $tokenSecret = $this->token_secret;

        if (! $tokenSecret) {
            return 'Token no disponible';
        }

        return substr($tokenSecret, 0, 4).str_repeat('*', max(strlen($tokenSecret) - 8, 8)).substr($tokenSecret, -4);
    }

    protected function looksLikeEncryptedPayload(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && array_key_exists('iv', $payload)
            && array_key_exists('value', $payload)
            && array_key_exists('mac', $payload);
    }
}
