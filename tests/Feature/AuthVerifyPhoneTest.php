<?php

namespace Tests\Feature;

use App\Enums\VerificationChannel;
use App\Models\Country;
use App\Models\User;
use App\Models\Verification;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthVerifyPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_phone_with_valid_code_and_get_token(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        $country = Country::query()->create([
            'code' => 'EG',
            'name' => ['en' => 'Egypt', 'ar' => 'مصر'],
            'dial_code' => '+20',
            'verification_channel' => VerificationChannel::Sms,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->mock(SmsService::class, function ($mock): void {
            $mock->shouldReceive('send')->once()->andReturn([
                'success' => true,
                'status' => '00',
                'response' => 'OK',
            ]);
        });

        $register = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'country_id' => $country->id,
            'email' => null,
            'phone' => '201022994534',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $register->assertCreated();

        $user = User::where('phone', '201022994534')->firstOrFail();
        $this->assertFalse((bool) $user->is_verified);
        $this->assertNull($user->phone_verified_at);

        $verification = Verification::where('user_id', $user->id)->where('type', 'phone')->latest()->firstOrFail();

        $verify = $this->postJson('/api/auth/verify-phone', [
            'phone' => '201022994534',
            'code' => $verification->code,
        ]);

        $verify->assertOk();
        $verify->assertJsonPath('success', true);
        $verify->assertJsonPath('data.token_type', 'Bearer');
        $this->assertNotEmpty($verify->json('data.token'));

        $user->refresh();
        $this->assertTrue((bool) $user->is_verified);
        $this->assertNotNull($user->phone_verified_at);

        $verification->refresh();
        $this->assertNotNull($verification->verified_at);
    }

    public function test_verify_phone_rejects_expired_code(): void
    {
        $user = User::factory()->create([
            'phone' => '201000000000',
            'is_verified' => false,
            'phone_verified_at' => null,
        ]);

        $verification = Verification::create([
            'user_id' => $user->id,
            'type' => 'phone',
            'target' => $user->phone,
            'code' => '123456',
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $resp = $this->postJson('/api/auth/verify-phone', [
            'phone' => $user->phone,
            'code' => $verification->code,
        ]);

        $resp->assertStatus(400);
        $resp->assertJsonPath('success', false);
    }
}
