<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Las credenciales no son validas.',
                ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user?->isActive()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Tu usuario interno se encuentra inactivo.',
            ]);
        }

        $user?->forceFill([
            'last_login_at' => now(),
        ])->save();

        if (! $user?->isAdmin()) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Este acceso web es solo para administracion interna.',
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
