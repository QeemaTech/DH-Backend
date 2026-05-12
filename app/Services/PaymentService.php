<?php

namespace App\Services;

use App\Models\Invoice;
use App\Services\Payments\Dema\DemaGateway;
use App\Services\Payments\Sadad\SadadGateway;
use App\Services\Payments\Tabby\TabbyGateway;

class PaymentService
{
    public function __construct(
        private SadadGateway $sadadGateway,
        private TabbyGateway $tabbyGateway,
        private DemaGateway $demaGateway,
    ) {}

    public function generatePaymentLink(Invoice $invoice): string
    {
        return match ($invoice->provider) {
            Invoice::PROVIDER_SADAD => $this->sadadGateway->createPaymentLink($invoice),
            Invoice::PROVIDER_TABBY => $this->tabbyGateway->createPaymentLink($invoice),
            Invoice::PROVIDER_DEMA => $this->demaGateway->createPaymentLink($invoice),
            default => throw new \Exception('Unsupported payment provider: '.$invoice->provider),
        };
    }
}
