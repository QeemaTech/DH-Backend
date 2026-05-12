<?php

namespace App\Services\Payments\Sadad;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

final class SadadInvoicePayloadBuilder
{
    private function kwdToFils(float $kwd): int
    {
        return (int) round($kwd * 1000);
    }

    private function filsToKwd(int $fils): float
    {
        return (float) number_format($fils / 1000, 3, '.', '');
    }

    private function normalizeMoney(float $value, int $precision = 3): float
    {
        return (float) number_format($value, $precision, '.', '');
    }

    private function formatMoneyString(float $value, int $precision = 3): string
    {
        return number_format($this->normalizeMoney($value, $precision), $precision, '.', '');
    }

    /**
     * Sadad expects each item {@code amount} = <strong>unit price</strong> (not line total).
     * Invoice total must equal Σ (unit amount × quantity).
     *
     * @param  array<int, array{amount: string, quantity: int}>  $items
     */
    private function sumExtendedTotalsFromUnitItemAmounts(array $items): string
    {
        if (extension_loaded('bcmath')) {
            $sum = '0.000';
            foreach ($items as $row) {
                $lineTotal = bcmul((string) $row['amount'], (string) $row['quantity'], 3);
                $sum = bcadd($sum, $lineTotal, 3);
            }

            return $this->formatMoneyString((float) $sum, 3);
        }

        $floatSum = 0.0;
        foreach ($items as $row) {
            $floatSum += (float) ((float) $row['amount'] * (int) $row['quantity']);
        }

        return $this->formatMoneyString($floatSum, 3);
    }

    /**
     * @return array<int, array{name:string,quantity:int,unit_fils:int}>
     */
    private function mapItemsInFils(Invoice $invoice): array
    {
        return $invoice->items->map(function ($item): array {
            return [
                'name' => (string) $item->name,
                'quantity' => (int) $item->quantity,
                'unit_fils' => max(0, $this->kwdToFils((float) $item->price)),
            ];
        })->toArray();
    }

    /**
     * @return array<int, array{name:string,quantity:int,unit_fils:int}>
     */
    private function splitLineByFils(string $name, int $qty, int $lineTotalFils): array
    {
        if ($qty <= 1) {
            return [[
                'name' => $name,
                'quantity' => 1,
                'unit_fils' => max(0, $lineTotalFils),
            ]];
        }

        $baseUnit = intdiv($lineTotalFils, $qty);
        $remainder = $lineTotalFils % $qty;

        $lines = [];

        if (($qty - $remainder) > 0) {
            $lines[] = [
                'name' => $name,
                'quantity' => $qty - $remainder,
                'unit_fils' => max(0, $baseUnit),
            ];
        }

        if ($remainder > 0) {
            $lines[] = [
                'name' => $name,
                'quantity' => $remainder,
                'unit_fils' => max(0, $baseUnit + 1),
            ];
        }

        return $lines;
    }

