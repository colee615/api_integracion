<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TokenController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Company\DashboardController as CompanyDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('company.dashboard');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');

        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
        Route::patch('/companies/{company}/settings', [CompanyController::class, 'updateSettings'])->name('companies.settings');
        Route::patch('/companies/{company}/status', [CompanyController::class, 'updateStatus'])->name('companies.status');
        Route::patch('/companies/{company}/locale', [CompanyController::class, 'updateLocale'])->name('companies.locale');
        Route::post('/companies/{company}/sessions/revoke', [CompanyController::class, 'revokeSessions'])->name('companies.sessions.revoke');

        Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
        Route::post('/tokens', [TokenController::class, 'store'])->name('tokens.store');
        Route::patch('/tokens/{token}/settings', [TokenController::class, 'updateSettings'])->name('tokens.settings');
        Route::patch('/tokens/{token}/revoke', [TokenController::class, 'revoke'])->name('tokens.revoke');
        Route::patch('/tokens/{token}/reactivate', [TokenController::class, 'reactivate'])->name('tokens.reactivate');
        Route::patch('/tokens/{token}/extend', [TokenController::class, 'extend'])->name('tokens.extend');
        Route::delete('/tokens/{token}', [TokenController::class, 'destroy'])->name('tokens.destroy');
    });
});

Route::middleware(['auth', 'company'])->prefix('empresa')->name('company.')->group(function (): void {
    Route::get('/', CompanyDashboardController::class)->name('dashboard');
});
