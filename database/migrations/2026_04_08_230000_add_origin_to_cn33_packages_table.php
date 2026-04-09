<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cn33_packages', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('recipient_name');
        });
    }

    public function down(): void
    {
        Schema::table('cn33_packages', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
