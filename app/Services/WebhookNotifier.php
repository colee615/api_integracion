<?php

namespace App\Services;

use App\Models\Package;
use App\Models\WebhookEndpoint;
use App\Support\PackageStatusCatalog;
use Illuminate\Support\Facades\Http;
use Throwable;

class WebhookNotifier
{
    public function dispatchForPackage(Package $package, string $status, array $context = []): void
    {
        $event = PackageStatusCatalog::eventForStatus($status);

        if (! $event) {
            return;
        }

        $company = $package->company;

        if (! $company) {
            return;
        }

        $payload = [
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'environment' => $company->environment,
            ],
            'shipment' => [
                'package_id' => $package->id,
                'tracking_code' => $package->tracking_code,
                'tracking_standard' => $package->tracking_standard,
                'status' => PackageStatusCatalog::normalize($status),
                'status_label' => PackageStatusCatalog::labelForStatus($status),
                'recipient_name' => $package->recipient_name,
                'recipient_document' => $package->recipient_document,
                'recipient_city' => $package->recipient_city,
                'recipient_department' => $package->recipient_department,
                'destination' => $package->destination,
                'last_movement_at' => $package->last_movement_at?->toIso8601String(),
            ],
            'context' => $context,
        ];

        $endpoints = WebhookEndpoint::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->get()
            ->filter(function (WebhookEndpoint $endpoint) use ($event): bool {
                $events = $endpoint->events ?? [];

                return in_array('*', $events, true) || in_array($event, $events, true);
            });

        foreach ($endpoints as $endpoint) {
            $delivery = $endpoint->deliveries()->create([
                'company_id' => $company->id,
                'event' => $event,
                'tracking_code' => $package->tracking_code,
                'payload' => $payload,
            ]);

            try {
                $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
                $signature = hash_hmac('sha256', $json, $endpoint->secret);

                $response = Http::timeout(10)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Webhook-Event' => $event,
                        'X-Webhook-Signature' => $signature,
                    ])
                    ->post($endpoint->target_url, $payload);

                $delivery->forceFill([
                    'response_status' => $response->status(),
                    'success' => $response->successful(),
                    'delivered_at' => now(),
                    'response_body' => $response->body(),
                ])->save();

                $endpoint->forceFill([
                    'last_used_at' => now(),
                ])->save();
            } catch (Throwable $exception) {
                $delivery->forceFill([
                    'response_status' => 0,
                    'success' => false,
                    'delivered_at' => now(),
                    'response_body' => $exception->getMessage(),
                ])->save();
            }
        }
    }
}
