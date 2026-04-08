<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::with([
            'apiTokens' => fn ($query) => $query->latest(),
            'user',
        ])
            ->withCount(['packages', 'movements'])
            ->latest()
            ->get();

        $sessionCounts = DB::table('sessions')
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return view('admin.companies.index', [
            'companies' => $companies,
            'sessionCounts' => $sessionCounts,
        ]);
    }

    public function show(Company $company): View
    {
        $company->load([
            'apiTokens' => fn ($query) => $query->latest(),
            'user',
        ]);

        $summary = [
            'tokens' => $company->apiTokens()->count(),
            'active_tokens' => $company->apiTokens()->get()->filter(fn ($token) => $token->canUse())->count(),
            'manifests' => Cn31Manifest::where('company_id', $company->id)->count(),
            'bags' => Cn31Bag::where('company_id', $company->id)->count(),
            'cn33_packages' => Cn33Package::where('company_id', $company->id)->count(),
            'packages' => Package::where('company_id', $company->id)->count(),
            'movements' => PackageMovement::where('company_id', $company->id)->count(),
        ];

        $recentManifests = Cn31Manifest::query()
            ->with([
                'bags' => fn ($query) => $query->latest(),
                'bags.cn33Packages' => fn ($query) => $query->latest(),
                'bags.cn33Packages.package.movements' => fn ($query) => $query->latest('occurred_at'),
            ])
            ->where('company_id', $company->id)
            ->latest('dispatch_date')
            ->limit(10)
            ->get();

        $recentBags = Cn31Bag::query()
            ->with('manifest:id,cn31_number')
            ->where('company_id', $company->id)
            ->latest()
            ->limit(10)
            ->get();

        $recentCn33Packages = Cn33Package::query()
            ->with('bag:id,bag_number')
            ->where('company_id', $company->id)
            ->latest()
            ->limit(10)
            ->get();

        $recentPackages = Package::query()
            ->where('company_id', $company->id)
            ->latest('registered_at')
            ->limit(10)
            ->get();

        $recentMovements = PackageMovement::query()
            ->with('package:id,tracking_code')
            ->where('company_id', $company->id)
            ->latest('occurred_at')
            ->limit(12)
            ->get();

        $sessionCount = $company->user
            ? (int) DB::table('sessions')->where('user_id', $company->user->id)->count()
            : 0;

        return view('admin.companies.show', [
            'company' => $company,
            'summary' => $summary,
            'sessionCount' => $sessionCount,
            'recentManifests' => $recentManifests,
            'recentBags' => $recentBags,
            'recentCn33Packages' => $recentCn33Packages,
            'recentPackages' => $recentPackages,
            'recentMovements' => $recentMovements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('companies', 'slug')],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'locale' => ['required', Rule::in(['es', 'en'])],
            'login_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'login_password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        DB::transaction(function () use ($validated): void {
            $company = Company::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'] ?: Str::slug($validated['name']),
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'status' => $validated['status'],
                'locale' => $validated['locale'],
            ]);

            User::create([
                'name' => $validated['name'],
                'email' => $validated['login_email'],
                'password' => $validated['login_password'],
                'role' => 'company',
                'company_id' => $company->id,
            ]);
        });

        return back()->with('status', 'Empresa creada correctamente.');
    }

    public function updateStatus(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $company->forceFill([
            'status' => $validated['status'],
        ])->save();

        if ($company->user) {
            $company->user->forceFill([
                'status' => $validated['status'],
            ])->save();
        }

        return back()->with('status', 'Estado de empresa actualizado.');
    }

    public function updateLocale(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['es', 'en'])],
        ]);

        $company->forceFill([
            'locale' => $validated['locale'],
        ])->save();

        return back()->with('status', 'Idioma de empresa actualizado.');
    }

    public function revokeSessions(Company $company): RedirectResponse
    {
        if (! $company->user) {
            return back()->with('status', 'La empresa no tiene usuario asociado.');
        }

        DB::table('sessions')
            ->where('user_id', $company->user->id)
            ->delete();

        return back()->with('status', 'Sesiones de empresa cerradas correctamente.');
    }
}
