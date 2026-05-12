<?php

namespace App\Services\Payments\Dema;

use App\Models\Invoice;
use App\Models\User;

final class DemaChargePayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Invoice $invoice, User $buyer): array
    {
        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        if (! $buyer->email) {
            throw new \Exception('Buyer email is required for Deema (Tap) payments.');
        }

        if (! $buyer->phone) {
            throw new \Exception('Buyer phone is required for Deema (Tap) payments.');
        }

        $currency = strtoupper((string) ($invoice->currency_code ?? 'KWD'));
        if ($currency !== 'KWD') {
            throw new \Exception('Deema (Tap) only supports KWD currency.');
        }

        $totalAmount = $this->resolveInvoiceTotalAmount($invoice);
        if ($totalAmount <= 0) {
            throw new \Exception('Deema payment amount must be positive.');
        }

        $amount = $this->formatAmountAsFloat($totalAmount);
        $this->assertSandboxAmountRangeIfNeeded($amount);

        $phone = $this->splitTapPhone((string) $buyer->phone);
        [$firstName, $lastName] = $this->splitName((string) ($buyer->name ?? 'Customer'));

        $sourceId = (string) config('services.dema.source_id', 'src_deema');
        $redirectUrl = (string) config('services.dema.redirect_urls.success', url('/dema/success'));
        $webhookUrl = (string) config('services.dema.webhook_url', url('/dema/webhook'));

        $orderId = data_get($invoice->provider_payload, 'order_id');

        return [
            'amount' => $amount,
            'currency' => 'KWD',
            'customer' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => (string) $buyer->email,
                'phone' => [
                    'country_code' => $phone['country_code'],
                    'number' => $phone['number'],
                ],
            ],
            'source' => [
                'id' => $sourceId,
            ],
            'description' => 'Invoice #'.$invoice->id,
            'redirect' => [
                'url' => $redirectUrl,
            ],
            'post' => [
                'url' => $webhookUrl,
            ],
            'metadata' => [
                'invoice_id' => (string) $invoice->id,
                'order_id' => $orderId !== null && $orderId !== '' ? (string) $orderId : '',
            ],
        ];
    }

    /**
     * @return array{country_code: string, number: string}
     */
    private function splitTapPhone(string $phone): array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            throw new \Exception('Buyer phone must contain digits for Deema (Tap).');
        }

        if (str_starts_with($digits, '965') && strlen($digits) >= 11) {
            return [
                'country_code' => '965',
                'number' => substr($digits, 3),
            ];
        }

        if (str_starts_with($digits, '966') && strlen($digits) >= 12) {
            return [
                'country_code' => '966',
                'number' => substr($digits, 3),
            ];
        }

        if (strlen($digits) === 8) {
            return [
                'country_code' => '965',
                'number' => $digits,
            ];
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 9) {
            return [
                'country_code' => '965',
                'number' => substr($digits, 1),
            ];
        }

        throw new \Exception('Unsupported phone format for Deema (Tap). Use +965XXXXXXXX or 8-digit Kuwait local.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['Customer', '-'];
        }

        $parts = preg_split('/\s+/', $name, 2) ?: [];

        $first = (string) ($parts[0] ?? 'Customer');
        $last = (string) ($parts[1] ?? '-');

        return [$first, $last];
    }

    private function resolveInvoiceTotalAmount(Invoice $invoice): float
    {
        $total = (float) ($invoice->total_amount ?? 0);
        if ($total > 0) {
            return $total;
        }

        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        $itemsTotal = (float) $invoice->items->sum(fn ($i) => ((float) $i->price) * ((int) $i->quantity));
        $shipping = (float) ($invoice->shipping_cost ?? 0);
        $coupon = (float) ($invoice->coupon_amount ?? 0);
        $wallet = (float) ($invoice->wallet_amount ?? 0);

        return $itemsTotal + $shipping - $coupon - $wallet;
    }

    private function formatAmountAsFloat(float $value): float
    {
        return round($value, 3);
    }

    private function assertSandboxAmountRangeIfNeeded(float $amount): void
    {
        if (! filter_var(config('services.dema.enforce_sandbox_amount_range', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $secret = (string) config('services.dema.merchant_secret_key', '');
        if ($secret === '' || ! str_contains(strtolower($secret), 'sk_test')) {
            return;
        }

        if ($amount < 100.0 || $amount > 200.0) {
            throw new \Exception(
                sprintf(
                    'Deema (Tap) sandbox guard: order total is %.3f KWD but DEMA_ENFORCE_SANDBOX_AMOUNT_RANGE is enabled, which only allows 100–200 KWD with sk_test keys. Set DEMA_ENFORCE_SANDBOX_AMOUNT_RANGE=false in .env for real cart totals, or use a test order between 100 and 200 KWD.',
                    $amount
                )
            );
        }
    }
}
