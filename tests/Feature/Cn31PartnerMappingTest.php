<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Cn31Bag;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Cn31PartnerMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_cn31_identifiers_link_the_bag_to_cn33_without_a_bag_dispatch_field(): void
    {
        $company = Company::create([
            'name' => '360 Lions Mapping Test',
            'slug' => '360-lions-mapping-test',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            '360 Lions Mapping Token',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $this->assertFalse(Schema::hasColumn('cn31_bags', 'dispatch_number_bag'));

        $this->postJson('/api/v1/cn31/manifests', [
            'cn31_number' => 'CWPUNAPELIMAAUX60063',
            'dispatch_number_manifest' => '0063',
            'origin_office' => 'CWPUNA',
            'destination_office' => 'PELIMA',
            'dispatch_date' => '2026-10-20 00:00:00',
            'bags' => [
                [
                    'bag_number' => 'PEEX260602250148',
                    'package_count' => 1,
                    'total_weight_kg' => 0.250,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.cn31_number', 'CWPUNAPELIMAAUX60063')
            ->assertJsonPath('data.dispatch_number_manifest', '0063')
            ->assertJsonPath('data.bags.0.bag_number', 'PEEX260602250148');

        $bag = Cn31Bag::query()->where('bag_number', 'PEEX260602250148')->firstOrFail();
        $this->assertSame('CWPUNAPELIMAAUX60063', $bag->manifest->cn31_number);

        $this->postJson('/api/v1/cn33/bags/PEEX260602250148/packages', [
            'packages' => [
                [
                    'tracking_code' => 'EN000000630BO',
                    'origin' => 'SHANGHAI',
                    'destination' => 'LA PAZ',
                    'weight_kg' => 0.250,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.bag_number', 'PEEX260602250148')
            ->assertJsonPath('data.manifest_number', 'CWPUNAPELIMAAUX60063')
            ->assertJsonPath('data.loaded_package_count', 1);
    }
}
