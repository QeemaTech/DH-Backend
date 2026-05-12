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
        Schema::create('digital_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name');
            $table->string('user_email');
            $table->string('user_phone');
            $table->string('user_gender');
            $table->date('user_birth_date');
            $table->string('user_national_number');
            $table->string('user_national_cart_front_image');
            $table->string('user_national_cart_back_image');
            $table->date('user_national_id_expire_date');
            $table->text('user_home_address');
            $table->string('user_ip_address');
            $table->string('user_country');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded']);
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']);
            $table->text('notes');
            $table->decimal('total', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('shipping_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_orders');
    }
};
