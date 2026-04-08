<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn31_manifests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('cn31_number');
            $table->string('origin_office');
            $table->string('destination_office');
            $table->timestamp('dispatch_date')->nullable();
            $table->unsignedInteger('total_bags')->default(0);
            $table->unsignedInteger('total_packages')->default(0);
            $table->decimal('total_weight_kg', 12, 3)->default(0);
            $table->string('status')->default('recibido_api');
            $table->timestamp('received_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'cn31_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn31_manifests');
    }
};
