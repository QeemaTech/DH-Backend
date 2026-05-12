<?php

namespace App\Services\Payments\Tabby;

use App\Models\Invoice;
use App\Models\User;

final class TabbyCheckoutPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Invoice $invoice, User $buyer, string $referenceId): array
    {
        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        $merchantCode = (string) config('services.tabby.merchant_code');
        if ($merchantCode === '') {
            throw new \Exception('Tabby merchant code is missing (TABBY_MERCHANT_CODE).');
        }

        if (! $buyer->email) {
            throw new \Exception('Buyer email is required for Tabby.');
        }

        if (! $buyer->phone) {
            throw new \Exception('Buyer phone is required for Tabby.');
        }

        $currency = (string) ($invoice->currency_code ?? 'KWD');
        $totalAmount = $this->resolveInvoiceTotalAmount($invoice);
        if ($totalAmount <= 0) {
            throw new \Exception('Tabby payment amount must be positive.');
        }

        $amount = $this->formatAmount($totalAmount, $currency);

        $items = $invoice->items->map(function ($item) {
            return [
                'reference_id' => (string) ($item->id ?? $item->name),
                'title' => (string) $item->name,
                'quantity' => (int) $item->quantity,
                'unit_price' => $this->formatAmount((float) $item->price, (string) ($invoice->currency_code ?? 'KWD')),
                'category' => 'Digital',
            ];
        })->values()->all();

        $lang = (string) config('services.tabby.lang', app()->getLocale());
        $successUrl = (string) config('services.tabby.merchant_urls.success', url('/tabby/success'));
        $cancelUrl = (string) config('services.tabby.merchant_urls.cancel', url('/tabby/cancel'));
        $failureUrl = (string) config('services.tabby.merchant_urls.failure', url('/tabby/failure'));

        return [
            'payment' => [
                'amount' => $amount,
                'currency' => $currency,
                'description' => 'Order #'.$referenceId,
                'buyer' => [
                    'name' => (string) ($buyer->name ?? 'Customer'),
                    'email' => (string) $buyer->email,
                    'phone' => (string) $buyer->phone,
                ],
                'order' => [
                    'reference_id' => (string) $referenceId,
                    'items' => $items,
                ],
            ],
            'lang' => $lang,
            'merchant_code' => $merchantCode,
            'merchant_urls' => [
                'success' => $successUrl,
                'cancel' => $cancelUrl,
                'failure' => $failureUrl,
            ],
        ];
    }

    private function formatAmount(float $value, string $currency): string
    {
        $precision = strtoupper($currency) === 'KWD' ? 3 : 2;

        return number_format($value, $precision, '.', '');
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
}
