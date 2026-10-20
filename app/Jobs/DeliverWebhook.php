<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Support\WebhookTargetPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhook implements ShouldQueue
{
    private const MAX_DELIVERY_ATTEMPTS = 6;

    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // The outbox scheduler owns retries so a restarted queue job cannot reset
    // the delivery attempt count and retry forever.
    public int $tries = 1;

    public int $timeout = 20;

    public function __construct(public readonly int $deliveryId)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900, 3600];
    }

    public function handle(WebhookTargetPolicy $targetPolicy): void
    {
        $delivery = DB::transaction(function (): ?WebhookDelivery {
            $delivery = WebhookDelivery::query()
                ->with('endpoint')
                ->lockForUpdate()
                ->find($this->deliveryId);

            if (! $delivery || in_array($delivery->status, ['delivered', 'cancelled', 'failed'], true)) {
                return null;
            }

            $leaseExpired = $delivery->status === 'sending'
                && ($delivery->last_attempt_at === null || $delivery->last_attempt_at->lt(now()->subMinutes(5)));
            if ($delivery->status === 'sending' && ! $leaseExpired) {
                return null;
            }

            if ($delivery->status !== 'queued' && $delivery->next_attempt_at?->isFuture()) {
                return null;
            }

            if ((int) $delivery->attempt_count >= self::MAX_DELIVERY_ATTEMPTS) {
                $delivery->forceFill([
                    'status' => 'failed',
                    'success' => false,
                    'next_attempt_at' => null,
                    'error_message' => 'Webhook delivery reached the automatic retry limit.',
                ])->save();

                return null;
            }

            $delivery->forceFill([
                'status' => 'sending',
                'attempt_count' => ((int) $delivery->attempt_count) + 1,
                'last_attempt_at' => now(),
                'next_attempt_at' => now()->addMinutes(5),
                'error_message' => null,
            ])->save();

            return $delivery->fresh('endpoint');
        });

        if (! $delivery) {
            return;
        }

        $endpoint = $delivery->endpoint;

        if (! $endpoint || ! $endpoint->is_active) {
            $delivery->forceFill([
                'status' => 'cancelled',
                'success' => false,
                'next_attempt_at' => null,
            ])->save();
            return;
        }

        try {
            $targetPolicy->assertSafe($endpoint->target_url);
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => 'failed',
                'success' => false,
                'error_message' => 'Webhook destination rejected by the security policy.',
                'delivered_at' => null,
                'next_attempt_at' => null,
            ])->save();
            return;
        }

        $json = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $signature = hash_hmac('sha256', $json, $endpoint->secret);

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $delivery->event,
                    'X-Webhook-Delivery' => (string) $delivery->event_id,
                    'X-Webhook-Signature' => $signature,
                ])
                ->withBody($json, 'application/json')
                ->post($endpoint->target_url);

            if (! $response->successful()) {
                throw new \RuntimeException('Webhook target returned HTTP '.$response->status().'.');
            }

            $delivery->forceFill([
                'status' => 'delivered',
                'success' => true,
                'response_status' => $response->status(),
                'delivered_at' => now(),
                'next_attempt_at' => null,
                'error_message' => null,
                'response_body' => null,
            ])->save();

            $endpoint->forceFill(['last_used_at' => now()])->save();
        } catch (Throwable $exception) {
            $exhausted = (int) $delivery->attempt_count >= self::MAX_DELIVERY_ATTEMPTS;
            $delivery->forceFill([
                'status' => $exhausted ? 'failed' : 'retrying',
                'success' => false,
                'response_status' => isset($response) ? $response->status() : null,
                'next_attempt_at' => $exhausted
                    ? null
                    : now()->addSeconds($this->backoff()[min((int) $delivery->attempt_count - 1, count($this->backoff()) - 1)]),
                'error_message' => isset($response)
                    ? 'Webhook receiver returned HTTP '.$response->status().($exhausted ? '; automatic retry limit reached.' : '.')
                    : 'Webhook connection failed ('.get_class($exception).').',
                'response_body' => null,
            ])->save();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);
        if (! $delivery || in_array($delivery->status, ['delivered', 'cancelled', 'failed'], true)) {
            return;
        }

        $attempt = max(1, (int) $delivery->attempt_count);
        $exhausted = $attempt >= self::MAX_DELIVERY_ATTEMPTS;

        $delivery->forceFill([
            'status' => $exhausted ? 'failed' : 'retrying',
            'success' => false,
            'delivered_at' => null,
            'next_attempt_at' => $exhausted
                ? null
                : now()->addSeconds($this->backoff()[min($attempt - 1, count($this->backoff()) - 1)]),
            'error_message' => $exception
                ? 'Webhook delivery failed ('.get_class($exception).').'
                : 'Webhook delivery failed.',
        ])->save();
    }
}
