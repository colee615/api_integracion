<?php

namespace App\Services;

use App\Jobs\DeliverWebhook;
use App\Models\Package;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\ApiTokenContext;
use App\Support\PackageStatusCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WebhookNotifier
{
    public function dispatchForPackage(Package $package, string $status, array $context = []): void
    {
        $event = PackageStatusCatalog::eventForStatus($status);
        $company = $package->company;

        if (! $event || ! $company || ($context['is_public'] ?? true) === false) {
            return;
        }

        $eventId = (string) ($context['event_id'] ?? Str::uuid());
        $payload = [
            'id' => $eventId,
            'type' => $event,
            'event_code' => $event,
            'test' => ($context['mode'] ?? null) === 'manual_test',
            'occurred_at' => isset($context['occurred_at'])
                ? Carbon::parse($context['occurred_at'])->utc()->toIso8601String()
                : now()->utc()->toIso8601String(),
            'company' => [
                'id' => $company->id,
                'slug' => $company->slug,
                'environment' => $company->environment,
            ],
            'shipment' => [
                'tracking_code' => $package->tracking_code,
                'tracking_standard' => $package->tracking_standard,
                'status' => PackageStatusCatalog::normalize($status),
                'status_label' => PackageStatusCatalog::labelForStatus($status),
            ],
            'data' => array_filter([
                'location' => $context['location'] ?? null,
                'reason_code' => $context['reason_code'] ?? null,
                'description' => $context['description'] ?? null,
                'source_reference' => $context['source_reference'] ?? null,
            ], static fn (mixed $value): bool => $value !== null),
        ];

        $packageToken = $package->created_by_api_token_id
            ? $company->apiTokens()->find($package->created_by_api_token_id)
            : null;

        $endpoints = ApiTokenContext::scopeToToken(
            WebhookEndpoint::query()
                ->where('company_id', $company->id)
                ->where('is_active', true),
            $packageToken
        )->when(isset($context['endpoint_id']), fn ($query) => $query->whereKey($context['endpoint_id']))
            ->get();

        foreach ($endpoints as $endpoint) {
            $events = $endpoint->events ?? [];

            $isManualTest = ($context['mode'] ?? null) === 'manual_test';
            if (! $isManualTest && ! in_array('*', $events, true) && ! in_array($event, $events, true)) {
                continue;
            }

            $delivery = WebhookDelivery::query()->firstOrCreate(
                [
                    'webhook_endpoint_id' => $endpoint->id,
                    'event_id' => $eventId,
                ],
                [
                    'company_id' => $company->id,
                    'created_by_api_token_id' => $endpoint->created_by_api_token_id,
                    'event' => $event,
                    'tracking_code' => $package->tracking_code,
                    'payload' => $payload,
                    'status' => 'pending',
                ]
            );

            if ($delivery->wasRecentlyCreated) {
                DeliverWebhook::dispatch($delivery->id)->afterCommit();
            }
        }
    }
}
