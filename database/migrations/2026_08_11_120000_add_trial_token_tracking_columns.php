<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_tokens', function (Blueprint $table) {
            $table->string('token_type')->default('production')->after('name');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('created_by_api_token_id')
                ->nullable()
                ->after('company_id')
                ->constrained('api_tokens')
                ->nullOnDelete();
            $table->dropUnique(['company_id', 'tracking_code']);
            $table->unique(['company_id', 'tracking_code', 'created_by_api_token_id'], 'packages_company_tracking_token_unique');
        });

        Schema::table('package_movements', function (Blueprint $table) {
            $table->foreignId('created_by_api_token_id')
                ->nullable()
                ->after('company_id')
                ->constrained('api_tokens')
                ->nullOnDelete();
        });

        Schema::table('cn31_manifests', function (Blueprint $table) {
            $table->foreignId('created_by_api_token_id')
                ->nullable()
                ->after('company_id')
                ->constrained('api_tokens')
                ->nullOnDelete();
            $table->dropUnique(['company_id', 'cn31_number']);
            $table->unique(['company_id', 'cn31_number', 'created_by_api_token_id'], 'cn31_manifests_company_number_token_unique');
        });

        Schema::table('cn31_bags', function (Blueprint $table) {
            $table->foreignId('created_by_api_token_id')
                ->nullable()
                ->after('company_id')
                ->constrained('api_tokens')
                ->nullOnDelete();
            $table->dropUnique(['company_id', 'bag_number']);
            $table->unique(['company_id', 'bag_number', 'created_by_api_token_id'], 'cn31_bags_company_number_token_unique');
        });

        Schema::table('cn33_packages', function (Blueprint $table) {
            $table->foreignId('created_by_api_token_id')
                ->nullable()
                ->after('company_id')
                ->constrained('api_tokens')
                ->nullOnDelete();
            $table->dropUnique(['company_id', 'tracking_code']);
            $table->unique(['company_id', 'tracking_code', 'created_by_api_token_id'], 'cn33_packages_company_tracking_token_unique');
        });

        Schema::table('webhook_endpoints', function (Blueprint $table) {
            $table->foreignId('created_by_api_token_id')
                ->nullable()
                ->after('company_id')
                ->constrained('api_tokens')
                ->nullOnDelete();
        });

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->foreignId('created_by_api_token_id')
                ->nullable()
                ->after('company_id')
                ->constrained('api_tokens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_api_token_id');
        });

        Schema::table('webhook_endpoints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_api_token_id');
        });

        Schema::table('cn33_packages', function (Blueprint $table) {
            $table->dropUnique('cn33_packages_company_tracking_token_unique');
            $table->unique(['company_id', 'tracking_code']);
            $table->dropConstrainedForeignId('created_by_api_token_id');
        });

        Schema::table('cn31_bags', function (Blueprint $table) {
            $table->dropUnique('cn31_bags_company_number_token_unique');
            $table->unique(['company_id', 'bag_number']);
            $table->dropConstrainedForeignId('created_by_api_token_id');
        });

        Schema::table('cn31_manifests', function (Blueprint $table) {
            $table->dropUnique('cn31_manifests_company_number_token_unique');
            $table->unique(['company_id', 'cn31_number']);
            $table->dropConstrainedForeignId('created_by_api_token_id');
        });

        Schema::table('package_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_api_token_id');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique('packages_company_tracking_token_unique');
            $table->unique(['company_id', 'tracking_code']);
            $table->dropConstrainedForeignId('created_by_api_token_id');
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropColumn('token_type');
        });
    }
};
