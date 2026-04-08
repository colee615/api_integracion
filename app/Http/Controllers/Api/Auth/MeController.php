<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $token = $request->attributes->get('currentApiToken');
        $authMode = $request->attributes->get('currentAuthMode', 'legacy_token');

        return response()->json([
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'status' => $company->status,
                    'environment' => $company->environment,
                ],
                'token' => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'abilities' => $token->abilities ?? [],
                    'auth_mode' => $authMode,
                ],
            ],
        ]);
    }
}
