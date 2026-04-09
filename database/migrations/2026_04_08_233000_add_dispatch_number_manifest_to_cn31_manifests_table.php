<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cn31_manifests', function (Blueprint $table): void {
            $table->string('dispatch_number_manifest')->nullable()->after('cn31_number');
        });
    }

    public function down(): void
    {
        Schema::table('cn31_manifests', function (Blueprint $table): void {
            $table->dropColumn('dispatch_number_manifest');
        });
    }
};
