<?php

namespace App\Http\Middleware;

use App\Support\ApiTokenContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $token = ApiTokenContext::currentToken($request);

        if (! $token || ! $token->allows($ability)) {
            return response()->json([
                'code' => 'INSUFFICIENT_ABILITY',
                'message' => __('api.errors.insufficient_ability'),
                'required_ability' => $ability,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
