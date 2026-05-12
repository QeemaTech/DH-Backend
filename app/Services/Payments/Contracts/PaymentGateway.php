<?php

namespace App\Services\Payments\Contracts;

use App\Models\Invoice;

interface PaymentGateway
{
    public function provider(): string;

    public function createPaymentLink(Invoice $invoice): string;
}
