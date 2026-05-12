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
        Schema::create('digital_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->comment('The product ID from the provider API');
            $table->string('company_name')->comment('The company that provides the digital product');
            $table->foreignId('merchant_id')->constrained('digital_merchants')->nullable()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('digital_categories')->nullable()->onDelete('cascade');
            $table->foreignId('sub_category_id')->constrained('digital_sub_categories')->nullable()->onDelete('cascade');
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->json('how_to_use')->nullable();
            $table->string('image')->nullable();
            $table->decimal('cost_after_vat', 10, 2);
            $table->decimal('price', 10, 2);
            $table->string('currency')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->integer('visits')->default(0);
            $table->boolean('optional_fields_exists')->default(false);
            $table->foreignId('last_update_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['product_id', 'company_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_products');
    }
};
