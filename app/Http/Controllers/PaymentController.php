<?php

namespace App\Http\Controllers;

use App\Models\DigitalOrder;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\DigitalOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function SadadWebhook(Request $request)
    {
        $data = $request->all();

        Log::info('Sadad webhook received', ['data' => $data]);

        $invoiceId = $data['invoiceId'] ?? null;
        $status = strtolower((string) ($data['status'] ?? ''));

        if (! $invoiceId || $status === '') {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $invoice = Invoice::query()->where('provider', Invoice::PROVIDER_SADAD)
            ->where('provider_invoice_id', (string) $invoiceId)
            ->first();
        if (! $invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        if (in_array($status, ['paid', 'success'], true)) {
            DB::transaction(function () use ($invoice) {
                $invoice->markPaid();
                // mark digital order paid if applicable and mark normal order paid if applicable
                $this->fulfillDigitalOrderIfApplicable($invoice);
                $this->fulfillNormalOrderIfApplicable($invoice);
            });
        } elseif (in_array($status, ['Failed', 'fail'], true)) {
            $invoice->markFailed();
        }

        return response()->json(['success' => true]);
    }
    public function TabbyWebhook(Request $request)
    {
        $headerName = (string) config('services.tabby.webhook.header_name');
        $headerValue = (string) config('services.tabby.webhook.header_value');

        if ($headerName !== '' && $headerValue !== '') {
            $sent = (string) $request->header($headerName, '');
            if (! hash_equals($headerValue, $sent)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $data = (array) $request->all();

        Log::info('Tabby webhook received', ['data' => $data]);

        $paymentId = (string) data_get($data, 'id', '');
        $status = strtolower((string) data_get($data, 'status', ''));
        $referenceId = (string) data_get($data, 'order.reference_id', '');

        if ($paymentId === '' || $status === '') {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $invoice = Invoice::query()
            ->where('provider', Invoice::PROVIDER_TABBY)
            ->where('provider_invoice_id', $paymentId)
            ->first();

        if (! $invoice && $referenceId !== '') {
            $invoice = Invoice::query()
                ->where('provider', Invoice::PROVIDER_TABBY)
                ->where('id', (int) $referenceId)
                ->first();
        }

        if (! $invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        // Store full webhook payload for audit/debugging
        $invoice->update([
            'provider_response' => array_merge((array) ($invoice->provider_response ?? []), [
                'webhook' => $data,
            ]),
        ]);

        // Map Tabby statuses (lowercase in webhooks)
        if (in_array($status, ['closed'], true)) {
            DB::transaction(function () use ($invoice) {
                $invoice->markPaid();
                $this->fulfillDigitalOrderIfApplicable($invoice);
            });
        } elseif (in_array($status, ['rejected', 'expired'], true)) {
            $invoice->markFailed();
        } else {
            $invoice->update(['status' => Invoice::STATUS_PENDING]);
        }

        return response()->json(['success' => true]);
    }

    public function DemaWebhook(Request $request)
    {
        $headerName = (string) config('services.dema.webhook.header_name');
        $headerValue = (string) config('services.dema.webhook.header_value');

        if ($headerName !== '' && $headerValue !== '') {
            $sent = (string) $request->header($headerName, '');
            if (! hash_equals($headerValue, $sent)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $data = (array) $request->all();

        Log::info('Deema webhook received', ['data' => $data]);

        $orderReference = (string) data_get($data, 'order_reference', '');
        if ($orderReference === '') {
            $orderReference = (string) data_get($data, 'data.order_reference', '');
        }

        $status = strtolower((string) data_get($data, 'status', ''));
        if ($status === '') {
            $status = strtolower((string) data_get($data, 'data.status', ''));
        }

        if ($orderReference === '' || $status === '') {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $invoice = Invoice::query()
            ->where('provider', Invoice::PROVIDER_DEMA)
            ->where('provider_invoice_id', $orderReference)
            ->first();

        if (! $invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoice->update([
            'provider_response' => array_merge((array) ($invoice->provider_response ?? []), [
                'webhook' => $data,
            ]),
        ]);

        if (in_array($status, ['captured'], true)) {
            DB::transaction(function () use ($invoice) {
                $invoice->markPaid();
                $this->fulfillDigitalOrderIfApplicable($invoice);
            });
        } elseif (in_array($status, ['failed', 'cancelled', 'expired'], true)) {
            $invoice->markFailed();
        } else {
            $invoice->update(['status' => Invoice::STATUS_PENDING]);
        }

        return response()->json(['success' => true]);
    }

    protected function fulfillDigitalOrderIfApplicable(Invoice $invoice): void
    {
        $digitalOrderId = data_get($invoice->provider_payload, 'digital_order_id');
        if (! $digitalOrderId) {
            return;
        }

        /** @var DigitalOrder|null $order */
        $order = DigitalOrder::query()->find((int) $digitalOrderId);
        if (! $order) {
            Log::warning('Digital order not found for paid invoice', [
                'invoice_id' => $invoice->id,
                'digital_order_id' => $digitalOrderId,
            ]);

            return;
        }

        // Payment is confirmed at this point; mark the digital order paid even if provider fulfillment fails later.
        if ($order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status' => $order->status === 'pending' ? 'processing' : $order->status,
            ]);
        }

        try {
            /** @var DigitalOrderService $service */
            $service = app(DigitalOrderService::class);
            $service->fulfillAfterPayment($order, paymentRef: 'INV-'.$invoice->id.'-DO-'.$order->id);
        } catch (\Throwable $e) {
            Log::error('Digital order fulfillment failed after payment', [
                'invoice_id' => $invoice->id,
                'digital_order_id' => $digitalOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    protected function fulfillNormalOrderIfApplicable(Invoice $invoice): void
    {
        $orderId = data_get($invoice->provider_payload, 'order_id');
        if (! $orderId) {
            return;
        }
        $order = Order::query()->find((int) $orderId);
        if (! $order) {
            return;
        }
        $order->update([
            'payment_status' => 'paid',
        ]);
        return ;


    }
}
