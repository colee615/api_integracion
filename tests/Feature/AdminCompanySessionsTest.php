<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCompanySessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_revoke_web_and_company_portal_sessions(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => '123456789',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $company = Company::create([
            'name' => 'Empresa Sesiones',
            'slug' => 'empresa-sesiones',
            'status' => 'active',
        ]);

        $companyUser = User::create([
            'name' => 'Empresa Sesiones',
            'email' => 'empresa-sesiones@test.local',
            'password' => '123456789',
            'role' => 'company',
            'status' => 'active',
            'company_id' => $company->id,
        ]);

        DB::table('sessions')->insert([
            'id' => 'session-company-1',
            'user_id' => $companyUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode(serialize(['_token' => 'fake'])),
            'last_activity' => now()->timestamp,
        ]);

        DB::table('cache')->insert([
            'key' => config('cache.prefix').'company_portal_session:test-token',
            'value' => serialize(['user_id' => $companyUser->id]),
            'expiration' => now()->addHour()->timestamp,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.companies.sessions.revoke', $company))
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', [
            'id' => 'session-company-1',
        ]);

        $this->assertDatabaseMissing('cache', [
            'key' => config('cache.prefix').'company_portal_session:test-token',
        ]);
    }
}
