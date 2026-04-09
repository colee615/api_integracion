<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cn31_bags', function (Blueprint $table) {
            $table->string('dispatch_number_bag', 30)->nullable()->after('bag_number');
            $table->unique(['company_id', 'dispatch_number_bag'], 'cn31_bags_company_dispatch_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cn31_bags', function (Blueprint $table) {
            $table->dropUnique('cn31_bags_company_dispatch_number_unique');
            $table->dropColumn('dispatch_number_bag');
        });
    }
};
