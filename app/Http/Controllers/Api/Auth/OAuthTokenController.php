<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OAuthAccessToken;
use App\Models\OAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

class OAuthTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grant_type' => ['required', 'in:client_credentials'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ], [
            'grant_type.in' => __('api.validation.oauth_grant_type_supported'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('api.errors.oauth_invalid_request'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $apiKey = $request->header('X-API-Key', (string) $request->input('api_key', ''));

        $company = Company::query()
            ->where('api_key', $apiKey)
            ->first();

        if (! $company) {
            return response()->json([
                'message' => __('api.errors.oauth_invalid_api_key'),
            ], 401);
        }

        App::setLocale($company->locale ?? 'es');

        $client = OAuthClient::query()
            ->where('company_id', $company->id)
            ->where('client_id', (string) $request->input('client_id'))
            ->first();

        if (! $client || ! $client->canUse() || ! $client->verifySecret((string) $request->input('client_secret'))) {
            return response()->json([
                'message' => __('api.errors.oauth_invalid_credentials'),
            ], 401);
        }

        if ($company->environment === 'sandbox' && ! $company->isSandboxActive()) {
            return response()->json([
                'message' => __('api.errors.sandbox_window_closed'),
            ], 403);
        }

        if ($company->environment === 'production' && ! $company->canUseProduction()) {
            return response()->json([
                'message' => __('api.errors.production_not_enabled'),
            ], 403);
        }

        [$accessToken, $plainTextToken] = OAuthAccessToken::issue($client, now()->addHour(), [
            'grant_type' => 'client_credentials',
        ]);

        $client->forceFill([
            'last_used_at' => now(),
        ])->save();

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plainTextToken,
            'expires_in' => 3600,
            'scope' => implode(' ', $accessToken->abilities ?? []),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'environment' => $company->environment,
                'sandbox' => [
                    'starts_at' => $company->sandbox_starts_at?->toIso8601String(),
                    'ends_at' => $company->sandbox_ends_at?->toIso8601String(),
                    'max_shipments' => $company->sandbox_max_shipments,
                    'used_shipments' => $company->sandbox_shipments_used,
                ],
            ],
        ]);
    }
}
