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
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::with([
            'apiTokens' => fn ($query) => $query->latest(),
            'user',
        ])
            ->withCount(['packages', 'movements', 'apiTokens', 'cn31Manifests', 'cn31Bags', 'cn33Packages'])
            ->latest()
            ->get();

        $webSessionCounts = DB::table(config('session.table', 'sessions'))
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $portalSessionCounts = collect(
            $this->getCompanyPortalSessionCounts(
                $companies->pluck('user.id')->filter()->all()
            )
        );

        $sessionCounts = $webSessionCounts->map(
            fn ($total) => (int) $total
        );

        foreach ($portalSessionCounts as $userId => $total) {
            $sessionCounts[$userId] = (int) ($sessionCounts[$userId] ?? 0) + (int) $total;
        }

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
            'delivered_packages' => Package::where('company_id', $company->id)->where('status', 'entregado')->count(),
            'packages_with_delivery_attempts' => Package::where('company_id', $company->id)->where('delivery_attempts', '>', 0)->count(),
            'total_delivery_attempts' => (int) Package::where('company_id', $company->id)->sum('delivery_attempts'),
            'delivered_bags' => Cn31Bag::where('company_id', $company->id)->where('status', 'entregado')->count(),
            'delivered_manifests' => Cn31Manifest::where('company_id', $company->id)->where('status', 'entregado')->count(),
        ];

        $summary['pending_delivery_packages'] = max($summary['packages'] - $summary['delivered_packages'], 0);
        $summary['delivery_progress_pct'] = $summary['packages'] > 0
            ? round(($summary['delivered_packages'] / $summary['packages']) * 100, 1)
            : 0.0;

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
            ->with([
                'manifest:id,cn31_number',
                'cn33Packages:id,cn31_bag_id,status',
            ])
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

        $sessionCount = 0;

        if ($company->user) {
            $sessionCount += (int) DB::table(config('session.table', 'sessions'))
                ->where('user_id', $company->user->id)
                ->count();
            $sessionCount += $this->getCompanyPortalSessionCountForUser($company->user->id);
        }

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

    public function updateSettings(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['es', 'en'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $company->forceFill([
            'locale' => $validated['locale'],
            'status' => $validated['status'],
        ])->save();

        if ($company->user) {
            $company->user->forceFill([
                'status' => $validated['status'],
            ])->save();
        }

        return back()->with('status', 'Configuracion de empresa actualizada.');
    }

    public function revokeSessions(Company $company): RedirectResponse
    {
        if (! $company->user) {
            return back()->with('status', 'La empresa no tiene usuario asociado.');
        }

        $webSessionsRevoked = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $company->user->id)
            ->delete();

        $portalSessionsRevoked = $this->revokeCompanyPortalSessions($company->user->id);
        $revokedTotal = $webSessionsRevoked + $portalSessionsRevoked;

        return back()->with(
            'status',
            $revokedTotal > 0
                ? 'Sesiones de empresa cerradas correctamente.'
                : 'No habia sesiones activas para cerrar.'
        );
    }

    public function destroy(Company $company): RedirectResponse
    {
        $operationalSummary = [
            'packages' => Package::where('company_id', $company->id)->count(),
            'movements' => PackageMovement::where('company_id', $company->id)->count(),
            'cn33_packages' => Cn33Package::where('company_id', $company->id)->count(),
            'bags' => Cn31Bag::where('company_id', $company->id)->count(),
            'manifests' => Cn31Manifest::where('company_id', $company->id)->count(),
        ];

        if (array_sum($operationalSummary) > 0) {
            return back()->with('company_delete_error', [
                'company' => $company->name,
                'message' => 'No se puede eliminar esta empresa porque ya tiene carga operativa registrada.',
                'summary' => $operationalSummary,
            ]);
        }

        DB::transaction(function () use ($company): void {
            if ($company->user) {
                $this->revokeCompanyPortalSessions($company->user->id);

                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $company->user->id)
                    ->delete();

                $company->user->delete();
            }

            $company->apiTokens()->delete();
            $company->delete();
        });

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Empresa eliminada correctamente.');
    }

    /**
     * @param  array<int>  $userIds
     * @return array<int, int>
     */
    private function getCompanyPortalSessionCounts(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return $this->getCompanyPortalSessionEntries()
            ->reduce(function (array $counts, object $entry) use ($userIds): array {
                $payload = @unserialize($entry->value, ['allowed_classes' => false]);
                $userId = is_array($payload) ? ($payload['user_id'] ?? null) : null;

                if (! is_int($userId) || ! in_array($userId, $userIds, true)) {
                    return $counts;
                }

                $counts[$userId] = ($counts[$userId] ?? 0) + 1;

                return $counts;
            }, []);
    }

    private function getCompanyPortalSessionCountForUser(int $userId): int
    {
        return $this->getCompanyPortalSessionCounts([$userId])[$userId] ?? 0;
    }

    private function revokeCompanyPortalSessions(int $userId): int
    {
        $entries = $this->getCompanyPortalSessionEntries()
            ->filter(function (object $entry) use ($userId): bool {
                $payload = @unserialize($entry->value, ['allowed_classes' => false]);

                return is_array($payload) && ($payload['user_id'] ?? null) === $userId;
            });

        if ($entries->isEmpty()) {
            return 0;
        }

        return DB::table($this->getCacheTableName())
            ->whereIn('key', $entries->pluck('key')->all())
            ->delete();
    }

    /**
     * @return Collection<int, object>
     */
    private function getCompanyPortalSessionEntries(): Collection
    {
        $cacheStore = $this->getDatabaseCacheStoreName();

        if ($cacheStore === null) {
            return collect();
        }

        return DB::table($this->getCacheTableName())
            ->select(['key', 'value', 'expiration'])
            ->where('key', 'like', $this->getCompanyPortalSessionCachePrefix().'%')
            ->where('expiration', '>', now()->timestamp)
            ->get();
    }

    private function getCacheTableName(): string
    {
        $cacheStore = $this->getDatabaseCacheStoreName();

        if ($cacheStore === null) {
            return 'cache';
        }

        return (string) config("cache.stores.{$cacheStore}.table", 'cache');
    }

    private function getCompanyPortalSessionCachePrefix(): string
    {
        return (string) config('cache.prefix', 'laravel-cache-').'company_portal_session:';
    }

    private function getDatabaseCacheStoreName(): ?string
    {
        $defaultStore = config('cache.default');

        if (config("cache.stores.{$defaultStore}.driver") === 'database') {
            return $defaultStore;
        }

        foreach ((array) config('cache.stores', []) as $storeName => $storeConfig) {
            if (($storeConfig['driver'] ?? null) === 'database') {
                return (string) $storeName;
            }
        }

        return null;
    }
}
