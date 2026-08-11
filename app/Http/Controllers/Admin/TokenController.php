<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Company;
use App\Services\TrialTokenPurger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TokenController extends Controller
{
    public function index(): View
    {
        return view('admin.tokens.index', [
            'companies' => Company::query()->orderBy('name')->get(),
            'tokens' => ApiToken::query()
                ->with('company')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'token_type' => ['required', Rule::in(['production', 'trial'])],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $company = Company::query()->findOrFail($validated['company_id']);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            $validated['name'],
            now()->parse($validated['starts_at']),
            now()->parse($validated['expires_at']),
            ['packages:write', 'packages:read'],
            $validated['token_type']
        );

        return back()->with('new_token', [
            'company' => $company->name,
            'name' => $validated['name'],
            'token' => $plainTextToken,
            'token_type' => $validated['token_type'],
        ])->with('status', 'Token exclusivo generado correctamente.');
    }

    public function revoke(ApiToken $token, TrialTokenPurger $purger): RedirectResponse
    {
        $token->forceFill([
            'revoked_at' => now(),
        ])->save();

        $purger->purge($token);

        return back()->with('status', 'Token revocado correctamente.');
    }

    public function reactivate(ApiToken $token): RedirectResponse
    {
        $token->forceFill([
            'revoked_at' => null,
        ])->save();

        return back()->with('status', 'Token reactivado correctamente.');
    }

    public function extend(Request $request, ApiToken $token): RedirectResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $token->forceFill([
            'starts_at' => now()->parse($validated['starts_at']),
            'expires_at' => now()->parse($validated['expires_at']),
        ])->save();

        return back()->with('status', 'Vigencia del token actualizada correctamente.');
    }

    public function updateSettings(Request $request, ApiToken $token, TrialTokenPurger $purger): RedirectResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $token->forceFill([
            'starts_at' => now()->parse($validated['starts_at']),
            'expires_at' => now()->parse($validated['expires_at']),
            'revoked_at' => $validated['status'] === 'inactive' ? now() : null,
        ])->save();

        if ($validated['status'] === 'inactive') {
            $purger->purge($token);
        }

        return back()->with('status', 'Configuracion del token actualizada correctamente.');
    }

    public function destroy(ApiToken $token, TrialTokenPurger $purger): RedirectResponse
    {
        $purger->purge($token);
        $token->delete();

        return back()->with('status', 'Token eliminado correctamente.');
    }
}
