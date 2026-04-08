<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ApiToken;
use App\Models\Package;
use App\Models\PackageMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'usersCount' => User::where('role', 'admin')->count(),
            'companiesCount' => Company::count(),
            'tokensCount' => ApiToken::count(),
            'packagesCount' => Package::count(),
            'movementsCount' => PackageMovement::count(),
            'activeSessionsCount' => DB::table('sessions')->whereNotNull('user_id')->count(),
            'companies' => Company::withCount(['packages', 'movements', 'apiTokens'])
                ->latest()
                ->get(),
            'recentMovements' => PackageMovement::query()
                ->with(['company', 'package'])
                ->latest('occurred_at')
                ->limit(15)
                ->get(),
        ]);
    }
}