    public function build(Invoice $invoice): SadadInvoicePayload
    {
        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        if ($invoice->items->isEmpty()) {
            throw new \Exception(__('Invoice has no items'));
        }

        $itemsTotalFils = (int) $invoice->items->sum(function ($item): int {
            $qty = (int) $item->quantity;
            $unitFils = $this->kwdToFils((float) $item->price);

            return $unitFils * $qty;
        });

        $shippingFils = $this->kwdToFils((float) ($invoice->shipping_cost ?? 0));
        $couponFils = $this->kwdToFils((float) ($invoice->coupon_amount ?? 0));
        $walletFils = $this->kwdToFils((float) ($invoice->wallet_amount ?? 0));

        $subtotal = $this->filsToKwd($itemsTotalFils);

        $sadadItems = $this->mapItemsInFils($invoice);
        if ($shippingFils > 0) {
            $sadadItems[] = [
                'name' => 'Shipping',
                'quantity' => 1,
                'unit_fils' => $shippingFils,
            ];
        }

        $desiredAmountFils = $itemsTotalFils + $shippingFils - $couponFils - $walletFils;
        if ($desiredAmountFils < 0) {
            throw new \Exception(__('Invoice total cannot be negative'));
        }

        $itemsSumFils = (int) collect($sadadItems)->sum(fn ($i) => ((int) $i['unit_fils']) * ((int) $i['quantity']));
        $deltaFils = $desiredAmountFils - $itemsSumFils;

        if ($deltaFils !== 0) {
            Log::warning('Sadad amount reconciliation (fils)', [
                'invoice_local_id' => $invoice->id,
                'desired_amount_fils' => $desiredAmountFils,
                'items_sum_fils_before' => $itemsSumFils,
                'delta_fils' => $deltaFils,
                'shipping_fils' => $shippingFils,
                'coupon_fils' => $couponFils,
                'wallet_fils' => $walletFils,
            ]);

            for ($idx = count($sadadItems) - 1; $idx >= 0 && $deltaFils !== 0; $idx--) {
                $qty = max(1, (int) $sadadItems[$idx]['quantity']);
                $unitFils = max(0, (int) $sadadItems[$idx]['unit_fils']);
                $lineTotalFils = $unitFils * $qty;

                if ($deltaFils < 0) {
                    $maxReducible = $lineTotalFils;
                    $reduce = min($maxReducible, abs($deltaFils));
                    $newLineTotalFils = $lineTotalFils - $reduce;

                    $replacement = $this->splitLineByFils((string) $sadadItems[$idx]['name'], $qty, $newLineTotalFils);
                    array_splice($sadadItems, $idx, 1, $replacement);

                    $deltaFils += $reduce;
                } else {
                    $newLineTotalFils = $lineTotalFils + $deltaFils;

                    $replacement = $this->splitLineByFils((string) $sadadItems[$idx]['name'], $qty, $newLineTotalFils);
                    array_splice($sadadItems, $idx, 1, $replacement);

                    $deltaFils = 0;
                }
            }

            if ($deltaFils !== 0) {
                throw new \Exception(__('Unable to reconcile Sadad amount with items total'));
            }
        }

        /**
         * Per Sadad samples, each line's {@code amount} is the <strong>unit price</strong>;
         * their server totals with {@code quantity × amount} and compares to the invoice {@code amount}.
         * Sending line totals as {@code amount} makes them compute e.g. 18 × 135 instead of 18 × 7.5.
         *
         * @see https://sadadpay.readme.io/reference/create-invoice
         */
        $itemsForPayload = [];
        foreach ($sadadItems as $i) {
            $qty = max(1, (int) $i['quantity']);
            $unitFils = max(0, (int) $i['unit_fils']);
            $unitAmountStr = $this->formatMoneyString($this->filsToKwd($unitFils), 3);

            $itemsForPayload[] = [
                'name' => (string) $i['name'],
                'quantity' => $qty,
                'amount' => $unitAmountStr,
            ];
        }

        $invoiceTotalFils = (int) collect($sadadItems)->sum(fn (array $i) => ((int) $i['unit_fils']) * max(1, (int) $i['quantity']));
        $invoiceAmountStr = $this->formatMoneyString($this->filsToKwd($invoiceTotalFils), 3);

        /**
         * Do not send Laravel invoice PK as vendor_Id / gateway — use optional config from dashboard.
         *
         * @see https://sadadpay.readme.io/
         */
        $invoicePayload = [
            'amount' => $invoiceAmountStr,
            'sendType' => 'Email',
            'lang' => app()->getLocale(),
            'customer_Name' => $invoice->customer_name,
            'customer_Mobile' => $invoice->customer_phone,
            'customer_Email' => $invoice->customer_email,
            'ref_Number' => 'INV-'.$invoice->id,
            'currency_Code' => $invoice->currency_code ?? 'KWD',
            'expiry_Date' => now()->addDays(5)->toDateString(),
            'attachment' => null,
            'terms_Condition_Enabled' => false,
            'items' => $itemsForPayload,
        ];

        $invoicePayload = $this->mergeOptionalSadadInvoiceFields($invoicePayload);

        $payload = [
            'invoices' => [$invoicePayload],
        ];

        $itemsSum = $this->normalizeMoney((float) $this->sumExtendedTotalsFromUnitItemAmounts($itemsForPayload), 3);
        Log::info('Sadad amount check', [
            'invoice_local_id' => $invoice->id,
            'amount' => (string) $payload['invoices'][0]['amount'],
            'items_sum' => $itemsSum,
            'diff' => $this->normalizeMoney(((float) $payload['invoices'][0]['amount']) - $itemsSum),
        ]);

        return new SadadInvoicePayload(
            payload: $payload,
            subtotal: $subtotal,
            totalAmount: (float) $this->normalizeMoney((float) $invoiceAmountStr, 3),
        );
    }

    /**
     * @param  array<string, mixed>  $invoicePayload
     * @return array<string, mixed>
     */
    private function mergeOptionalSadadInvoiceFields(array $invoicePayload): array
    {
        $vendorRaw = trim((string) config('services.sadad.invoice_vendor_id', ''));

        if ($vendorRaw !== '') {
            $invoicePayload['vendor_Id'] = filter_var($vendorRaw, FILTER_VALIDATE_INT) !== false
                ? (int) $vendorRaw
                : $vendorRaw;
        }

        $gatewayRaw = trim((string) config('services.sadad.invoice_payment_gateway', ''));
        if ($gatewayRaw !== '') {
            $invoicePayload['paymentGateway'] = $gatewayRaw;
        }

        $gatewayCodeRaw = trim((string) config('services.sadad.invoice_payment_gateway_code', ''));
        if ($gatewayCodeRaw !== '') {
            $invoicePayload['paymentGatewayCode'] = $gatewayCodeRaw;
        }

        return $invoicePayload;
    }
}
