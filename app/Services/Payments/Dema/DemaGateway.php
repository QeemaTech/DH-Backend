<?php

namespace App\Services\Payments\Dema;

use App\Models\Invoice;
use App\Models\User;
use App\Services\DemaService;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DemaGateway implements PaymentGateway
{
    public function __construct(
        private DemaService $dema,
    ) {}

    public function provider(): string
    {
        return Invoice::PROVIDER_DEMA;
    }

    public function createPaymentLink(Invoice $invoice): string
    {
        if ($invoice->payment_url) {
            return $invoice->payment_url;
        }

        /** @var User|null $buyer */
        $buyer = $invoice->user()->first();
        if (! $buyer) {
            throw new \Exception('Invoice user is required for Deema.');
        }

        $payload = $this->buildPurchasePayload($invoice);
        $response = $this->dema->createPurchase($payload);

        $orderReference = (string) data_get($response, 'data.order_reference', '');
        $purchaseId = (int) data_get($response, 'data.purchase_id', 0);
        $redirectLink = (string) data_get($response, 'data.redirect_link', '');

        if ($orderReference === '' || $redirectLink === '') {
            $detail = $this->deemaPurchaseFailureDetail($response);
            Log::warning('Deema purchase returned missing order_reference or redirect_link', [
                'detail' => $detail,
                'response' => $response,
            ]);
            throw new \Exception('Deema payment could not be started: '.$detail);
        }

        return DB::transaction(function () use ($invoice, $payload, $response, $redirectLink, $orderReference, $purchaseId) {
            $invoice->update([
                'provider' => Invoice::PROVIDER_DEMA,
                'status' => Invoice::STATUS_PENDING,
                // Deema order_reference is the key to track the checkout
                'provider_invoice_id' => $orderReference,
                // Deema purchase_id is used for refund/cancel
                'provider_key' => $purchaseId > 0 ? (string) $purchaseId : null,
                'payment_url' => $redirectLink,
                'provider_payload' => array_merge((array) ($invoice->provider_payload ?? []), [
                    'dema_request' => $payload,
                ]),
                'provider_response' => [
                    'purchase' => $response,
                ],
            ]);

            return $redirectLink;
        });
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function deemaPurchaseFailureDetail(array $response): string
    {
        $message = (string) data_get($response, 'message', '');
        if ($message !== '') {
            return $message;
        }

        $error = (string) data_get($response, 'error', '');
        if ($error !== '') {
            return $error;
        }

        $code = (string) data_get($response, 'error_code', '');
        $detail = (string) data_get($response, 'error_message', '');

        if ($detail !== '') {
            return $code !== '' ? "{$code}: {$detail}" : $detail;
        }

        return 'Deema did not return a redirect_link.';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPurchasePayload(Invoice $invoice): array
    {
        $currency = strtoupper((string) ($invoice->currency_code ?? 'KWD'));
        if (! in_array($currency, ['KWD', 'BHD'], true)) {
            throw new \Exception('Deema only supports KWD and BHD currency codes.');
        }

        $amount = round((float) ($invoice->total_amount ?? 0), 3);
        if ($amount <= 0) {
            throw new \Exception('Deema payment amount must be positive.');
        }

        $orderId = data_get($invoice->provider_payload, 'order_id');
        $merchantOrderId = $orderId !== null && $orderId !== '' ? (string) $orderId : (string) $invoice->id;

        $successUrl = (string) config('services.dema.merchant_urls.success', url('/dema/success'));
        $failureUrl = (string) config('services.dema.merchant_urls.failure', url('/dema/failure'));

        return [
            'amount' => $amount,
            'currency_code' => $currency,
            'merchant_order_id' => $merchantOrderId,
            'merchant_urls' => [
                'success' => $successUrl,
                'failure' => $failureUrl,
            ],
        ];
    }
}
