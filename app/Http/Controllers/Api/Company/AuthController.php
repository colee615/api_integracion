<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('company')
            ->where('email', $validated['email'])
            ->where('role', 'company')
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => __('api.errors.invalid_credentials'),
            ], 422);
        }

        App::setLocale($user->company?->locale ?? 'es');

        if (! $user->isActive() || $user->company?->status !== 'active') {
            return response()->json([
                'message' => __('api.errors.company_access_inactive'),
            ], 403);
        }

        $token = Str::random(80);

        Cache::put('company_portal_session:'.$token, [
            'user_id' => $user->id,
        ], now()->addHours(12));

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'message' => __('api.messages.portal_login_success'),
            'data' => [
                'token' => $token,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'empresa',
                ],
                'company' => [
                    'id' => $user->company?->id,
                    'name' => $user->company?->name,
                    'slug' => $user->company?->slug,
                    'status' => $user->company?->status,
                    'locale' => $user->company?->locale ?? 'es',
                ],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('companyPortalUser');

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                ],
                'company' => [
                    'id' => $user->company?->id,
                    'name' => $user->company?->name,
                    'slug' => $user->company?->slug,
                    'status' => $user->company?->status,
                    'locale' => $user->company?->locale ?? 'es',
                    'contact_name' => $user->company?->contact_name,
                    'contact_email' => $user->company?->contact_email,
                    'contact_phone' => $user->company?->contact_phone,
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('companyPortalSessionToken');

        if ($token) {
            Cache::forget('company_portal_session:'.$token);
        }

        return response()->json([
            'message' => __('api.messages.portal_logout_success'),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->attributes->get('companyPortalUser');

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => __('api.errors.current_password_invalid'),
                'errors' => [
                    'current_password' => [__('api.errors.current_password_invalid')],
                ],
            ], 422);
        }

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        return response()->json([
            'message' => __('api.messages.password_changed'),
        ]);
    }
}
