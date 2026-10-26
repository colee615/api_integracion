<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Models\Company;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverySummaryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_read_dispatch_and_bag_delivery_summaries(): void
    {
        $company = $this->createCompany('Resumen CN31');
        [, $token] = ApiToken::issue(
            $company,
            'Solo lectura documental',
            now()->subMinute(),
            now()->addDays(10),
            ['documents:read']
        );
        $headers = $this->headers($token);

        $manifest = Cn31Manifest::create([
            'company_id' => $company->id,
            'cn31_number' => 'CN31-SUMMARY-001',
            'origin_office' => 'SHANGHAI',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => now()->subDay(),
            'total_bags' => 2,
            'total_packages' => 3,
            'total_weight_kg' => 1.250,
            'status' => 'conciliado',
            'delivery_status' => 'en_proceso',
        ]);

        $bagInProcess = $this->createBag($company, $manifest, 'BAG-001', 'en_proceso');
        $pendingBag = $this->createBag($company, $manifest, 'BAG-002', 'pendiente');

        $this->createCn33Row($company, $bagInProcess, 'PKG-DONE', 'completado');
        $this->createCn33Row($company, $bagInProcess, 'PKG-PROCESS', 'en_proceso');
        $this->createCn33Row($company, $pendingBag, 'PKG-PENDING', 'pendiente');

        $manifestResponse = $this->getJson('/api/v1/cn31/manifests/CN31-SUMMARY-001', $headers)
            ->assertOk()
            ->assertJsonPath('data.delivery_summary.registered_bags', 2)
            ->assertJsonPath('data.delivery_summary.completed_bags', 0)
            ->assertJsonPath('data.delivery_summary.in_process_bags', 1)
            ->assertJsonPath('data.delivery_summary.pending_bags', 1)
            ->assertJsonPath('data.delivery_summary.registered_packages', 3)
            ->assertJsonPath('data.delivery_summary.delivered_packages', 1)
            ->assertJsonPath('data.delivery_summary.in_process_packages', 1)
            ->assertJsonPath('data.delivery_summary.pending_packages', 1)
            ->assertJsonCount(2, 'data.bags');

        $bagsByNumber = collect($manifestResponse->json('data.bags'))->keyBy('bag_number');
        $this->assertSame(2, $bagsByNumber->get('BAG-001')['delivery_summary']['registered_packages']);
        $this->assertSame(1, $bagsByNumber->get('BAG-001')['delivery_summary']['delivered_packages']);
        $this->assertSame(1, $bagsByNumber->get('BAG-001')['delivery_summary']['in_process_packages']);
        $this->assertSame(1, $bagsByNumber->get('BAG-002')['delivery_summary']['pending_packages']);

        $this->getJson('/api/v1/cn33/bags/BAG-001', $headers)
            ->assertOk()
            ->assertJsonPath('data.bag_number', 'BAG-001')
            ->assertJsonPath('data.delivery_summary.registered_packages', 2)
            ->assertJsonPath('data.delivery_summary.delivered_packages', 1)
            ->assertJsonPath('data.delivery_summary.in_process_packages', 1)
            ->assertJsonPath('data.delivery_summary.pending_packages', 0)
            ->assertJsonCount(2, 'data.packages');

        $this->getJson('/api/v1/packages/PKG-DONE', $headers)->assertForbidden();
        $this->getJson('/api/v1/cn33/bags/BAG-001/movements', $headers)->assertForbidden();
    }

    public function test_cn31_manifest_list_supports_date_dispatch_and_pagination_filters(): void
    {
        $company = $this->createCompany('Filtros CN31');
        [, $token] = ApiToken::issue(
            $company,
            'Consulta de despachos',
            now()->subMinute(),
            now()->addDays(10),
            ['documents:read']
        );
        $headers = $this->headers($token);

        $this->createManifestForList($company, 'CN31-FILTER-001', '0063', '2026-10-10 09:00:00', '2026-10-12 08:00:00');
        $this->createManifestForList($company, 'CN31-FILTER-002', '0000000002', '2026-10-12 10:00:00', '2026-10-15 08:00:00');
        $this->createManifestForList($company, 'CN31-FILTER-003', '0000000003', '2026-10-12 11:00:00', '2026-10-12 18:00:00');

        $this->getJson('/api/v1/cn31/manifests?dispatch_date_from=2026-10-12&dispatch_date_to=2026-10-12', $headers)
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->getJson('/api/v1/cn31/manifests?received_at_from=2026-10-12&received_at_to=2026-10-12', $headers)
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->getJson('/api/v1/cn31/manifests?dispatch_number_manifest=0063', $headers)
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.cn31_number', 'CN31-FILTER-001');

        $pagedResponse = $this->getJson('/api/v1/cn31/manifests?received_at_from=2026-10-12&received_at_to=2026-10-12&per_page=1&page=1', $headers)
            ->assertOk()
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total', 2);
        $this->assertStringContainsString('per_page=1', $pagedResponse->json('next_page_url'));
        $this->assertStringContainsString('received_at_from=2026-10-12', $pagedResponse->json('next_page_url'));

        $this->getJson('/api/v1/cn31/manifests?dispatch_date_from=2026-10-15&dispatch_date_to=2026-10-12', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'CN31_FILTER_VALIDATION_ERROR');
        $this->getJson('/api/v1/cn31/manifests?received_at_from=12-10-2026', $headers)->assertUnprocessable();
        $this->getJson('/api/v1/cn31/manifests?per_page=101', $headers)->assertUnprocessable();
        $this->getJson('/api/v1/cn31/manifests?dispatch_number_manifest=12A', $headers)->assertUnprocessable();
    }

    public function test_package_detail_exposes_origin_from_its_cn33_row(): void
    {
        $company = $this->createCompany('Origen CN33');
        [, $token] = ApiToken::issue(
            $company,
            'Consulta de tracking',
            now()->subMinute(),
            now()->addDays(10),
            ['tracking:read']
        );

        $package = Package::create([
            'company_id' => $company->id,
            'tracking_code' => 'WS34353433222',
            'recipient_name' => 'Destinatario de prueba',
            'destination' => 'LIMA',
            'status' => 'pre_alerta_recibida',
        ]);
        $manifest = Cn31Manifest::create([
            'company_id' => $company->id,
            'cn31_number' => 'CN31-ORIGIN-001',
            'origin_office' => 'SHANGHAI',
            'destination_office' => 'LIMA',
            'dispatch_date' => now()->subDay(),
            'total_bags' => 1,
            'total_packages' => 1,
            'total_weight_kg' => 0.250,
        ]);
        $bag = $this->createBag($company, $manifest, 'ORIGIN-BAG', 'pendiente');
        Cn33Package::create([
            'company_id' => $company->id,
            'cn31_bag_id' => $bag->id,
            'package_id' => $package->id,
            'tracking_code' => $package->tracking_code,
            'origin' => 'SHANGHAI',
            'destination' => 'LIMA',
            'weight_kg' => 0.250,
            'status' => 'documentado_cn22',
            'delivery_status' => 'pendiente',
        ]);

        $this->getJson('/api/v1/packages/'.$package->tracking_code, $this->headers($token))
            ->assertOk()
            ->assertJsonPath('data.origin', 'SHANGHAI')
            ->assertJsonPath('data.destination', 'LIMA');

        $this->getJson('/api/v1/packages?tracking_code='.$package->tracking_code, $this->headers($token))
            ->assertOk()
            ->assertJsonPath('data.0.origin', 'SHANGHAI')
            ->assertJsonPath('data.0.destination', 'LIMA');
    }

    public function test_token_from_another_company_cannot_read_dispatch_or_bag(): void
    {
        $owner = $this->createCompany('Empresa propietaria');
        $other = $this->createCompany('Otra empresa');
        [, $ownerToken] = ApiToken::issue($owner, 'Owner', now()->subMinute(), now()->addDays(10));
        [, $otherToken] = ApiToken::issue($other, 'Other', now()->subMinute(), now()->addDays(10));
        $ownerHeaders = $this->headers($ownerToken);
        $otherHeaders = $this->headers($otherToken);

        $manifest = Cn31Manifest::create([
            'company_id' => $owner->id,
            'cn31_number' => 'CN31-PRIVATE-001',
            'origin_office' => 'SHANGHAI',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => now()->subDay(),
            'total_bags' => 1,
            'total_packages' => 1,
            'total_weight_kg' => 0.250,
            'status' => 'conciliado',
        ]);
        $bag = $this->createBag($owner, $manifest, 'PRIVATE-BAG', 'pendiente');

        $this->getJson('/api/v1/cn31/manifests/CN31-PRIVATE-001', $ownerHeaders)->assertOk();
        $this->getJson('/api/v1/cn33/bags/PRIVATE-BAG', $ownerHeaders)->assertOk();

        $this->getJson('/api/v1/cn31/manifests/CN31-PRIVATE-001', $otherHeaders)
            ->assertNotFound()
            ->assertJsonPath('code', 'CN31_NOT_FOUND');
        $this->getJson('/api/v1/cn33/bags/PRIVATE-DISPATCH', $otherHeaders)
            ->assertNotFound()
            ->assertJsonPath('code', 'CN33_BAG_NOT_FOUND');
    }

    public function test_trial_data_is_visible_only_to_the_trial_token_that_created_it(): void
    {
        $company = $this->createCompany('Empresa sandbox');
        [$creator, $creatorToken] = ApiToken::issue(
            $company,
            'Trial creador',
            now()->subMinute(),
            now()->addDays(10),
            ['documents:read'],
            'trial'
        );
        [, $secondTrialToken] = ApiToken::issue(
            $company,
            'Otro trial',
            now()->subMinute(),
            now()->addDays(10),
            ['documents:read'],
            'trial'
        );
        [, $productionToken] = ApiToken::issue(
            $company,
            'Producción',
            now()->subMinute(),
            now()->addDays(10),
            ['documents:read'],
            'production'
        );

        $manifest = Cn31Manifest::create([
            'company_id' => $company->id,
            'created_by_api_token_id' => $creator->id,
            'cn31_number' => 'CN31-TRIAL-001',
            'origin_office' => 'SHANGHAI',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => now()->subDay(),
            'total_bags' => 1,
            'total_packages' => 0,
            'total_weight_kg' => 0,
            'status' => 'pendiente_cn33',
        ]);
        $this->createBag($company, $manifest, 'TRIAL-BAG', 'pendiente', $creator->id);

        $this->getJson('/api/v1/cn31/manifests/CN31-TRIAL-001', $this->headers($creatorToken))->assertOk();
        $this->getJson('/api/v1/cn31/manifests/CN31-TRIAL-001', $this->headers($secondTrialToken))->assertNotFound();
        $this->getJson('/api/v1/cn31/manifests/CN31-TRIAL-001', $this->headers($productionToken))->assertNotFound();

        $this->getJson('/api/v1/cn31/manifests', $this->headers($creatorToken))->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/cn31/manifests', $this->headers($secondTrialToken))->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/cn31/manifests', $this->headers($productionToken))->assertOk()->assertJsonPath('total', 0);
    }

    private function createManifestForList(
        Company $company,
        string $cn31Number,
        string $dispatchNumber,
        string $dispatchDate,
        string $receivedAt
    ): Cn31Manifest {
        return Cn31Manifest::create([
            'company_id' => $company->id,
            'cn31_number' => $cn31Number,
            'dispatch_number_manifest' => $dispatchNumber,
            'origin_office' => 'SHANGHAI',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => $dispatchDate,
            'received_at' => $receivedAt,
            'total_bags' => 0,
            'total_packages' => 0,
            'total_weight_kg' => 0,
            'status' => 'pendiente_cn33',
        ]);
    }

    private function createCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.str()->random(6),
            'status' => 'active',
        ]);
    }

    private function createBag(
        Company $company,
        Cn31Manifest $manifest,
        string $bagNumber,
        string $deliveryStatus,
        ?int $tokenId = null
    ): Cn31Bag {
        return Cn31Bag::create([
            'company_id' => $company->id,
            'created_by_api_token_id' => $tokenId,
            'cn31_manifest_id' => $manifest->id,
            'bag_number' => $bagNumber,
            'declared_package_count' => 1,
            'declared_weight_kg' => 0.250,
            'status' => 'conciliado',
            'operational_status' => 'abierto',
            'delivery_status' => $deliveryStatus,
        ]);
    }

    private function createCn33Row(Company $company, Cn31Bag $bag, string $trackingCode, string $deliveryStatus): Cn33Package
    {
        return Cn33Package::create([
            'company_id' => $company->id,
            'cn31_bag_id' => $bag->id,
            'tracking_code' => $trackingCode,
            'origin' => 'SHANGHAI',
            'destination' => 'LA PAZ',
            'weight_kg' => 0.250,
            'status' => 'documentado_cn22',
            'delivery_status' => $deliveryStatus,
        ]);
    }

    private function headers(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }
}
