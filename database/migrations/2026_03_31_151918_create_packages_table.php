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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_code');
            $table->string('reference')->nullable();
            $table->string('recipient_name');
            $table->string('recipient_document')->nullable();
            $table->string('destination')->nullable();
            $table->string('status')->default('registrado');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tracking_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
