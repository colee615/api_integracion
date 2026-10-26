<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Cn31Bag;
use App\Models\Cn31BagMovement;
use App\Models\Cn31Manifest;
use App\Models\PackageMovement;
use App\Models\Company;
use App\Models\Package;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_valid_token(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Token not provided.',
            ]);
    }

    public function test_api_json_messages_follow_company_locale_for_operational_endpoints(): void
    {
        $company = Company::create([
            'name' => 'English Company',
            'slug' => 'english-company',
            'status' => 'active',
            'locale' => 'en',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'EN Token',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/cn31/manifests', [
            'cn31_number' => 'CN31-EN-001',
            'dispatch_number_manifest' => '0001',
            'origin_office' => 'COCHABAMBA',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => '2026-03-31 16:00:00',
            'bags' => [
                [
                    'bag_number' => 'SACA-EN-001',
                    'package_count' => 1,
                    'total_weight_kg' => 0.250,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('message', 'CN31 received successfully.');

        $this->postJson('/api/v1/cn33/bags/SACA-EN-001/packages', [
            'packages' => [
                [
                    'tracking_code' => 'EN000000201BO',
                    'origin' => 'COCHABAMBA',
                    'destination' => 'LA PAZ',
                    'weight_kg' => 0.250,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('message', 'CN33 received successfully.');

        $this->postJson('/api/v1/cn22/shipments', [
            'records' => [
                [
                    'tracking_code' => 'EN000000201BO',
                    'origin_office' => 'COCHABAMBA',
                    'destination_office' => 'LA PAZ',
                    'sender_name' => 'Sender EN',
                    'sender_country' => 'BOLIVIA',
                    'sender_address' => 'Main street 10',
                    'sender_phone' => '70000010',
                    'recipient_name' => 'John Doe',
                    'recipient_document' => '5555555',
                    'recipient_address' => 'Street 20',
                    'recipient_address_reference' => 'Blue door',
                    'recipient_city' => 'LA PAZ',
                    'recipient_department' => 'LA PAZ',
                    'recipient_phone' => '70000011',
                    'description' => 'books',
                    'gross_weight_grams' => 250,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 2,
                    'value_fob_usd' => 17.00,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('message', 'CN22 batch received successfully.')
            ->assertJsonPath('data.results.0.message', 'CN22 record received successfully by the system.');

        $this->postJson('/api/v1/packages/EN000000201BO/movements', [
            'status' => 'en_proceso_aduana',
            'location' => 'Airport',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('message', 'Movement registered successfully.');
    }

    public function test_company_can_create_package_and_movements_with_a_valid_token(): void
    {
        $company = Company::create([
            'name' => 'Empresa Demo',
            'slug' => 'empresa-demo',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Nuxt Test',
            now()->subMinute(),
            now()->addDays(30)
        );

        $this->assertSame($plainTextToken, $company->apiTokens()->latest()->first()->token_secret);

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/packages', [
            'tracking_code' => 'PKG-001',
            'recipient_name' => 'Cliente Uno',
            'destination' => 'La Paz',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.tracking_code', 'PKG-001');

        $this->postJson('/api/v1/packages/PKG-001/movements', [
            'status' => 'en_transito',
            'location' => 'Santa Cruz',
            'description' => 'Salio del centro logístico',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'en_transito');

        $this->getJson('/api/v1/packages/PKG-001', $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'en_transito')
            ->assertJsonCount(2, 'data.movements');
    }

    public function test_expired_token_is_rejected(): void
    {
        $company = Company::create([
            'name' => 'Empresa Expirada',
            'slug' => 'empresa-expirada',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Token Vencido',
            now()->subDays(2),
            now()->subMinute()
        );

        $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ])->assertUnauthorized();
    }

    public function test_company_token_cannot_access_packages_from_another_company(): void
    {
        $companyA = Company::create([
            'name' => 'Empresa A',
            'slug' => 'empresa-a',
            'status' => 'active',
        ]);

        $companyB = Company::create([
            'name' => 'Empresa B',
            'slug' => 'empresa-b',
            'status' => 'active',
        ]);

        [, $tokenA] = ApiToken::issue(
            $companyA,
            'Token A',
            now()->subMinute(),
            now()->addDays(30)
        );

        [, $tokenB] = ApiToken::issue(
            $companyB,
            'Token B',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headersA = [
            'Authorization' => 'Bearer '.$tokenA,
            'Accept' => 'application/json',
        ];

        $headersB = [
            'Authorization' => 'Bearer '.$tokenB,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/packages', [
            'tracking_code' => 'A-001',
            'recipient_name' => 'Cliente A',
        ], $headersA)->assertCreated();

        $this->postJson('/api/v1/packages', [
            'tracking_code' => 'B-001',
            'recipient_name' => 'Cliente B',
        ], $headersB)->assertCreated();

        $this->getJson('/api/v1/packages/A-001', $headersB)->assertNotFound();
        $this->getJson('/api/v1/packages/B-001', $headersA)->assertNotFound();
    }

    public function test_token_can_be_revoked_and_reactivated(): void
    {
        $company = Company::create([
            'name' => 'Empresa Tokens',
            'slug' => 'empresa-tokens',
            'status' => 'active',
        ]);

        [$token] = ApiToken::issue(
            $company,
            'Token Operativo',
            now()->subMinute(),
            now()->addDays(10)
        );

        $this->assertTrue($token->canUse());

        $token->forceFill(['revoked_at' => now()])->save();
        $this->assertFalse($token->fresh()->canUse());

        $token->fresh()->forceFill(['revoked_at' => null])->save();
        $this->assertTrue($token->fresh()->canUse());
    }

    public function test_company_can_send_cn22_batch_with_multiple_records(): void
    {
        $company = Company::create([
            'name' => 'Empresa CN22',
            'slug' => 'empresa-cn22',
            'status' => 'active',
            'locale' => 'es',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'CN22 Token',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $response = $this->postJson('/api/v1/cn22/shipments', [
            'records' => [
                [
                    'tracking_code' => 'EN000000001BO',
                    'origin_office' => 'COCHABAMBA',
                    'destination_office' => 'LA PAZ',
                    'sender_name' => 'Leonardo Doria Medina',
                    'sender_country' => 'BOLIVIA',
                    'sender_address' => 'Av. Sendero 123',
                    'sender_phone' => '78458965',
                    'recipient_name' => 'Marco Antonio Espinoza Rojas',
                    'recipient_document' => '1234567',
                    'recipient_address' => 'Av Mario Mercado 220',
                    'recipient_address_reference' => 'Frente a la plaza principal',
                    'recipient_city' => 'LA PAZ',
                    'recipient_department' => 'LA PAZ',
                    'recipient_phone' => '76785423',
                    'recipient_whatsapp' => '76785423',
                    'destination' => 'LA PAZ',
                    'description' => 'documentos',
                    'gross_weight_grams' => 250,
                    'weight_kg' => 0.250,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 2,
                    'value_fob_usd' => 17.00,
                    'customs_items' => [
                        [
                            'description' => 'Documentos',
                            'quantity' => 1,
                            'value' => 17.00,
                            'weight_kg' => 0.250,
                        ],
                    ],
                ],
                [
                    'tracking_code' => 'EN000000004BO',
                    'origin_office' => 'COCHABAMBA',
                    'destination_office' => 'LA PAZ',
                    'sender_name' => 'Leonardo Doria Medina',
                    'sender_country' => 'BOLIVIA',
                    'sender_address' => 'Av. Sendero 123',
                    'sender_phone' => '78458965',
                    'recipient_name' => 'Marco Antonio Espinoza Rojas',
                    'recipient_document' => '1234568',
                    'recipient_address' => 'Av Mario Mercado 220',
                    'recipient_address_reference' => 'Puerta azul',
                    'recipient_city' => 'LA PAZ',
                    'recipient_department' => 'LA PAZ',
                    'recipient_phone' => '76785423',
                    'recipient_whatsapp' => '76785423',
                    'destination' => 'LA PAZ',
                    'description' => 'documentos',
                    'gross_weight_grams' => 250,
                    'weight_kg' => 0.250,
                    'length_cm' => 22,
                    'width_cm' => 18,
                    'height_cm' => 3,
                    'value_fob_usd' => 21.00,
                ],
            ],
        ], $headers)->assertCreated();

        $response->assertJsonPath('data.received', 2)
            ->assertJsonCount(2, 'data.results')
            ->assertJsonPath('data.results.0.status', 'pre_alerta_recibida');

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'tracking_code' => 'EN000000001BO',
            'status' => 'pre_alerta_recibida',
            'sender_country' => 'BOLIVIA',
            'gross_weight_grams' => 250,
        ]);

        $this->assertTrue(
            PackageMovement::query()
                ->where('company_id', $company->id)
                ->where('status', 'pre_alerta_recibida')
                ->whereIn('description', [
                    'Pre-alerta CN22 recibida antes del arribo fisico.',
                    'CN22 pre-alert received before physical arrival.',
                ])
                ->exists()
        );
    }

    public function test_company_can_register_cn31_and_cn33_then_link_cn22_package(): void
    {
        $company = Company::create([
            'name' => 'Empresa Integrada',
            'slug' => 'empresa-integrada',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Token Integracion Postal',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/cn31/manifests', [
            'cn31_number' => 'CN31-0001-BO',
            'dispatch_number_manifest' => '0001',
            'origin_office' => 'COCHABAMBA',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => '2026-03-31 16:00:00',
            'bags' => [
                [
                    'bag_number' => 'SACA-001',
                    'package_count' => 2,
                    'total_weight_kg' => 0.500,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pendiente_cn33');

        $this->postJson('/api/v1/cn33/bags/SACA-001/packages', [
            'packages' => [
                [
                    'tracking_code' => 'EN000000001BO',
                    'origin' => 'COCHABAMBA',
                    'destination' => 'LA PAZ',
                    'weight_kg' => 0.250,
                ],
                [
                    'tracking_code' => 'EN000000004BO',
                    'origin' => 'COCHABAMBA',
                    'destination' => 'LA PAZ',
                    'weight_kg' => 0.250,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'conciliado')
            ->assertJsonPath('data.loaded_package_count', 2);

        $this->postJson('/api/v1/cn22/shipments', [
            'records' => [
                [
                    'tracking_code' => 'EN000000001BO',
                    'origin_office' => 'COCHABAMBA',
                    'destination_office' => 'LA PAZ',
                    'sender_name' => 'Leonardo Doria Medina',
                    'sender_country' => 'BOLIVIA',
                    'sender_address' => 'Av. Sendero 123',
                    'sender_phone' => '78458965',
                    'recipient_name' => 'Marco Antonio Espinoza Rojas',
                    'recipient_document' => '1234567',
                    'recipient_address' => 'Av Mario Mercado 220',
                    'recipient_address_reference' => 'Puerta cafe al lado de farmacia',
                    'recipient_city' => 'LA PAZ',
                    'recipient_department' => 'LA PAZ',
                    'recipient_phone' => '76785423',
                    'recipient_whatsapp' => '76785423',
                    'destination' => 'LA PAZ',
                    'description' => 'documentos',
                    'gross_weight_grams' => 250,
                    'weight_kg' => 0.250,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 2,
                    'value_fob_usd' => 17.00,
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.results.0.tracking_code', 'EN000000001BO');

        $this->assertDatabaseHas('cn31_manifests', [
            'company_id' => $company->id,
            'cn31_number' => 'CN31-0001-BO',
            'status' => 'conciliado',
        ]);

        $this->assertDatabaseHas('cn31_bags', [
            'company_id' => $company->id,
            'bag_number' => 'SACA-001',
            'status' => 'conciliado',
        ]);

        $package = Package::query()
            ->where('company_id', $company->id)
            ->where('tracking_code', 'EN000000001BO')
            ->first();

        $this->assertNotNull($package);
        $this->assertSame('CN31-0001-BO', $package->meta['cn31_number']);
        $this->assertSame('SACA-001', $package->meta['bag_number']);

        $this->assertDatabaseHas('cn33_packages', [
            'company_id' => $company->id,
            'tracking_code' => 'EN000000001BO',
            'status' => 'documentado_cn22',
            'package_id' => $package->id,
        ]);
    }

    public function test_company_can_register_cn31_cn33_and_cn22_in_one_bulk_request(): void
    {
        $company = Company::create([
            'name' => 'Empresa Bulk',
            'slug' => 'empresa-bulk',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Token Bulk',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/integration/bulk', [
            'manifest' => [
                'cn31_number' => 'CN31-BULK-001',
                'dispatch_number_manifest' => '0001',
                'origin_office' => 'COCHABAMBA',
                'destination_office' => 'LA PAZ',
                'dispatch_date' => '2026-04-08 11:00:00',
                'bags' => [
                    [
                        'bag_number' => 'SACA-BULK-001',
                        'package_count' => 2,
                        'total_weight_kg' => 0.500,
                        'dispatch_note' => 'Carga masiva principal',
                        'packages' => [
                            [
                                'tracking_code' => 'EN000000801BO',
                                'origin' => 'COCHABAMBA',
                                'reference' => 'BULK-001',
                                'destination' => 'LA PAZ',
                                'weight_kg' => 0.250,
                                'notes' => 'Primer paquete',
                                'cn22' => [
                                    'origin_office' => 'COCHABAMBA',
                                    'destination_office' => 'LA PAZ',
                                    'sender_name' => '360 Lions',
                                    'sender_country' => 'BOLIVIA',
                                    'sender_address' => 'Av. Integracion 100',
                                    'sender_phone' => '70000001',
                                    'recipient_name' => 'Cliente Uno',
                                    'recipient_document' => '7000001',
                                    'recipient_address' => 'Calle Uno 123',
                                    'recipient_address_reference' => 'Puerta azul',
                                    'recipient_city' => 'LA PAZ',
                                    'recipient_department' => 'LA PAZ',
                                    'recipient_phone' => '71111111',
                                    'recipient_whatsapp' => '71111111',
                                    'description' => 'Documentos',
                                    'gross_weight_grams' => 250,
                                    'length_cm' => 20,
                                    'width_cm' => 15,
                                    'height_cm' => 2,
                                    'value_fob_usd' => 18.00,
                                ],
                            ],
                            [
                                'tracking_code' => 'EN000000802BO',
                                'origin' => 'COCHABAMBA',
                                'reference' => 'BULK-002',
                                'destination' => 'LA PAZ',
                                'weight_kg' => 0.250,
                                'notes' => 'Segundo paquete',
                                'cn22' => [
                                    'origin_office' => 'COCHABAMBA',
                                    'destination_office' => 'LA PAZ',
                                    'sender_name' => '360 Lions',
                                    'sender_country' => 'BOLIVIA',
                                    'sender_address' => 'Av. Integracion 100',
                                    'sender_phone' => '70000001',
                                    'recipient_name' => 'Cliente Dos',
                                    'recipient_document' => '7000002',
                                    'recipient_address' => 'Calle Dos 456',
                                    'recipient_address_reference' => 'Frente a plaza',
                                    'recipient_city' => 'LA PAZ',
                                    'recipient_department' => 'LA PAZ',
                                    'recipient_phone' => '72222222',
                                    'recipient_whatsapp' => '72222222',
                                    'description' => 'Ropa',
                                    'gross_weight_grams' => 250,
                                    'length_cm' => 22,
                                    'width_cm' => 16,
                                    'height_cm' => 4,
                                    'value_fob_usd' => 25.00,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cn31_number', 'CN31-BULK-001')
            ->assertJsonPath('data.total_bags', 1)
            ->assertJsonPath('data.total_packages', 2)
            ->assertJsonPath('data.bags.0.status', 'conciliado')
            ->assertJsonPath('data.packages.0.status', 'pre_alerta_recibida');

        $this->assertDatabaseHas('cn31_manifests', [
            'company_id' => $company->id,
            'cn31_number' => 'CN31-BULK-001',
            'status' => 'conciliado',
        ]);

        $this->assertDatabaseHas('cn31_bags', [
            'company_id' => $company->id,
            'bag_number' => 'SACA-BULK-001',
            'status' => 'conciliado',
        ]);

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'tracking_code' => 'EN000000801BO',
            'status' => 'pre_alerta_recibida',
        ]);

        $this->assertDatabaseHas('cn33_packages', [
            'company_id' => $company->id,
            'tracking_code' => 'EN000000801BO',
            'status' => 'documentado_cn22',
        ]);
    }

    public function test_bulk_request_is_rejected_when_declared_weights_do_not_match_loaded_packages(): void
    {
        $company = Company::create([
            'name' => 'Empresa Bulk Invalido',
            'slug' => 'empresa-bulk-invalido',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Token Bulk Invalido',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/integration/bulk', [
            'manifest' => [
                'cn31_number' => 'CN31-BULK-INVALID-001',
                'dispatch_number_manifest' => '0002',
                'origin_office' => 'COCHABAMBA',
                'destination_office' => 'LA PAZ',
                'dispatch_date' => '2026-04-08 11:00:00',
                'bags' => [
                    [
                        'bag_number' => 'SACA-BULK-INVALID-001',
                        'package_count' => 2,
                        'total_weight_kg' => 0.700,
                        'packages' => [
                            [
                                'tracking_code' => 'EN000000901BO',
                                'origin' => 'COCHABAMBA',
                                'destination' => 'LA PAZ',
                                'weight_kg' => 0.250,
                                'cn22' => [
                                    'origin_office' => 'COCHABAMBA',
                                    'destination_office' => 'LA PAZ',
                                    'sender_name' => '360 Lions',
                                    'sender_country' => 'BOLIVIA',
                                    'sender_address' => 'Av. Integracion 100',
                                    'sender_phone' => '70000001',
                                    'recipient_name' => 'Cliente Uno',
                                    'recipient_document' => '7000901',
                                    'recipient_address' => 'Calle Uno 123',
                                    'recipient_address_reference' => 'Puerta azul',
                                    'recipient_city' => 'LA PAZ',
                                    'recipient_department' => 'LA PAZ',
                                    'recipient_phone' => '71111111',
                                    'recipient_whatsapp' => '71111111',
                                    'description' => 'Documentos',
                                    'gross_weight_grams' => 250,
                                    'length_cm' => 20,
                                    'width_cm' => 15,
                                    'height_cm' => 2,
                                    'value_fob_usd' => 18.00,
                                ],
                            ],
                            [
                                'tracking_code' => 'EN000000902BO',
                                'origin' => 'COCHABAMBA',
                                'destination' => 'LA PAZ',
                                'weight_kg' => 0.250,
                                'cn22' => [
                                    'origin_office' => 'COCHABAMBA',
                                    'destination_office' => 'LA PAZ',
                                    'sender_name' => '360 Lions',
                                    'sender_country' => 'BOLIVIA',
                                    'sender_address' => 'Av. Integracion 100',
                                    'sender_phone' => '70000001',
                                    'recipient_name' => 'Cliente Dos',
                                    'recipient_document' => '7000902',
                                    'recipient_address' => 'Calle Dos 456',
                                    'recipient_address_reference' => 'Frente a plaza',
                                    'recipient_city' => 'LA PAZ',
                                    'recipient_department' => 'LA PAZ',
                                    'recipient_phone' => '72222222',
                                    'recipient_whatsapp' => '72222222',
                                    'description' => 'Ropa',
                                    'gross_weight_grams' => 250,
                                    'length_cm' => 22,
                                    'width_cm' => 16,
                                    'height_cm' => 4,
                                    'value_fob_usd' => 25.00,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'BULK_INTEGRATION_VALIDATION_ERROR')
            ->assertJsonFragment([
                'field' => 'manifest.bags.0.total_weight_kg',
            ]);
    }

    public function test_cn31_and_cn33_return_understandable_validation_errors(): void
    {
        $company = Company::create([
            'name' => 'Empresa Errores',
            'slug' => 'empresa-errores',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Token Errores',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/cn31/manifests', [
            'cn31_number' => 'CN31-ERROR',
            'dispatch_number_manifest' => '0001',
            'origin_office' => 'COCHABAMBA',
            'bags' => [
                [
                    'bag_number' => 'SACA-001',
                ],
            ],
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CN31_VALIDATION_ERROR')
            ->assertJsonFragment([
                'field' => 'destination_office',
                'attribute' => 'destination_office',
            ])
            ->assertJsonFragment([
                'field' => 'bags.0.package_count',
                'record_index' => 0,
                'attribute' => 'package_count',
            ]);

        $this->postJson('/api/v1/cn31/manifests', [
            'cn31_number' => 'CN31-VALIDO',
            'dispatch_number_manifest' => '0002',
            'origin_office' => 'COCHABAMBA',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => '2026-03-31 16:00:00',
            'bags' => [
                [
                    'bag_number' => 'SACA-VALIDA',
                    'package_count' => 1,
                    'total_weight_kg' => 0.250,
                ],
            ],
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/cn33/bags/SACA-VALIDA/packages', [
            'packages' => [
                [
                    'tracking_code' => 'EN000000001BO',
                    'origin' => 'COCHABAMBA',
                ],
            ],
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CN33_VALIDATION_ERROR')
            ->assertJsonFragment([
                'field' => 'packages.0.destination',
                'record_index' => 0,
                'attribute' => 'destination',
            ]);
    }

    public function test_cn22_requires_mandatory_fields_and_rejects_duplicates(): void
    {
        $company = Company::create([
            'name' => 'Empresa CN22 Validacion',
            'slug' => 'empresa-cn22-validacion',
            'status' => 'active',
            'locale' => 'es',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'CN22 Token Validacion',
            now()->subMinute(),
            now()->addDays(30)
        );

        $headers = [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ];

        $response = $this->postJson('/api/v1/cn22/shipments', [
            'records' => [
                [
                    'tracking_code' => 'EN000000010BO',
                    'origin_office' => 'COCHABAMBA',
                ],
                [
                    'tracking_code' => 'EN000000010BO',
                    'origin_office' => 'COCHABAMBA',
                ],
            ],
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CN22_VALIDATION_ERROR')
            ->assertJsonFragment([
                'field' => 'records.0.destination_office',
                'record_index' => 0,
                'attribute' => 'destination_office',
            ])
            ->assertJsonFragment([
                'field' => 'records.1.tracking_code',
                'record_index' => 1,
                'attribute' => 'tracking_code',
            ]);

        $this->assertContains($response->json('message'), [
            'El lote CN22 contiene errores de validacion.',
            'The CN22 batch contains validation errors.',
        ]);
    }

    public function test_panel_token_is_the_only_supported_api_authentication_method(): void
    {
        $company = Company::create([
            'name' => 'Empresa Solo Token',
            'slug' => 'empresa-solo-token',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Token Principal',
            now()->subMinute(),
            now()->addDays(30)
        );

        $this->postJson('/api/v1/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'unused',
            'client_secret' => 'unused',
        ], [
            'X-API-Key' => $company->api_key,
            'Accept' => 'application/json',
        ])->assertNotFound();

        $this->getJson('/api/v1/me', [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.company.name', 'Empresa Solo Token')
            ->assertJsonPath('data.token.auth_mode', 'legacy_token');
    }

    public function test_package_status_movement_dispatches_protocol_webhook(): void
    {
        Http::fake([
            'https://example.com/webhooks/agbc' => Http::response(['ok' => true], 200),
        ]);

        $company = Company::create([
            'name' => 'Empresa Webhook',
            'slug' => 'empresa-webhook',
            'status' => 'active',
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Webhook Token',
            now()->subMinute(),
            now()->addDays(30)
        );

        WebhookEndpoint::create([
            'company_id' => $company->id,
            'name' => '360Lion',
            'target_url' => 'https://example.com/webhooks/agbc',
            'secret' => 'super-secret-signature',
            'events' => ['shipment.customs_in_progress'],
            'is_active' => true,
        ]);

        $package = Package::create([
            'company_id' => $company->id,
            'tracking_code' => 'EN000000099BO',
            'tracking_standard' => 'UPU_S10',
            'recipient_name' => 'Cliente Webhook',
            'recipient_document' => '9999999',
            'recipient_city' => 'LA PAZ',
            'recipient_department' => 'LA PAZ',
            'status' => 'pre_alerta_recibida',
            'registered_at' => now(),
            'last_movement_at' => now(),
        ]);

        $this->postJson('/api/v1/packages/EN000000099BO/movements', [
            'status' => 'en_proceso_aduana',
            'location' => 'Aeropuerto El Alto',
            'description' => 'Ingreso a canal aduanero',
        ], [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'en_proceso_aduana');

        Http::assertSentCount(1);

        $this->assertDatabaseHas('webhook_deliveries', [
            'company_id' => $company->id,
            'event' => 'shipment.customs_in_progress',
            'tracking_code' => 'EN000000099BO',
            'success' => 1,
        ]);
    }

    public function test_sandbox_rejects_cn22_when_shipment_limit_would_be_exceeded(): void
    {
        $company = Company::create([
            'name' => 'Empresa Sandbox',
            'slug' => 'empresa-sandbox',
            'status' => 'active',
            'environment' => 'sandbox',
            'sandbox_starts_at' => now()->subDay(),
            'sandbox_ends_at' => now()->addDays(14),
            'sandbox_max_shipments' => 100,
            'sandbox_shipments_used' => 99,
        ]);

        [, $plainTextToken] = ApiToken::issue(
            $company,
            'Sandbox Token',
            now()->subMinute(),
            now()->addDays(10)
        );

        $this->postJson('/api/v1/cn22/shipments', [
            'records' => [
                [
                    'tracking_code' => 'EN000000111BO',
                    'origin_office' => 'COCHABAMBA',
                    'destination_office' => 'LA PAZ',
                    'sender_name' => 'Remitente Uno',
                    'sender_country' => 'BOLIVIA',
                    'sender_address' => 'Av. Uno',
                    'sender_phone' => '70000001',
                    'recipient_name' => 'Cliente Uno',
                    'recipient_document' => '1111111',
                    'recipient_address' => 'Calle Uno',
                    'recipient_address_reference' => 'Casa roja',
                    'recipient_city' => 'LA PAZ',
                    'recipient_department' => 'LA PAZ',
                    'recipient_phone' => '70000002',
                    'description' => 'ropa deportiva',
                    'gross_weight_grams' => 500,
                    'length_cm' => 20,
                    'width_cm' => 20,
                    'height_cm' => 10,
                    'value_fob_usd' => 12.50,
                ],
                [
                    'tracking_code' => 'EN000000112BO',
                    'origin_office' => 'COCHABAMBA',
                    'destination_office' => 'LA PAZ',
                    'sender_name' => 'Remitente Dos',
                    'sender_country' => 'BOLIVIA',
                    'sender_address' => 'Av. Dos',
                    'sender_phone' => '70000003',
                    'recipient_name' => 'Cliente Dos',
                    'recipient_document' => '2222222',
                    'recipient_address' => 'Calle Dos',
                    'recipient_address_reference' => 'Edificio verde',
                    'recipient_city' => 'LA PAZ',
                    'recipient_department' => 'LA PAZ',
                    'recipient_phone' => '70000004',
                    'description' => 'accesorios',
                    'gross_weight_grams' => 400,
                    'length_cm' => 18,
                    'width_cm' => 12,
                    'height_cm' => 8,
                    'value_fob_usd' => 9.80,
                ],
            ],
        ], [
            'Authorization' => 'Bearer '.$plainTextToken,
            'Accept' => 'application/json',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'SANDBOX_SHIPMENT_LIMIT_REACHED');
    }

    public function test_trial_token_is_isolated_from_production_records_with_the_same_tracking_code(): void
    {
        $company = Company::create([
            'name' => 'Empresa Aislada',
            'slug' => 'empresa-aislada',
            'status' => 'active',
        ]);

        [, $productionToken] = ApiToken::issue(
            $company,
            'Token Produccion',
            now()->subMinute(),
            now()->addDays(30)
        );

        [$trialTokenModel, $trialToken] = ApiToken::issue(
            $company,
            'Token Trial',
            now()->subMinute(),
            now()->addDays(30),
            ['packages:write', 'packages:read'],
            'trial'
        );

        $payload = [
            'tracking_code' => 'EN000009999BO',
            'recipient_name' => 'Cliente Demo',
            'destination' => 'La Paz',
        ];

        $this->postJson('/api/v1/packages', $payload, [
            'Authorization' => 'Bearer '.$productionToken,
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->postJson('/api/v1/packages', $payload, [
            'Authorization' => 'Bearer '.$trialToken,
            'Accept' => 'application/json',
        ])->assertCreated();

        $productionPackage = Package::query()
            ->where('company_id', $company->id)
            ->where('tracking_code', 'EN000009999BO')
            ->whereNull('created_by_api_token_id')
            ->first();

        $trialPackage = Package::query()
            ->where('company_id', $company->id)
            ->where('tracking_code', 'EN000009999BO')
            ->where('created_by_api_token_id', $trialTokenModel->id)
            ->first();

        $this->assertNotNull($productionPackage);
        $this->assertNotNull($trialPackage);

        $this->getJson('/api/v1/packages/EN000009999BO', [
            'Authorization' => 'Bearer '.$productionToken,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.id', $productionPackage->id);

        $this->getJson('/api/v1/packages/EN000009999BO', [
            'Authorization' => 'Bearer '.$trialToken,
            'Accept' => 'application/json',
        ])->assertOk()
            ->assertJsonPath('data.id', $trialPackage->id);
    }

    public function test_revoking_trial_token_purges_only_its_operational_data(): void
    {
        $company = Company::create([
            'name' => 'Empresa Trial',
            'slug' => 'empresa-trial',
            'status' => 'active',
        ]);

        [$trialTokenModel, $trialToken] = ApiToken::issue(
            $company,
            'Token Trial',
            now()->subMinute(),
            now()->addDays(30),
            ['packages:write', 'packages:read'],
            'trial'
        );

        [, $productionToken] = ApiToken::issue(
            $company,
            'Token Produccion',
            now()->subMinute(),
            now()->addDays(30)
        );

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $trialHeaders = [
            'Authorization' => 'Bearer '.$trialToken,
            'Accept' => 'application/json',
        ];

        $productionHeaders = [
            'Authorization' => 'Bearer '.$productionToken,
            'Accept' => 'application/json',
        ];

        $this->postJson('/api/v1/cn31/manifests', [
            'cn31_number' => 'CN31-TRIAL-001',
            'dispatch_number_manifest' => '0001',
            'origin_office' => 'COCHABAMBA',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => '2026-08-11 10:00:00',
            'bags' => [
                [
                    'bag_number' => 'TRIAL-BAG-001',
                    'package_count' => 1,
                    'total_weight_kg' => 0.250,
                ],
            ],
        ], $trialHeaders)->assertCreated();

        $this->postJson('/api/v1/cn33/bags/TRIAL-BAG-001/packages', [
            'packages' => [
                [
                    'tracking_code' => 'EN000001001BO',
                    'origin' => 'COCHABAMBA',
                    'destination' => 'LA PAZ',
                    'weight_kg' => 0.250,
                ],
            ],
        ], $trialHeaders)->assertCreated();

        $this->postJson('/api/v1/cn22/shipments', [
            'records' => [
                [
                    'tracking_code' => 'EN000001001BO',
                    'origin_office' => 'COCHABAMBA',
                    'destination_office' => 'LA PAZ',
                    'sender_name' => 'Remitente Trial',
                    'sender_country' => 'BOLIVIA',
                    'sender_address' => 'Av. Trial 1',
                    'sender_phone' => '70000001',
                    'recipient_name' => 'Cliente Trial',
                    'recipient_document' => '1234567',
                    'recipient_address' => 'Calle Trial 123',
                    'recipient_address_reference' => 'Puerta azul',
                    'recipient_city' => 'LA PAZ',
                    'recipient_department' => 'LA PAZ',
                    'recipient_phone' => '71111111',
                    'description' => 'Documentos',
                    'gross_weight_grams' => 250,
                    'length_cm' => 20,
                    'width_cm' => 15,
                    'height_cm' => 2,
                    'value_fob_usd' => 18.00,
                ],
            ],
        ], $trialHeaders)->assertCreated();

        $trialBag = Cn31Bag::query()
            ->where('company_id', $company->id)
            ->where('bag_number', 'TRIAL-BAG-001')
            ->firstOrFail();

        $this->postJson('/api/v1/cn33/bags/TRIAL-BAG-001/movements', [
            'ips_operation_code' => 'RECEPTACLE_RECEIVE',
            'location_code' => 'TEST-OFFICE',
            'location' => 'Oficina de prueba',
            'physical_confirmation' => true,
            'occurred_at' => now()->subMinutes(2)->toIso8601String(),
        ], $trialHeaders)->assertCreated();

        $this->postJson('/api/v1/cn33/bags/TRIAL-BAG-001/movements', [
            'ips_operation_code' => 'RECEPTACLE_OPEN',
            'location_code' => 'TEST-OFFICE',
            'location' => 'Oficina de prueba',
            'occurred_at' => now()->subMinute()->toIso8601String(),
        ], $trialHeaders)->assertCreated();

        $this->postJson('/api/v1/packages/EN000001001BO/movements', [
            'status' => 'entregado',
            'location' => 'La Paz',
            'description' => 'Entrega de prueba',
        ], $trialHeaders)->assertCreated();

        $trialPackage = Package::query()
            ->where('company_id', $company->id)
            ->where('tracking_code', 'EN000001001BO')
            ->firstOrFail();

        $this->postJson('/api/v1/packages', [
            'tracking_code' => 'EN000001002BO',
            'recipient_name' => 'Cliente Produccion',
            'destination' => 'La Paz',
        ], $productionHeaders)->assertCreated();

        $this->assertDatabaseHas('cn31_manifests', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'cn31_number' => 'CN31-TRIAL-001',
        ]);

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'tracking_code' => 'EN000001001BO',
        ]);

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'created_by_api_token_id' => null,
            'tracking_code' => 'EN000001002BO',
        ]);

        $this->assertDatabaseHas('package_movements', [
            'company_id' => $company->id,
            'package_id' => $trialPackage->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'status' => 'entregado',
        ]);

        $this->assertDatabaseHas('cn31_bag_movements', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'ips_operation_code' => 'RECEPTACLE_OPEN',
        ]);

        $this->assertDatabaseHas('cn33_packages', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'tracking_code' => 'EN000001001BO',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.tokens.revoke', $trialTokenModel))
            ->assertRedirect();

        $this->assertDatabaseMissing('cn31_manifests', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'cn31_number' => 'CN31-TRIAL-001',
        ]);

        $this->assertDatabaseMissing('packages', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'tracking_code' => 'EN000001001BO',
        ]);

        $this->assertDatabaseMissing('cn31_bags', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'bag_number' => 'TRIAL-BAG-001',
        ]);

        $this->assertDatabaseMissing('cn33_packages', [
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialTokenModel->id,
            'tracking_code' => 'EN000001001BO',
        ]);

        $this->assertDatabaseMissing('package_movements', [
            'company_id' => $company->id,
            'package_id' => $trialPackage->id,
        ]);

        $this->assertDatabaseMissing('cn31_bag_movements', [
            'company_id' => $company->id,
            'cn31_bag_id' => $trialBag->id,
        ]);

        $this->assertDatabaseHas('packages', [
            'company_id' => $company->id,
            'created_by_api_token_id' => null,
            'tracking_code' => 'EN000001002BO',
        ]);

        $this->assertDatabaseHas('package_movements', [
            'company_id' => $company->id,
            'created_by_api_token_id' => null,
            'status' => 'registrado',
        ]);

        $this->getJson('/api/v1/packages/EN000001001BO', $trialHeaders)->assertUnauthorized();

        $this->actingAs($admin)
            ->patch(route('admin.tokens.reactivate', $trialTokenModel))
            ->assertRedirect();

        $this->getJson('/api/v1/cn31/manifests/CN31-TRIAL-001', $trialHeaders)->assertNotFound();
    }

    public function test_deleting_trial_token_purges_its_package_and_bag_event_history(): void
    {
        $company = Company::create([
            'name' => 'Empresa Trial para eliminar',
            'slug' => 'empresa-trial-eliminar',
            'status' => 'active',
        ]);

        [$trialToken] = ApiToken::issue(
            $company,
            'Token Trial para eliminar',
            now()->subMinute(),
            now()->addDays(30),
            ['documents:read', 'tracking:read'],
            'trial'
        );

        $manifest = Cn31Manifest::create([
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialToken->id,
            'cn31_number' => 'CN31-TRIAL-DELETE',
            'origin_office' => 'SHANGHAI',
            'destination_office' => 'LA PAZ',
            'dispatch_date' => now()->subDay(),
            'total_bags' => 1,
            'total_packages' => 1,
            'total_weight_kg' => 0.250,
            'status' => 'conciliado',
        ]);
        $bag = Cn31Bag::create([
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialToken->id,
            'cn31_manifest_id' => $manifest->id,
            'bag_number' => 'TRIAL-DELETE-BAG',
            'declared_package_count' => 1,
            'declared_weight_kg' => 0.250,
            'status' => 'conciliado',
            'operational_status' => 'recibido',
        ]);
        $package = Package::create([
            'company_id' => $company->id,
            'created_by_api_token_id' => $trialToken->id,
            'tracking_code' => 'EN000001099BO',
            'recipient_name' => 'Cliente de prueba',
            'status' => 'entregado',
            'registered_at' => now()->subDay(),
            'last_movement_at' => now(),
        ]);

        Cn31BagMovement::create([
            'company_id' => $company->id,
            'cn31_bag_id' => $bag->id,
            'created_by_api_token_id' => $trialToken->id,
            'ips_operation_code' => 'RECEPTACLE_RECEIVE',
            'source_type' => 'company_api',
            'location_code' => 'TEST-OFFICE',
            'status' => 'recibido',
            'occurred_at' => now()->subHour(),
        ]);
        PackageMovement::create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'created_by_api_token_id' => $trialToken->id,
            'status' => 'entregado',
            'occurred_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.tokens.destroy', $trialToken))
            ->assertRedirect();

        $this->assertDatabaseMissing('api_tokens', ['id' => $trialToken->id]);
        $this->assertDatabaseMissing('cn31_manifests', ['id' => $manifest->id]);
        $this->assertDatabaseMissing('cn31_bags', ['id' => $bag->id]);
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
        $this->assertDatabaseMissing('cn31_bag_movements', ['cn31_bag_id' => $bag->id]);
        $this->assertDatabaseMissing('package_movements', ['package_id' => $package->id]);
    }
}
