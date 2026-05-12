<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSadadPayment extends Command
{
    protected $signature = 'sadad:test
                            {--amount=10.000 : Total amount for a single item}
                            {--name=Test Item : Item name}
                            {--customer-name=Test Customer}
                            {--customer-phone=00000000}
                            {--customer-email=test@example.com}
                            {--user-id= : Optional user id}
                            {--reuse-latest : Reuse latest pending invoice if exists}';

    protected $description = 'Create a test invoice + items and generate Sadad payment link';

    public function handle(PaymentService $paymentService): int
    {
        if (! \Schema::hasTable('invoices') || ! \Schema::hasTable('invoice_items')) {
            $this->error('Missing invoices tables. Run: php artisan migrate');

            return self::FAILURE;
        }

        $reuse = (bool) $this->option('reuse-latest');

        if ($reuse) {
            $invoice = Invoice::query()->where('provider', Invoice::PROVIDER_SADAD)->where('status', Invoice::STATUS_PENDING)->latest('id')->first();
            if ($invoice) {
                $this->info("Reusing invoice #{$invoice->id}");
                $link = $paymentService->generatePaymentLink($invoice);
                $this->line($link);

                return self::SUCCESS;
            }
        }

        $amount = (float) $this->option('amount');

        $invoice = DB::transaction(function () use ($amount) {
            $invoice = Invoice::create([
                'user_id' => $this->option('user-id') ? (int) $this->option('user-id') : null,
                'customer_name' => (string) $this->option('customer-name'),
                'customer_phone' => (string) $this->option('customer-phone'),
                'customer_email' => (string) $this->option('customer-email'),
                'currency_code' => 'KWD',
                'shipping_cost' => 0,
                'coupon_amount' => 0,
                'wallet_amount' => 0,
                'provider' => Invoice::PROVIDER_SADAD,
                'status' => Invoice::STATUS_PENDING,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'name' => (string) $this->option('name'),
                'quantity' => 1,
                'price' => $amount,
            ]);

            return $invoice->fresh('items');
        });

        $this->info("Created invoice #{$invoice->id}");

        $link = $paymentService->generatePaymentLink($invoice);
        $this->info('Payment link:');
        $this->line($link);

        return self::SUCCESS;
    }
}
