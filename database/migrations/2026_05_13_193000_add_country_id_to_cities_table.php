<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('state_id')->constrained()->cascadeOnDelete();
            $table->index(['country_id', 'state_id', 'is_active'], 'cities_country_state_active_index');
        });

        DB::statement('
            UPDATE cities c
            INNER JOIN states s ON s.id = c.state_id
            SET c.country_id = s.country_id
            WHERE c.country_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex('cities_country_state_active_index');
            $table->dropConstrainedForeignId('country_id');
        });
    }
};

