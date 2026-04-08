<?php

namespace Tests\Feature;

use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Models\Company;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalPackageDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal_api.token' => 'internal-test-token',
        ]);
    }

    public function test_internal_delivery_api_marks_package_bag_and_manifest_as_delivered_when_all_bag_packages_are_delivered(): void
    {
        $company = Company::create([
            'name' => 'Empresa Interna',
            'slug' => 'empresa-interna',
            'status' => 'active',
        ]);

        $manifest = Cn31Manifest::create([
            'company_id' => $company->id,
            'cn31_number' => 'CN31-DELIVERED-001',
            'origin_office' => 'COCHABAMBA',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => now()->subDay(),
            'total_bags' => 1,
            'total_packages' => 2,
            'total_weight_kg' => 0.500,
            'status' => 'conciliado',
        ]);

        $bag = Cn31Bag::create([
            'company_id' => $company->id,
            'cn31_manifest_id' => $manifest->id,
            'bag_number' => 'SACA-DELIVERED-001',
            'declared_package_count' => 2,
            'declared_weight_kg' => 0.500,
            'status' => 'conciliado',
            'meta' => [
                'loaded_package_count' => 2,
                'loaded_weight_kg' => 0.500,
            ],
        ]);

        $packageOne = Package::create([
            'company_id' => $company->id,
            'tracking_code' => 'EN000000901BO',
            'recipient_name' => 'Cliente Uno',
            'status' => 'en_ruta_entrega',
            'registered_at' => now()->subDay(),
            'last_movement_at' => now()->subHour(),
        ]);

        $packageTwo = Package::create([
            'company_id' => $company->id,
            'tracking_code' => 'EN000000902BO',
            'recipient_name' => 'Cliente Dos',
            'status' => 'entregado',
            'registered_at' => now()->subDay(),
            'last_movement_at' => now()->subHour(),
        ]);

        Cn33Package::create([
            'company_id' => $company->id,
            'cn31_bag_id' => $bag->id,
            'package_id' => $packageOne->id,
            'tracking_code' => $packageOne->tracking_code,
            'recipient_name' => 'Cliente Uno',
            'destination' => 'LA PAZ',
            'weight_kg' => 0.250,
            'status' => 'documentado_cn22',
        ]);

        Cn33Package::create([
            'company_id' => $company->id,
            'cn31_bag_id' => $bag->id,
            'package_id' => $packageTwo->id,
            'tracking_code' => $packageTwo->tracking_code,
            'recipient_name' => 'Cliente Dos',
            'destination' => 'LA PAZ',
            'weight_kg' => 0.250,
            'status' => 'entregado',
            'meta' => [
                'delivered_at' => now()->subHour()->toIso8601String(),
            ],
        ]);

        $deliveredAt = '2026-04-08T20:15:00-04:00';

        $this->postJson('/api/v1/internal/packages/deliver', [
            'tracking_code' => 'EN000000901BO',
            'occurred_at' => $deliveredAt,
            'location' => 'LA PAZ',
            'description' => 'Entrega final confirmada por operaciones.',
        ], [
            'X-Internal-Token' => 'internal-test-token',
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.tracking_code', 'EN000000901BO')
            ->assertJsonPath('data.status', 'entregado')
            ->assertJsonPath('data.bag.status', 'entregado')
            ->assertJsonPath('data.manifest.status', 'entregado');

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'tracking_code' => 'EN000000901BO',
            'status' => 'entregado',
        ]);

        $this->assertDatabaseHas('package_movements', [
            'company_id' => $company->id,
            'package_id' => $packageOne->id,
            'status' => 'entregado',
            'location' => 'LA PAZ',
        ]);

        $this->assertDatabaseHas('cn33_packages', [
            'company_id' => $company->id,
            'tracking_code' => 'EN000000901BO',
            'status' => 'entregado',
        ]);

        $this->assertSame(
            $deliveredAt,
            Cn31Bag::find($bag->id)?->meta['delivered_at'] ?? null
        );

        $this->assertSame(
            $deliveredAt,
            Cn31Manifest::find($manifest->id)?->meta['delivered_at'] ?? null
        );
    }

    public function test_internal_delivery_api_rejects_requests_without_internal_token(): void
    {
        $this->postJson('/api/v1/internal/packages/deliver', [], [
            'Accept' => 'application/json',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid internal API token.');
    }
}
