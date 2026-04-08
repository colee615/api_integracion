<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_user_cannot_log_in_to_internal_laravel_panel(): void
    {
        $company = Company::create([
            'name' => 'Empresa Portal',
            'slug' => 'empresa-portal',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Empresa Portal',
            'email' => 'portal@empresa.test',
            'password' => '123456789',
            'role' => 'company',
            'company_id' => $company->id,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'portal@empresa.test',
            'password' => '123456789',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }
}
