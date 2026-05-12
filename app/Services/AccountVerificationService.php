<?php

namespace App\Services;

use App\Enums\VerificationChannel;
use App\Models\User;

class AccountVerificationService
{
    public function __construct(private PhoneVerificationService $phoneVerification) {}

    /**
     * Send signup verification using the channel configured for the user's country.
     */
    public function sendForNewUser(User $user): void
    {
        $channel = $user->country?->verification_channel ?? VerificationChannel::Sms;

        match ($channel) {
            VerificationChannel::Email => $this->sendEmail($user),
            VerificationChannel::Whatsapp => $this->phoneVerification->sendViaWhatsApp($user),
            VerificationChannel::Sms => $this->phoneVerification->send($user),
        };
    }

    /**
     * Resend verification for an existing unverified user.
     */
    public function resend(User $user): void
    {
        $this->sendForNewUser($user);
    }

    private function sendEmail(User $user): void
    {
        if (! $user->email) {
            throw new \InvalidArgumentException(__('Email is required for verification in this country.'));
        }

        $user->sendEmailVerificationNotification();
    }
}
