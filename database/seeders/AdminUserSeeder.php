<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'eynar345@gmail.com'],
            [
                'name' => 'Eynar',
                'role' => 'admin',
                'status' => 'active',
                'password' => '123456789',
            ]
        );
    }
}
