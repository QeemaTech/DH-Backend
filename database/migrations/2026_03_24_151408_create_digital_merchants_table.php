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
        Schema::create('digital_merchants', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id')->comment('The merchant ID from the provider API');
            $table->string('company_name')->comment('The company that provides the digital merchant');
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('redeem_steps')->nullable();
            $table->json('terms')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('digital_merchants')->onDelete('cascade');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique(['merchant_id', 'company_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_merchants');
    }
};
