<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->where('role', 'admin')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        User::create([
            ...$validated,
            'role' => 'admin',
        ]);

        return back()->with('status', 'Usuario interno creado correctamente.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($request->user()->is($user) && $validated['status'] === 'inactive') {
            return back()->withErrors([
                'status' => 'No puedes desactivar tu propio usuario.',
            ]);
        }

        $user->forceFill([
            'status' => $validated['status'],
        ])->save();

        return back()->with('status', 'Estado de usuario actualizado.');
    }
}
