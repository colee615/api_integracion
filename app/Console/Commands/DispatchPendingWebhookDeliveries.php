<?php

namespace App\Console\Commands;

use App\Jobs\DeliverWebhook;
use App\Models\WebhookDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchPendingWebhookDeliveries extends Command
{
    protected $signature = 'webhooks:dispatch-pending {--limit=200}';

    protected $description = 'Dispatch pending and overdue webhook deliveries.';

    public function handle(): int
    {
        $now = now();
        $staleBefore = $now->copy()->subMinutes(5);
        $limit = max(1, min((int) $this->option('limit'), 1000));

        $deliveryIds = WebhookDelivery::query()
            ->where(function ($query) use ($now, $staleBefore): void {
                $query->where(function ($due) use ($now): void {
                    $due->whereIn('status', ['pending', 'retrying'])
                        ->where(fn ($time) => $time->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now));
                })->orWhere(function ($queued) use ($now): void {
                    $queued->where('status', 'queued')
                        ->where(fn ($time) => $time->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now));
                })->orWhere(function ($sending) use ($staleBefore): void {
                    $sending->where('status', 'sending')->where(function ($lease) use ($staleBefore): void {
                        $lease->whereNull('last_attempt_at')->orWhere('last_attempt_at', '<=', $staleBefore);
                    });
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $dispatched = 0;
        foreach ($deliveryIds as $deliveryId) {
            $claimed = DB::transaction(function () use ($deliveryId, $now, $staleBefore): bool {
                $delivery = WebhookDelivery::query()->lockForUpdate()->find($deliveryId);
                if (! $delivery) {
                    return false;
                }

                $due = in_array($delivery->status, ['pending', 'retrying', 'queued'], true)
                    && ($delivery->next_attempt_at === null || $delivery->next_attempt_at->lte($now));
                $staleSending = $delivery->status === 'sending'
                    && ($delivery->last_attempt_at === null || $delivery->last_attempt_at->lte($staleBefore));

                if (! $due && ! $staleSending) {
                    return false;
                }

                $delivery->forceFill([
                    'status' => 'queued',
                    'next_attempt_at' => now()->addMinutes(5),
                ])->save();

                return true;
            });

            if ($claimed) {
                DeliverWebhook::dispatch((int) $deliveryId);
                $dispatched++;
            }
        }

        $this->info("Dispatched {$dispatched} webhook delivery job(s).");

        return self::SUCCESS;
    }
}
