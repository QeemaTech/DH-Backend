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
        Schema::create('country_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['country_id', 'product_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique(['country_id', 'phone']);
            $table->string('gender')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('national_number')->nullable()->after('birth_date');
            $table->string('national_cart_front_image')->nullable()->after('national_number');
            $table->string('national_cart_back_image')->nullable()->after('national_cart_front_image');
            $table->date('national_id_expire_date')->nullable()->after('national_cart_back_image');
            $table->text('home_address')->nullable()->after('national_id_expire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropUnique(['country_id', 'phone']);
            $table->dropColumn([
                'country_id',
                'gender',
                'birth_date',
                'national_number',
                'national_cart_front_image',
                'national_cart_back_image',
                'national_id_expire_date',
                'home_address',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });

        Schema::dropIfExists('country_product');
    }
};
