<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_product_purchase_limits', function (Blueprint $table) {
            $table->id();
            $table->enum('verification_level', ['contact_verified', 'fully_verified']);
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->decimal('limit_amount', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['verification_level', 'period_type'], 'dppl_ver_level_period_idx');
            $table->index(['verification_level', 'is_active'], 'dppl_ver_level_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_product_purchase_limits');
    }
};
