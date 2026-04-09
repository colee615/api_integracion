<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\WebhookNotifier;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageMovementController extends Controller
{
    public function index(Request $request, string $trackingCode): JsonResponse
    {
        $package = $this->resolvePackage($request, $trackingCode);

        return response()->json([
            'data' => $package->movements()->get()->map(fn ($movement) => [
                'id' => $movement->id,
                'status' => $movement->status,
                'status_label' => PackageStatusCatalog::labelForStatus($movement->status),
                'location' => $movement->location,
                'description' => $movement->description,
                'occurred_at' => $movement->occurred_at?->toIso8601String(),
                'meta' => $movement->meta ?? [],
            ]),
        ]);
    }

    public function store(Request $request, string $trackingCode, WebhookNotifier $notifier): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $package = $this->resolvePackage($request, $trackingCode);

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:100', Rule::in(PackageStatusCatalog::allowedStatuses())],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $normalizedStatus = PackageStatusCatalog::normalize($validated['status']);

        $movement = $package->movements()->create([
            ...$validated,
            'status' => $normalizedStatus,
            'company_id' => $company->id,
            'occurred_at' => $validated['occurred_at'] ?? now(),
        ]);

        $package->forceFill([
            'status' => $normalizedStatus,
            'last_movement_at' => $movement->occurred_at,
        ])->save();

        $notifier->dispatchForPackage($package->fresh('company'), $normalizedStatus, [
            'location' => $movement->location,
            'description' => $movement->description,
            'movement_id' => $movement->id,
        ]);

        return response()->json([
            'message' => __('api.messages.movement_registered'),
            'data' => [
                'id' => $movement->id,
                'status' => $movement->status,
                'status_label' => PackageStatusCatalog::labelForStatus($movement->status),
                'location' => $movement->location,
                'description' => $movement->description,
                'occurred_at' => $movement->occurred_at?->toIso8601String(),
                'meta' => $movement->meta ?? [],
            ],
        ], 201);
    }

    private function resolvePackage(Request $request, string $trackingCode): Package
    {
        $company = $request->attributes->get('currentCompany');

        return Package::query()
            ->where('company_id', $company->id)
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();
    }
}
