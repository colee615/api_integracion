<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Cn31Bag;
use App\Models\Cn31Manifest;
use App\Models\Cn33Package;
use App\Models\Company;
use App\Models\Package;
use App\Models\PackageMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPortalApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_log_in_via_portal_api_and_fetch_dashboard(): void
    {
        $company = Company::create([
            'name' => 'Portal API',
            'slug' => 'portal-api',
            'status' => 'active',
            'locale' => 'en',
        ]);

        User::create([
            'name' => 'Portal API',
            'email' => 'portal-api@empresa.test',
            'password' => '123456789',
            'role' => 'company',
            'status' => 'active',
            'company_id' => $company->id,
        ]);

        $loginResponse = $this->postJson('/api/v1/company/auth/login', [
            'email' => 'portal-api@empresa.test',
            'password' => '123456789',
        ])->assertOk()
            ->assertJsonPath('message', 'Login successful.');

        $token = $loginResponse->json('data.token');

        $this->getJson('/api/v1/company/auth/me', [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.company.name', 'Portal API')
            ->assertJsonPath('data.company.locale', 'en');

        $this->getJson('/api/v1/company/dashboard', [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary',
                    'recent_manifests',
                    'recent_bags',
                    'recent_movements',
                    'recent_packages',
                    'tokens',
                ],
            ]);
    }

    public function test_company_portal_dashboard_only_returns_own_company_data(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa Uno',
            'slug' => 'empresa-uno',
            'status' => 'active',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa Dos',
            'slug' => 'empresa-dos',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Empresa Uno',
            'email' => 'empresa-uno@test.local',
            'password' => '123456789',
            'role' => 'company',
            'status' => 'active',
            'company_id' => $companyA->id,
        ]);

        User::create([
            'name' => 'Empresa Dos',
            'email' => 'empresa-dos@test.local',
            'password' => '123456789',
            'role' => 'company',
            'status' => 'active',
            'company_id' => $companyB->id,
        ]);

        $packageA = Package::create([
            'company_id' => $companyA->id,
            'tracking_code' => 'UNO-001',
            'recipient_name' => 'Cliente Uno',
            'status' => 'registrado',
            'registered_at' => now(),
            'last_movement_at' => now(),
        ]);

        PackageMovement::create([
            'company_id' => $companyA->id,
            'package_id' => $packageA->id,
            'status' => 'clasificado',
            'location' => 'La Paz',
            'occurred_at' => now(),
        ]);

        $manifest = Cn31Manifest::create([
            'company_id' => $companyA->id,
            'cn31_number' => 'CN31-UNO',
            'origin_office' => 'La Paz',
            'destination_office' => 'Cochabamba',
            'dispatch_date' => now(),
            'total_bags' => 1,
            'total_packages' => 1,
            'total_weight_kg' => 0.250,
            'status' => 'conciliado',
            'received_at' => now(),
        ]);

        $bag = Cn31Bag::create([
            'company_id' => $companyA->id,
            'cn31_manifest_id' => $manifest->id,
            'bag_number' => 'SACA-UNO',
            'declared_package_count' => 1,
            'declared_weight_kg' => 0.250,
            'status' => 'conciliado',
            'received_at' => now(),
            'meta' => [
                'loaded_package_count' => 1,
                'loaded_weight_kg' => 0.250,
            ],
        ]);

        Cn33Package::create([
            'company_id' => $companyA->id,
            'cn31_bag_id' => $bag->id,
            'package_id' => $packageA->id,
            'tracking_code' => 'UNO-001',
            'recipient_name' => 'Cliente Uno',
            'destination' => 'La Paz',
            'weight_kg' => 0.250,
            'status' => 'documentado_cn22',
        ]);

        $packageB = Package::create([
            'company_id' => $companyB->id,
            'tracking_code' => 'DOS-001',
            'recipient_name' => 'Cliente Dos',
            'status' => 'registrado',
            'registered_at' => now(),
            'last_movement_at' => now(),
        ]);

        PackageMovement::create([
            'company_id' => $companyB->id,
            'package_id' => $packageB->id,
            'status' => 'entregado',
            'location' => 'Cochabamba',
            'occurred_at' => now(),
        ]);

        ApiToken::issue(
            $companyA,
            'Token Empresa Uno',
            now()->subMinute(),
            now()->addDays(10)
        );

        ApiToken::issue(
            $companyB,
            'Token Empresa Dos',
            now()->subMinute(),
            now()->addDays(10)
        );

        $loginResponse = $this->postJson('/api/v1/company/auth/login', [
            'email' => 'empresa-uno@test.local',
            'password' => '123456789',
        ])->assertOk();

        $token = $loginResponse->json('data.token');

        $this->getJson('/api/v1/company/dashboard', [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.summary.packages', 1)
            ->assertJsonPath('data.summary.movements', 1)
            ->assertJsonPath('data.summary.tokens', 1)
            ->assertJsonPath('data.summary.manifests', 1)
            ->assertJsonPath('data.summary.bags', 1)
            ->assertJsonPath('data.summary.cn33_packages', 1)
            ->assertJsonCount(1, 'data.recent_manifests')
            ->assertJsonCount(1, 'data.recent_bags')
            ->assertJsonCount(1, 'data.recent_movements')
            ->assertJsonCount(1, 'data.recent_packages')
            ->assertJsonPath('data.recent_manifests.0.cn31_number', 'CN31-UNO')
            ->assertJsonPath('data.recent_bags.0.bag_number', 'SACA-UNO')
            ->assertJsonPath('data.recent_movements.0.tracking_code', 'UNO-001');
    }

    public function test_company_can_change_password_with_current_password_confirmation(): void
    {
        $company = Company::create([
            'name' => 'Empresa Password',
            'slug' => 'empresa-password',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Empresa Password',
            'email' => 'empresa-password@test.local',
            'password' => '123456789',
            'role' => 'company',
            'status' => 'active',
            'company_id' => $company->id,
        ]);

        $loginResponse = $this->postJson('/api/v1/company/auth/login', [
            'email' => 'empresa-password@test.local',
            'password' => '123456789',
        ])->assertOk();

        $token = $loginResponse->json('data.token');

        $this->postJson('/api/v1/company/auth/change-password', [
            'current_password' => '123456789',
            'password' => '987654321',
            'password_confirmation' => '987654321',
        ], [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('message', 'Contrasena actualizada correctamente.');

        $this->postJson('/api/v1/company/auth/login', [
            'email' => 'empresa-password@test.local',
            'password' => '987654321',
        ])->assertOk();
    }

    public function test_company_portal_messages_follow_company_locale(): void
    {
        $company = Company::create([
            'name' => 'Portal EN',
            'slug' => 'portal-en',
            'status' => 'active',
            'locale' => 'en',
        ]);

        User::create([
            'name' => 'Portal EN',
            'email' => 'portal-en@test.local',
            'password' => '123456789',
            'role' => 'company',
            'status' => 'active',
            'company_id' => $company->id,
        ]);

        $loginResponse = $this->postJson('/api/v1/company/auth/login', [
            'email' => 'portal-en@test.local',
            'password' => '123456789',
        ])->assertOk()
            ->assertJsonPath('message', 'Login successful.');

        $token = $loginResponse->json('data.token');

        $this->postJson('/api/v1/company/auth/change-password', [
            'current_password' => 'wrong-password',
            'password' => '987654321',
            'password_confirmation' => '987654321',
        ], [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'The current password is incorrect.');

        $this->postJson('/api/v1/company/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('message', 'Session closed successfully.');
    }
}
