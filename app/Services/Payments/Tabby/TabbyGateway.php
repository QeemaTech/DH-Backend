<?php

namespace App\Services\Payments\Tabby;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\TabbyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TabbyGateway implements PaymentGateway
{
    public function __construct(
        private TabbyService $tabby,
        private TabbyCheckoutPayloadBuilder $payloadBuilder,
    ) {}

    public function provider(): string
    {
        return Invoice::PROVIDER_TABBY;
    }

    public function createPaymentLink(Invoice $invoice): string
    {
        if ($invoice->payment_url) {
            return $invoice->payment_url;
        }

        /** @var User|null $buyer */
        $buyer = $invoice->user()->first();
        if (! $buyer) {
            throw new \Exception('Invoice user is required for Tabby.');
        }

        $referenceId = (string) ($invoice->id);
        $payload = $this->payloadBuilder->build($invoice, $buyer, $referenceId);

        $response = $this->tabby->createCheckoutSession($payload);

        $paymentId = data_get($response, 'payment.id');
        $webUrl = data_get($response, 'web_url')
            ?? data_get($response, 'configuration.available_products.installments.0.web_url')
            ?? data_get($response, 'configuration.available_products.pay_later.0.web_url')
            ?? data_get($response, 'configuration.available_products.pay_next_month.0.web_url');

        if (! $webUrl || ! $paymentId) {
            Log::error('Tabby checkout response missing fields', [
                'response' => $response,
            ]);
            throw new \Exception('Invalid Tabby checkout response');
        }

        return DB::transaction(function () use ($invoice, $payload, $response, $webUrl, $paymentId) {
            $invoice->update([
                'provider' => Invoice::PROVIDER_TABBY,
                'status' => Invoice::STATUS_PENDING,
                'provider_invoice_id' => (string) $paymentId,
                'payment_url' => (string) $webUrl,
                'provider_payload' => array_merge((array) ($invoice->provider_payload ?? []), [
                    'tabby_request' => $payload,
                ]),
                'provider_response' => [
                    'checkout' => $response,
                ],
            ]);

            return (string) $webUrl;
        });
    }
}
