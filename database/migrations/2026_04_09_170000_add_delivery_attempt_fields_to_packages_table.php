<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedSmallInteger('delivery_attempts')->default(0)->after('last_movement_at');
            $table->timestamp('last_delivery_attempt_at')->nullable()->after('delivery_attempts');
        });

        DB::table('packages')->update([
            'delivery_attempts' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['delivery_attempts', 'last_delivery_attempt_at']);
        });
    }
};
