<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageMovement;
use App\Support\PackageStatusCatalog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $company = auth()->user()->company()->with('apiTokens')->firstOrFail();
        $packagesCount = Package::where('company_id', $company->id)->count();
        $movementsCount = PackageMovement::where('company_id', $company->id)->count();
        $packagesWithDeliveryAttempts = Package::where('company_id', $company->id)
            ->where('delivery_attempts', '>', 0)
            ->count();
        $totalDeliveryAttempts = (int) Package::where('company_id', $company->id)
            ->sum('delivery_attempts');
        $recentPackages = Package::query()
            ->where('company_id', $company->id)
            ->latest('last_movement_at')
            ->limit(10)
            ->get();

        return view('company.dashboard', [
            'company' => $company,
            'packagesCount' => $packagesCount,
            'movementsCount' => $movementsCount,
            'packagesWithDeliveryAttempts' => $packagesWithDeliveryAttempts,
            'totalDeliveryAttempts' => $totalDeliveryAttempts,
            'recentPackages' => $recentPackages,
            'recentMovements' => PackageMovement::with('package')
                ->where('company_id', $company->id)
                ->latest('occurred_at')
                ->limit(10)
                ->get(),
            'statusLabel' => fn (string $status): string => PackageStatusCatalog::labelForStatus($status),
        ]);
    }
}
