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
        Schema::table('digital_order_items', function (Blueprint $table) {
            $table->string('provider_reference')->nullable()->after('notes');
            $table->json('provider_response')->nullable()->after('provider_reference');
            $table->json('delivered_data')->nullable()->after('provider_response');
            $table->timestamp('delivered_at')->nullable()->after('delivered_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_order_items', function (Blueprint $table) {
            $table->dropColumn(['provider_reference', 'provider_response', 'delivered_data', 'delivered_at']);
        });
    }
};
