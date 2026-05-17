<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->json('verification_channels')->nullable()->after('verification_channel');
            $table->string('flag')->nullable()->after('dial_code');
        });

        DB::table('countries')
            ->select(['id', 'verification_channel'])
            ->orderBy('id')
            ->chunkById(100, function ($countries): void {
                foreach ($countries as $country) {
                    $legacyChannel = (string) ($country->verification_channel ?? 'sms');
                    $mapped = in_array($legacyChannel, ['sms', 'whatsapp', 'email'], true)
                        ? [$legacyChannel]
                        : ['sms'];

                    DB::table('countries')
                        ->where('id', $country->id)
                        ->update(['verification_channels' => json_encode($mapped)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['verification_channels', 'flag']);
        });
    }
};
