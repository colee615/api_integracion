<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Http\Middleware\SetCompanyLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return response()->json([
                'message' => __('api.errors.token_missing'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $apiToken = ApiToken::with('company')
            ->where('token_hash', hash('sha256', $bearerToken))
            ->first();

        if (! $apiToken || ! $apiToken->canUse()) {
            return response()->json([
                'message' => __('api.errors.token_invalid'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $apiToken->touchLastUsed();
        App::setLocale(SetCompanyLocale::resolveLocale($request, $apiToken->company?->locale));
        $request->attributes->set('currentCompany', $apiToken->company);
        $request->attributes->set('currentApiToken', $apiToken);
        $request->attributes->set('currentAuthMode', 'legacy_token');

        return $next($request);
    }
}
