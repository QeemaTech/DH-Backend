<?php

namespace Database\Seeders;

use App\Enums\VerificationChannel;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            [
                'code' => 'KW',
                'name' => ['en' => 'Kuwait', 'ar' => 'الكويت'],
                'dial_code' => '+965',
                'verification_channel' => VerificationChannel::Sms,
                'sort_order' => 1,
            ],
            [
                'code' => 'AE',
                'name' => ['en' => 'United Arab Emirates', 'ar' => 'الإمارات العربية المتحدة'],
                'dial_code' => '+971',
                'verification_channel' => VerificationChannel::Sms,
                'sort_order' => 2,
            ],
            [
                'code' => 'EG',
                'name' => ['en' => 'Egypt', 'ar' => 'مصر'],
                'dial_code' => '+20',
                'verification_channel' => VerificationChannel::Whatsapp,
                'sort_order' => 3,
            ],
        ];

        foreach ($rows as $row) {
            Country::query()->updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
