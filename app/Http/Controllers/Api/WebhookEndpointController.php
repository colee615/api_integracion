<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverWebhook;
use App\Models\Package;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookNotifier;
use App\Support\ApiTokenContext;
use App\Support\PackageStatusCatalog;
use App\Support\WebhookTargetPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class WebhookEndpointController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $endpoints = ApiTokenContext::scopeToToken(
            WebhookEndpoint::query()
                ->with(['deliveries' => fn ($query) => $query->latest()->limit(5)])
                ->where('company_id', $company->id),
            $apiToken
        )
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
                    'event_id' => $delivery->event_id,
                    'tracking_code' => $delivery->tracking_code,
                    'response_status' => $delivery->response_status,
                    'status' => $delivery->status,
                    'attempt_count' => (int) $delivery->attempt_count,
                    'last_attempt_at' => $delivery->last_attempt_at?->toIso8601String(),
                    'success' => $delivery->success,
                    'error_message' => $delivery->error_message,
                    'delivered_at' => $delivery->success ? $delivery->delivered_at?->toIso8601String() : null,
                ])->values(),
            ])->values(),
        ]);
    }

    public function deliveries(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->assertEndpointOwnership($request, $webhookEndpoint);
        $deliveries = $webhookEndpoint->deliveries()->latest()->paginate(50);

        return response()->json([
            'data' => $deliveries->getCollection()->map(fn (WebhookDelivery $delivery) => [
                'id' => $delivery->id,
                'event_id' => $delivery->event_id,
                'event' => $delivery->event,
                'tracking_code' => $delivery->tracking_code,
                'status' => $delivery->status,
                'attempt_count' => (int) $delivery->attempt_count,
                'response_status' => $delivery->response_status,
                'last_attempt_at' => $delivery->last_attempt_at?->toIso8601String(),
                'next_attempt_at' => $delivery->next_attempt_at?->toIso8601String(),
                'delivered_at' => $delivery->success ? $delivery->delivered_at?->toIso8601String() : null,
                'error_message' => $delivery->error_message,
            ])->values(),
            'meta' => [
                'current_page' => $deliveries->currentPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
                'last_page' => $deliveries->lastPage(),
            ],
        ]);
    }

    public function retry(Request $request, WebhookEndpoint $webhookEndpoint, WebhookDelivery $webhookDelivery): JsonResponse
    {
        $this->assertEndpointOwnership($request, $webhookEndpoint);
        abort_unless($webhookDelivery->webhook_endpoint_id === $webhookEndpoint->id, 404);
        abort_unless($webhookEndpoint->is_active, 404);

        if (! in_array($webhookDelivery->status, ['failed', 'retrying'], true)) {
            return response()->json([
                'code' => 'WEBHOOK_DELIVERY_NOT_RETRYABLE',
                'message' => __('api.errors.webhook_not_retryable'),
            ], 409);
        }

        $webhookDelivery->forceFill([
            'status' => 'pending',
            'success' => false,
            'attempt_count' => 0,
            'response_status' => null,
            'delivered_at' => null,
            'next_attempt_at' => null,
            'error_message' => null,
        ])->save();

        DeliverWebhook::dispatch($webhookDelivery->id)->afterCommit();

        return response()->json([
            'message' => __('api.messages.webhook_retry_queued'),
            'data' => ['id' => $webhookDelivery->id, 'status' => 'pending'],
        ], 202);
    }

    public function destroy(Request $request, WebhookEndpoint $webhookEndpoint): JsonResponse
    {
        $this->assertEndpointOwnership($request, $webhookEndpoint);
        $webhookEndpoint->forceFill(['is_active' => false])->save();

        return response()->json(['message' => __('api.messages.webhook_disabled')]);
    }

    public function store(Request $request, WebhookTargetPolicy $targetPolicy): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'target_url' => ['required', 'url', 'max:255'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in([...PackageStatusCatalog::webhookEvents(), '*'])],
            'is_active' => ['nullable', 'boolean'],
            'secret' => ['nullable', 'string', 'min:16', 'max:120'],
        ]);

        try {
            $targetPolicy->assertSafe($validated['target_url']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['target_url' => [$exception->getMessage()]]);
        }

        $endpoint = WebhookEndpoint::create([
            'company_id' => $company->id,
            ...ApiTokenContext::ownershipAttributes($apiToken),
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

    public function update(Request $request, WebhookEndpoint $webhookEndpoint, WebhookTargetPolicy $targetPolicy): JsonResponse
    {
        $this->assertEndpointOwnership($request, $webhookEndpoint);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'target_url' => ['sometimes', 'required', 'url', 'max:255'],
            'events' => ['sometimes', 'required', 'array', 'min:1'],
            'events.*' => ['required', 'string', Rule::in([...PackageStatusCatalog::webhookEvents(), '*'])],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'secret' => ['sometimes', 'required', 'string', 'min:16', 'max:120'],
        ]);

        if ($validated === []) {
            throw ValidationException::withMessages(['webhook' => ['Provide at least one field to update.']]);
        }

        if (isset($validated['target_url'])) {
            try {
                $targetPolicy->assertSafe($validated['target_url']);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['target_url' => [$exception->getMessage()]]);
            }
        }

        $rotatedSecret = $validated['secret'] ?? null;
        $webhookEndpoint->fill($validated)->save();

        return response()->json([
            'message' => __('api.messages.webhook_updated'),
            'data' => [
                'id' => $webhookEndpoint->id,
                'name' => $webhookEndpoint->name,
                'target_url' => $webhookEndpoint->target_url,
                'events' => $webhookEndpoint->events,
                'is_active' => $webhookEndpoint->is_active,
                'secret' => $rotatedSecret,
            ],
        ]);
    }

    public function test(Request $request, WebhookEndpoint $webhookEndpoint, WebhookNotifier $notifier): JsonResponse
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        $this->assertEndpointOwnership($request, $webhookEndpoint);
        abort_unless($webhookEndpoint->is_active, 404);

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

        $package = ApiTokenContext::scopeToToken(
            Package::query()->where('company_id', $company->id),
            $apiToken
        )
            ->where('tracking_code', $request->string('tracking_code'))
            ->firstOrFail();

        $notifier->dispatchForPackage($package, (string) $request->input('status'), [
            'mode' => 'manual_test',
            'endpoint_id' => $webhookEndpoint->id,
        ]);

        return response()->json([
            'message' => __('api.messages.webhook_test_queued'),
            'status' => 'pending',
        ], 202);
    }

    private function assertEndpointOwnership(Request $request, WebhookEndpoint $endpoint): void
    {
        $company = $request->attributes->get('currentCompany');
        $apiToken = ApiTokenContext::currentToken($request);

        abort_unless($endpoint->company_id === $company->id, 404);
        abort_unless($endpoint->created_by_api_token_id === ($apiToken?->isTrial() ? $apiToken->id : null), 404);
    }
}
