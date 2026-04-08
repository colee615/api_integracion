<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('api_key', 64)->nullable()->unique()->after('slug');
            $table->string('environment')->default('production')->after('status');
            $table->timestamp('sandbox_starts_at')->nullable()->after('environment');
            $table->timestamp('sandbox_ends_at')->nullable()->after('sandbox_starts_at');
            $table->unsignedInteger('sandbox_max_shipments')->default(100)->after('sandbox_ends_at');
            $table->unsignedInteger('sandbox_shipments_used')->default(0)->after('sandbox_max_shipments');
            $table->timestamp('production_enabled_at')->nullable()->after('sandbox_shipments_used');
            $table->json('integration_settings')->nullable()->after('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'api_key',
                'environment',
                'sandbox_starts_at',
                'sandbox_ends_at',
                'sandbox_max_shipments',
                'sandbox_shipments_used',
                'production_enabled_at',
                'integration_settings',
            ]);
        });
    }
};
