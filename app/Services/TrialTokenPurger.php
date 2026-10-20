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
            $manifestIds = Cn31Manifest::query()
                ->where('created_by_api_token_id', $token->id)
                ->select('id');

            $bagIds = DB::table('cn31_bags')
                ->where(function ($query) use ($token, $manifestIds): void {
                    $query->where('created_by_api_token_id', $token->id)
                        ->orWhereIn('cn31_manifest_id', $manifestIds);
                })
                ->select('id');

            $packageIds = Package::query()
                ->where('created_by_api_token_id', $token->id)
                ->select('id');

            WebhookDelivery::query()
                ->where(function ($query) use ($token): void {
                    $query->where('created_by_api_token_id', $token->id)
                        ->orWhereIn('webhook_endpoint_id', WebhookEndpoint::query()
                            ->where('created_by_api_token_id', $token->id)
                            ->select('id'));
                })
                ->delete();

            WebhookEndpoint::query()
                ->where('created_by_api_token_id', $token->id)
                ->delete();

            // Event tables are append-only in normal operation and do not cascade
            // from their parent records, so clear this token's sandbox history first.
            DB::table('package_movements')
                ->where(function ($query) use ($token, $packageIds): void {
                    $query->where('created_by_api_token_id', $token->id)
                        ->orWhereIn('package_id', $packageIds);
                })
                ->delete();

            DB::table('cn31_bag_movements')
                ->where(function ($query) use ($token, $bagIds): void {
                    $query->where('created_by_api_token_id', $token->id)
                        ->orWhereIn('cn31_bag_id', $bagIds);
                })
                ->delete();

            DB::table('cn33_packages')
                ->where(function ($query) use ($token, $bagIds): void {
                    $query->where('created_by_api_token_id', $token->id)
                        ->orWhereIn('cn31_bag_id', $bagIds);
                })
                ->delete();

            DB::table('cn31_bags')
                ->whereIn('id', $bagIds)
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
