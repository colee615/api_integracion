<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
        });

        DB::table('users')
            ->where('role', 'company')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function (object $user): void {
                $base = Str::lower((string) Str::before((string) $user->email, '@'));
                $base = preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'empresa';
                $username = $base;
                $suffix = 1;

                while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $suffix++;
                    $username = $base.'-'.$suffix;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'username' => $username,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
