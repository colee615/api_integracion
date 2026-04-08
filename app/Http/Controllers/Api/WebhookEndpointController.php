<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Models\Package;
use App\Services\WebhookNotifier;
use App\Support\PackageStatusCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookEndpointController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $endpoints = WebhookEndpoint::query()
            ->with(['deliveries' => fn ($query) => $query->latest()->limit(5)])
            ->where('company_id', $company->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $endpoints->map(fn (WebhookEndpoint $endpoint) => [
                'id' => $endpoint->id,
                'name' => $endpoint->name,
                'target_url' => $endpoint->target_url,
                'events' => $endpoint->events,
                'is_active' => $endpoint->is_active,
                'secret_masked' => substr($endpoint->secret, 0, 4).'********'.substr($endpoint->secret, -4),
                'last_used_at' => $endpoint->last_used_at?->toIso8601String(),
                'recent_deliveries' => $endpoint->deliveries->map(fn ($delivery) => [
                    'id' => $delivery->id,
                    'event' => $delivery->event,
                    'tracking_code' => $delivery->tracking_code,
                    'response_status' => $delivery->response_status,
                    'success' => $delivery->success,
                    'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'target_url' => ['required', 'url', 'max:255'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in([...PackageStatusCatalog::webhookEvents(), '*'])],
            'is_active' => ['nullable', 'boolean'],
            'secret' => ['nullable', 'string', 'min:16', 'max:120'],
        ]);

        $endpoint = WebhookEndpoint::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'target_url' => $validated['target_url'],
            'events' => $validated['events'],
            'is_active' => $validated['is_active'] ?? true,
            'secret' => $validated['secret'] ?? Str::random(40),
        ]);

        return response()->json([
            'message' => __('api.messages.webhook_registered'),
            'data' => [
                'id' => $endpoint->id,
                'name' => $endpoint->name,
                'target_url' => $endpoint->target_url,
                'events' => $endpoint->events,
                'is_active' => $endpoint->is_active,
                'secret' => $endpoint->secret,
            ],
        ], 201);
    }

    public function test(Request $request, WebhookEndpoint $webhookEndpoint, WebhookNotifier $notifier): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');

        abort_unless($webhookEndpoint->company_id === $company->id, 404);

        $validator = Validator::make($request->all(), [
            'tracking_code' => ['required', 'string'],
            'status' => ['required', Rule::in(PackageStatusCatalog::allowedStatuses())],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('api.errors.invalid_webhook_test_data'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $package = Package::query()
            ->where('company_id', $company->id)
            ->where('tracking_code', $request->string('tracking_code'))
            ->firstOrFail();

        $notifier->dispatchForPackage($package, (string) $request->input('status'), [
            'mode' => 'manual_test',
            'endpoint_id' => $webhookEndpoint->id,
        ]);

        return response()->json([
            'message' => __('api.messages.webhook_test_executed'),
        ]);
    }
}
