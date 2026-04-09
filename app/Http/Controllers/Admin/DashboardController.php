<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cn31Manifest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse((string) $request->string('date'))->startOfDay()
            : Carbon::today();
        $selectedYearStart = $selectedDate->copy()->startOfYear();
        $selectedYearEnd = $selectedDate->copy()->endOfYear();

        $selectedManifests = Cn31Manifest::query()
            ->with([
                'company',
                'bags' => fn ($query) => $query
                    ->with([
                        'cn33Packages' => fn ($cn33Query) => $cn33Query
                            ->with([
                                'package' => fn ($packageQuery) => $packageQuery->with('movements'),
                            ]),
                    ])
                    ->latest(),
            ])
            ->whereDate('dispatch_date', $selectedDate)
            ->latest('dispatch_date')
            ->limit(15)
            ->get();

        $availableDateSet = collect()
            ->merge(
                Cn31Manifest::query()
                    ->whereBetween('dispatch_date', [$selectedYearStart, $selectedYearEnd])
                    ->get(['dispatch_date'])
                    ->flatMap(fn ($manifest) => [
                        optional($manifest->dispatch_date)?->toDateString(),
                    ])
            )
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.dashboard', [
            'selectedDate' => $selectedDate,
            'availableDates' => $availableDateSet,
            'manifestsTodayCount' => $selectedManifests->count(),
            'bagsTodayCount' => (int) $selectedManifests->sum('total_bags'),
            'companiesSendingTodayCount' => $selectedManifests
                ->pluck('company_id')
                ->filter()
                ->unique()
                ->count(),
            'packagesCount' => (int) $selectedManifests->sum('total_packages'),
            'receivedTodayCount' => (int) $selectedManifests->sum('total_packages'),
            'todayManifests' => $selectedManifests,
        ]);
    }
}
