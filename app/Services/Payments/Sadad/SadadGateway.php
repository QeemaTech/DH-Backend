<?php

namespace App\Services\Payments\Sadad;

use App\Models\Invoice;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\SadadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SadadGateway implements PaymentGateway
{
    public function __construct(
        private SadadService $sadad,
        private SadadInvoicePayloadBuilder $payloadBuilder,
    ) {}

    public function provider(): string
    {
        return Invoice::PROVIDER_SADAD;
    }

    public function createPaymentLink(Invoice $invoice): string
    {
        if ($invoice->payment_url) {
            return $invoice->payment_url;
        }

        $built = $this->payloadBuilder->build($invoice);

        return DB::transaction(function () use ($invoice, $built) {
            $response = $this->sadad->createInvoice($built->payload);

            if (data_get($response, 'isValid') === false) {
                $errorKey = data_get($response, 'errorKey') ?? 'SadadInvoiceInvalid';
                throw new \Exception((string) $errorKey);
            }

            $invoiceId = data_get($response, 'response.invoiceId')
                ?? data_get($response, 'response.invoiceID')
                ?? data_get($response, 'invoiceId')
                ?? data_get($response, 'invoiceID')
                ?? data_get($response, 'data.invoiceId')
                ?? data_get($response, 'data.invoiceID');

            if (! $invoiceId) {
                Log::error('Sadad invoiceId missing', [
                    'invoice_local_id' => $invoice->id,
                    'sadad_response' => $response,
                ]);
                throw new \Exception('Sadad invoiceId missing');
            }

            $link = data_get($response, 'response.invoiceURL');
            $key = null;
            $invoiceData = null;

            if (! $link) {
                $invoiceData = $this->sadad->getInvoice($invoiceId);
                $key = data_get($invoiceData, 'response.key');
                if (! $key) {
                    throw new \Exception('Sadad payment key missing');
                }

                $paymentBaseUrl = rtrim((string) config('services.sadad.payment_base_url', 'https://sadadpay.net'), '/');
                $link = $paymentBaseUrl.'/pay/'.$key;
            }

            $invoice->update([
                'provider' => Invoice::PROVIDER_SADAD,
                'status' => Invoice::STATUS_PENDING,
                'provider_invoice_id' => (string) $invoiceId,
                'provider_key' => $key ? (string) $key : null,
                'payment_url' => $link,
                'provider_payload' => array_merge((array) ($invoice->provider_payload ?? []), [
                    'sadad_request' => $built->payload,
                ]),
                'provider_response' => [
                    'create_invoice' => $response,
                    'get_invoice' => $invoiceData ?? null,
                ],
                'subtotal' => $built->subtotal,
                'total_amount' => $built->totalAmount,
                'currency_code' => $invoice->currency_code ?? 'KWD',
                'expires_at' => now()->addDays(5)->toDateString(),
            ]);

            return (string) $link;
        });
    }
}
