<?php

namespace App\Services;

use App\Enums\VerificationChannel;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AccountVerificationService
{
    public function __construct(private PhoneVerificationService $phoneVerification) {}

    /**
     * Send signup verification using all configured channels for the user's country.
     *
     * @return array{
     *     sent_channels: array<int, string>,
     *     failed_channels: array<int, array{channel: string, reason: string}>,
     *     sent_details: array<int, array{channel: string, target: string, verification_url?: string}>
     * }
     */
    public function sendForNewUser(User $user): array
    {
        $channels = $user->country?->getVerificationChannels() ?? [];
        if ($channels === []) {
            throw new \InvalidArgumentException(__('No verification channels are configured for this country.'));
        }

        $sentChannels = [];
        $failedChannels = [];
        $sentDetails = [];

        // Keep one OTP code across phone channels to avoid invalidating previous sends.
        $phoneCode = random_int(100000, 999999);

        foreach ($channels as $channelValue) {
            $channel = VerificationChannel::tryFrom((string) $channelValue);
            if (! $channel) {
                continue;
            }

            try {
                match ($channel) {
                    VerificationChannel::Email => $this->sendEmail($user),
                    VerificationChannel::Whatsapp => $this->phoneVerification->sendViaWhatsApp($user, $phoneCode),
                    VerificationChannel::Sms => $this->phoneVerification->send($user, $phoneCode),
                };
                $sentChannels[] = $channel->value;
                $detail = [
                    'channel' => $channel->value,
                    'target' => $channel === VerificationChannel::Email
                        ? (string) ($user->email ?? '')
                        : (string) ($user->phone ?? ''),
                ];

                if ($channel === VerificationChannel::Email && config('app.debug')) {
                    $detail['verification_url'] = URL::temporarySignedRoute(
                        'api.auth.verify-email-link',
                        now()->addMinutes(60),
                        [
                            'id' => $user->getKey(),
                            'hash' => sha1($user->getEmailForVerification()),
                        ]
                    );
                }

                $sentDetails[] = $detail;
            } catch (\Throwable $e) {
                $failedChannels[] = [
                    'channel' => $channel->value,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        if ($sentChannels === []) {
            throw new \InvalidArgumentException(__('Verification could not be sent using configured channels.'));
        }

        return [
            'sent_channels' => array_values(array_unique($sentChannels)),
            'failed_channels' => $failedChannels,
            'sent_details' => $sentDetails,
        ];
    }

    /**
     * Resend verification for an existing unverified user.
     *
     * @return array{sent_channels: array<int, string>, failed_channels: array<int, array{channel: string, reason: string}>}
     */
    public function resend(User $user): array
    {
        return $this->sendForNewUser($user);
    }

    private function sendEmail(User $user): void
    {
        if (! $user->email) {
            throw new \InvalidArgumentException(__('Email is required for verification in this country.'));
        }

        $user->sendEmailVerificationNotification();

        Log::info('Verification email notification dispatched', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
