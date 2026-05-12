<?php

namespace App\Services\Payments\Sadad;

final class SadadInvoicePayload
{
    /**
     * @param  array{invoices: array<int, array<string, mixed>>}  $payload
     */
    public function __construct(
        public array $payload,
        public float $subtotal,
        public float $totalAmount,
    ) {}
}
