<?php

namespace Tests\Feature;

use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Models\ApiToken;
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

        config(['services.internal_api.token' => 'test-internal-api-token']);
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
            'delivery_status' => 'completado',
            'meta' => [
                'delivered_at' => now()->subHour()->toIso8601String(),
            ],
        ]);

        [, $companyReadToken] = ApiToken::issue(
            $company,
            'Consulta documental',
            now()->subMinute(),
            now()->addDays(10),
            ['documents:read']
        );

        $deliveredAt = now()->subMinutes(30)->toIso8601String();

        $this->postJson('/api/v1/internal/packages/deliver', [
            'tracking_code' => 'EN000000901BO',
            'occurred_at' => $deliveredAt,
            'location' => 'LA PAZ',
            'description' => 'Entrega final confirmada por operaciones.',
        ], [
            ...$this->internalHeaders(),
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.tracking_code', 'EN000000901BO')
            ->assertJsonPath('data.status', 'entregado')
            ->assertJsonPath('data.bag.status', 'conciliado')
            ->assertJsonPath('data.bag.delivery_status', 'completado')
            ->assertJsonPath('data.manifest.status', 'conciliado')
            ->assertJsonPath('data.manifest.delivery_status', 'completado');

        $this->getJson('/api/v1/cn31/manifests/CN31-DELIVERED-001', [
            'Authorization' => 'Bearer '.$companyReadToken,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.delivery_summary.delivered_packages', 2)
            ->assertJsonPath('data.delivery_summary.registered_bags', 1)
            ->assertJsonPath('data.delivery_summary.completed_bags', 1);

        $this->getJson('/api/v1/cn33/bags/SACA-DELIVERED-001', [
            'Authorization' => 'Bearer '.$companyReadToken,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.delivery_summary.registered_packages', 2)
            ->assertJsonPath('data.delivery_summary.delivered_packages', 2)
            ->assertJsonPath('data.delivery_status', 'completado');

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
            'status' => 'documentado_cn22',
            'delivery_status' => 'completado',
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

    public function test_internal_delivery_api_requires_tracking_code_in_request_body(): void
    {
        $this->postJson('/api/v1/internal/packages/deliver', [], [
            ...$this->internalHeaders(),
            'Accept' => 'application/json',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_code']);
    }

    public function test_internal_delivery_api_returns_friendly_message_when_package_does_not_exist(): void
    {
        $this->postJson('/api/v1/internal/packages/deliver', [
            'tracking_code' => 'EN000009999BO',
        ], [
            ...$this->internalHeaders(),
            'Accept' => 'application/json',
        ])->assertStatus(404)
            ->assertJson([
                'message' => __('api.not_found.package', ['tracking_code' => 'EN000009999BO']),
            ]);
    }

    public function test_internal_delivery_attempt_api_increments_attempts_and_creates_incident_movement(): void
    {
        $company = Company::create([
            'name' => 'Empresa Intentos',
            'slug' => 'empresa-intentos',
            'status' => 'active',
        ]);

        $manifest = Cn31Manifest::create([
            'company_id' => $company->id,
            'cn31_number' => 'CN31-ATTEMPTS-001',
            'origin_office' => 'COCHABAMBA',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => now()->subDay(),
            'total_bags' => 1,
            'total_packages' => 1,
            'total_weight_kg' => 0.500,
            'status' => 'conciliado',
        ]);

        $bag = Cn31Bag::create([
            'company_id' => $company->id,
            'cn31_manifest_id' => $manifest->id,
            'bag_number' => 'SACA-ATTEMPTS-001',
            'declared_package_count' => 1,
            'declared_weight_kg' => 0.500,
            'status' => 'conciliado',
        ]);

        $package = Package::create([
            'company_id' => $company->id,
            'tracking_code' => 'EN000000903BO',
            'recipient_name' => 'Cliente Intentos',
            'status' => 'en_ruta_entrega',
            'registered_at' => now()->subDay(),
            'last_movement_at' => now()->subHour(),
        ]);

        Cn33Package::create([
            'company_id' => $company->id,
            'cn31_bag_id' => $bag->id,
            'package_id' => $package->id,
            'tracking_code' => $package->tracking_code,
            'recipient_name' => 'Cliente Intentos',
            'destination' => 'LA PAZ',
            'weight_kg' => 0.500,
            'status' => 'documentado_cn22',
        ]);

        $occurredAt = now()->subMinutes(30)->toIso8601String();

        $this->postJson('/api/v1/internal/packages/delivery-attempt', [
            'tracking_code' => 'EN000000903BO',
            'occurred_at' => $occurredAt,
            'location' => 'LA PAZ',
            'description' => 'No se encontro al destinatario en domicilio.',
        ], [
            ...$this->internalHeaders(),
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.tracking_code', 'EN000000903BO')
            ->assertJsonPath('data.delivery_attempts', 1)
            ->assertJsonPath('data.status', 'intentos_carteros')
            ->assertJsonPath('data.last_delivery_attempt.location', 'LA PAZ');

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'tracking_code' => 'EN000000903BO',
            'status' => 'intentos_carteros',
            'delivery_attempts' => 1,
        ]);

        $this->assertDatabaseHas('package_movements', [
            'company_id' => $company->id,
            'package_id' => $package->id,
            'status' => 'intentos_carteros',
            'location' => 'LA PAZ',
            'description' => 'No se encontro al destinatario en domicilio.',
        ]);

        $this->assertNotNull(
            Package::find($package->id)?->last_delivery_attempt_at
        );

        $this->assertDatabaseHas('cn33_packages', [
            'tracking_code' => 'EN000000903BO',
            'delivery_status' => 'en_proceso',
        ]);
    }

    public function test_internal_delivery_attempt_api_returns_friendly_message_when_package_does_not_exist(): void
    {
        $this->postJson('/api/v1/internal/packages/delivery-attempt', [
            'tracking_code' => 'EN000009998BO',
            'description' => 'Intento de prueba',
        ], [
            ...$this->internalHeaders(),
            'Accept' => 'application/json',
        ])->assertStatus(404)
            ->assertJson([
                'message' => __('api.not_found.package', ['tracking_code' => 'EN000009998BO']),
            ]);
    }

    public function test_internal_delivery_api_rejects_requests_without_internal_token(): void
    {
        $this->postJson('/api/v1/internal/packages/deliver', [
            'tracking_code' => 'EN000009999BO',
        ])->assertUnauthorized();
    }

    private function internalHeaders(): array
    {
        return [
            'X-Internal-Token' => 'test-internal-api-token',
        ];
    }
}
