<?php

namespace App\Support;

use App\Models\ApiToken;
use Illuminate\Http\Request;

class ApiTokenContext
{
    public static function currentToken(Request $request): ?ApiToken
    {
        $token = $request->attributes->get('currentApiToken');

        return $token instanceof ApiToken ? $token : null;
    }

    public static function scopeToToken(mixed $query, ?ApiToken $token, string $column = 'created_by_api_token_id'): mixed
    {
        if ($token?->isTrial()) {
            return $query->where($column, $token->id);
        }

        return $query->whereNull($column);
    }

    public static function applyCompanyScope(mixed $query, int $companyId, ?ApiToken $token, string $column = 'created_by_api_token_id'): mixed
    {
        return static::scopeToToken(
            $query->where('company_id', $companyId),
            $token,
            $column
        );
    }

    public static function ownershipAttributes(?ApiToken $token): array
    {
        return [
            'created_by_api_token_id' => $token?->isTrial() ? $token->id : null,
        ];
    }
}
