<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn31_bags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cn31_manifest_id')->constrained('cn31_manifests')->cascadeOnDelete();
            $table->string('bag_number');
            $table->unsignedInteger('declared_package_count')->default(0);
            $table->decimal('declared_weight_kg', 12, 3)->default(0);
            $table->string('status')->default('pendiente_cn33');
            $table->timestamp('received_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'bag_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn31_bags');
    }
};
