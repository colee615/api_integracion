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
        Schema::table('packages', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->after('reference');
            $table->string('sender_country')->nullable()->after('sender_name');
            $table->string('sender_address')->nullable()->after('sender_country');
            $table->string('sender_phone')->nullable()->after('sender_address');
            $table->string('recipient_phone')->nullable()->after('recipient_document');
            $table->string('recipient_whatsapp')->nullable()->after('recipient_phone');
            $table->string('recipient_city')->nullable()->after('recipient_whatsapp');
            $table->string('recipient_department')->nullable()->after('recipient_city');
            $table->string('recipient_address_reference')->nullable()->after('recipient_department');
            $table->string('origin_office')->nullable()->after('destination');
            $table->string('destination_office')->nullable()->after('origin_office');
            $table->text('recipient_address')->nullable()->after('destination_office');
            $table->text('shipment_description')->nullable()->after('recipient_address');
            $table->timestamp('shipment_date')->nullable()->after('shipment_description');
            $table->unsignedInteger('gross_weight_grams')->nullable()->after('shipment_date');
            $table->decimal('weight_kg', 10, 3)->nullable()->after('gross_weight_grams');
            $table->decimal('length_cm', 10, 2)->nullable()->after('weight_kg');
            $table->decimal('width_cm', 10, 2)->nullable()->after('length_cm');
            $table->decimal('height_cm', 10, 2)->nullable()->after('width_cm');
            $table->decimal('value_fob_usd', 12, 2)->nullable()->after('height_cm');
            $table->string('currency_code', 3)->default('USD')->after('value_fob_usd');
            $table->timestamp('pre_alert_at')->nullable()->after('currency_code');
            $table->string('tracking_standard')->nullable()->after('pre_alert_at');
            $table->json('customs_items')->nullable()->after('tracking_standard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name',
                'sender_country',
                'sender_address',
                'sender_phone',
                'recipient_phone',
                'recipient_whatsapp',
                'recipient_city',
                'recipient_department',
                'recipient_address_reference',
                'origin_office',
                'destination_office',
                'recipient_address',
                'shipment_description',
                'shipment_date',
                'gross_weight_grams',
                'weight_kg',
                'length_cm',
                'width_cm',
                'height_cm',
                'value_fob_usd',
                'currency_code',
                'pre_alert_at',
                'tracking_standard',
                'customs_items',
            ]);
        });
    }
};
