<?php

namespace App\Services;

use App\Exceptions\DigitalProductPurchaseLimitException;
use App\Models\DigitalOrder;
use App\Models\DigitalProductPurchaseLimit;
use App\Models\User;
use Carbon\Carbon;

class DigitalProductPurchaseLimitService
{
    /**
     * @return array{allowed: bool, verification_level: string|null, message: string|null, data: array<string, mixed>|null}
     */
    public function checkPurchaseLimit(User $user, float $newOrderAmount): array
    {
        $verificationLevel = $this->getUserVerificationLevel($user);

        if ($verificationLevel === null) {
            return [
                'allowed' => false,
                'verification_level' => null,
                'message' => 'You must verify your email or phone number before purchasing digital products.',
                'data' => null,
            ];
        }

        $activeLimits = DigitalProductPurchaseLimit::query()
            ->where('verification_level', $verificationLevel)
            ->where('is_active', true)
            ->get();

        foreach ($activeLimits as $limit) {
            $periodStart = $this->getPeriodStartDate($limit->period_type);

            $currentUsedAmount = (float) DigitalOrder::query()
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $periodStart)
                ->where(function ($query) {
                    $query->whereIn('payment_status', ['pending', 'paid'])
                        ->orWhereIn('status', ['pending', 'completed']);
                })
                ->sum('total_cost');

            $requestedAmount = (float) $newOrderAmount;
            $limitAmount = (float) $limit->limit_amount;
            $totalAfterRequest = $currentUsedAmount + $requestedAmount;
            $remainingAmount = max(0.0, $limitAmount - $currentUsedAmount);

            if ($totalAfterRequest > $limitAmount) {
                return [
                    'allowed' => false,
                    'verification_level' => $verificationLevel,
                    'message' => sprintf('You have exceeded your %s digital product purchase limit.', $limit->period_type),
                    'data' => [
                        'period' => $limit->period_type,
                        'limit_amount' => $limitAmount,
                        'current_used_amount' => $currentUsedAmount,
                        'requested_amount' => $requestedAmount,
                        'remaining_amount' => $remainingAmount,
                        'verification_level' => $verificationLevel,
                    ],
                ];
            }
        }

        return [
            'allowed' => true,
            'verification_level' => $verificationLevel,
            'message' => null,
            'data' => null,
        ];
    }

    public function assertPurchaseLimit(User $user, float $newOrderAmount): void
    {
        $result = $this->checkPurchaseLimit($user, $newOrderAmount);

        if (! $result['allowed']) {
            throw new DigitalProductPurchaseLimitException(
                message: $result['message'] ?? 'Digital product purchase limit validation failed.',
                responseData: $result['data'] ?? [],
                statusCode: 422,
            );
        }
    }

    public function getUserVerificationLevel(User $user): ?string
    {
        $isEmailVerified = $user->email_verified_at !== null;
        $isPhoneVerified = $user->phone_verified_at !== null;

        if (! $isEmailVerified && ! $isPhoneVerified) {
            return null;
        }

        if ($isEmailVerified && $isPhoneVerified) {
            return DigitalProductPurchaseLimit::VERIFICATION_FULLY;
        }

        return DigitalProductPurchaseLimit::VERIFICATION_CONTACT;
    }

    public function getPeriodStartDate(string $periodType): Carbon
    {
        return match ($periodType) {
            DigitalProductPurchaseLimit::PERIOD_DAILY => now()->subHours(24),
            DigitalProductPurchaseLimit::PERIOD_WEEKLY => now()->subDays(7),
            DigitalProductPurchaseLimit::PERIOD_MONTHLY => now()->subDays(30),
            default => now(),
        };
    }
}
