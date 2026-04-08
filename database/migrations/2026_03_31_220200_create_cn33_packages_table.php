<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cn33_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cn31_bag_id')->constrained('cn31_bags')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_code');
            $table->string('reference')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('weight_kg', 12, 3)->nullable();
            $table->unsignedInteger('item_order')->nullable();
            $table->string('status')->default('pendiente_cn22');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tracking_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cn33_packages');
    }
};
