<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCompanyPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return response()->json([
                'message' => __('api.errors.portal_session_missing'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $sessionData = Cache::get('company_portal_session:'.$bearerToken);

        if (! $sessionData || empty($sessionData['user_id'])) {
            return response()->json([
                'message' => __('api.errors.portal_session_invalid'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::with('company')->find($sessionData['user_id']);

        if (! $user || ! $user->isCompany() || ! $user->isActive() || $user->company?->status !== 'active') {
            Cache::forget('company_portal_session:'.$bearerToken);

            return response()->json([
                'message' => __('api.errors.company_access_inactive'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        App::setLocale($user->company?->locale ?? 'es');
        $request->attributes->set('companyPortalUser', $user);
        $request->attributes->set('companyPortalSessionToken', $bearerToken);

        return $next($request);
    }
}
