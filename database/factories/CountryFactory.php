<?php

namespace Database\Factories;

use App\Enums\VerificationChannel;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->countryCode();

        return [
            'code' => strtoupper(substr($code, 0, 2)),
            'name' => [
                'en' => fake()->country(),
                'ar' => fake()->country(),
            ],
            'dial_code' => '+'.fake()->numerify('##'),
            'verification_channel' => VerificationChannel::Sms,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
