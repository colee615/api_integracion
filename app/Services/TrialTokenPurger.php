<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\Cn31Manifest;
use App\Models\Package;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\DB;

class TrialTokenPurger
{
    public function purge(ApiToken $token): void
    {
        if (! $token->isTrial()) {
            return;
        }

        DB::transaction(function () use ($token): void {
            WebhookDelivery::query()
                ->where('created_by_api_token_id', $token->id)
                ->delete();

            WebhookEndpoint::query()
                ->where('created_by_api_token_id', $token->id)
                ->delete();

            Cn31Manifest::query()
                ->where('created_by_api_token_id', $token->id)
                ->delete();

            Package::query()
                ->where('created_by_api_token_id', $token->id)
                ->delete();
        });
    }
}
