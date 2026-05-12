<?php

namespace App\Services;

use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Carbon;

class PhoneVerificationService
{
    public function __construct(
        private SmsService $sms,
        private WhatsAppService $whatsapp
    ) {}

    public function send(User $user, ?int $code = null): void
    {
        if (! $user->phone) {
            throw new \InvalidArgumentException('User phone is missing.');
        }

        $this->deliver($user, $code, function (string $phone, string $message): void {
            $this->sms->send($phone, $message);
        });
    }

    public function sendViaWhatsApp(User $user, ?int $code = null): void
    {
        if (! $user->phone) {
            throw new \InvalidArgumentException('User phone is missing.');
        }

        $this->deliver($user, $code, function (string $phone, string $message): void {
            $this->whatsapp->send($phone, $message);
        });
    }

    /**
     * @param  callable(string,string):void  $sender
     */
    private function deliver(User $user, ?int $code, callable $sender): void
    {
        $code = $code ?? random_int(100000, 999999);

        Verification::query()
            ->where('user_id', $user->id)
            ->where('type', 'phone')
            ->where('target', $user->phone)
            ->whereNull('verified_at')
            ->delete();

        Verification::create([
            'user_id' => $user->id,
            'type' => 'phone',
            'target' => $user->phone,
            'code' => (string) $code,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $sender($user->phone, __('Your verification code is: :code', ['code' => $code]));
    }
}
