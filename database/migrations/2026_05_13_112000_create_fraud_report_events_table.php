<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_report_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fraud_report_id')->constrained('fraud_reports')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->string('actor_type', 32)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['fraud_report_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_report_events');
    }
};
