<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCompanySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['slug' => 'empresa-demo'],
            [
                'name' => '360 Lions',
                'api_key' => Str::upper(Str::random(40)),
                'contact_name' => '360 Lions',
                'contact_email' => 'pruebalions@gmail.com',
                'contact_phone' => '70000000',
                'status' => 'active',
                'locale' => 'es',
            ]
        );

        User::query()
            ->where('role', 'company')
            ->where('company_id', $company->id)
            ->update([
                'email' => 'pruebalions@gmail.com',
            ]);

        User::updateOrCreate(
            [
                'email' => 'pruebalions@gmail.com',
            ],
            [
                'name' => $company->name,
                'role' => 'company',
                'status' => 'active',
                'company_id' => $company->id,
                'password' => '123456789',
            ]
        );
    }
}
