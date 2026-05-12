<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();

            // Money
            $table->string('currency_code', 3)->default('KWD');
            $table->decimal('subtotal', 12, 3)->default(0);
            $table->decimal('shipping_cost', 12, 3)->default(0);
            $table->decimal('coupon_amount', 12, 3)->nullable()->default(0);
            $table->decimal('wallet_amount', 12, 3)->nullable()->default(0);
            $table->decimal('total_amount', 12, 3)->default(0);

            // Payment / Gateway
            $table->string('provider')->default('sadad'); // sadad, etc.
            $table->string('status')->default('pending'); // pending/paid/failed/cancelled/refunded
            $table->string('provider_invoice_id')->nullable(); // e.g. Sadad invoiceId
            $table->string('provider_key')->nullable(); // e.g. Sadad key used in pay url
            $table->text('payment_url')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->date('expires_at')->nullable();

            // Store provider payloads for auditing/debugging
            $table->json('provider_payload')->nullable();
            $table->json('provider_response')->nullable();

            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index(['user_id', 'status']);
            $table->unique(['provider', 'provider_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
