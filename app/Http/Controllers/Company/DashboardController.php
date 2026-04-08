<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageMovement;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $company = auth()->user()->company()->with('apiTokens')->firstOrFail();

        return view('company.dashboard', [
            'company' => $company,
            'packagesCount' => Package::where('company_id', $company->id)->count(),
            'movementsCount' => PackageMovement::where('company_id', $company->id)->count(),
            'recentMovements' => PackageMovement::with('package')
                ->where('company_id', $company->id)
                ->latest('occurred_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
