<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SadadPaymentController extends Controller
{
    /**
     * Create an invoice and return Sadad payment link.
     */
    public function createPaymentLink(Request $request, PaymentService $paymentService): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'coupon_amount' => ['nullable', 'numeric', 'min:0'],
            'wallet_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        $invoice = DB::transaction(function () use ($data, $user) {
            $invoice = Invoice::create([
                'user_id' => $user?->id,
                'customer_name' => $data['customer_name'] ?? ($user?->name),
                'customer_phone' => $data['customer_phone'] ?? ($user?->phone),
                'customer_email' => $data['customer_email'] ?? ($user?->email),
                'currency_code' => $data['currency_code'] ?? 'KWD',
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'coupon_amount' => $data['coupon_amount'] ?? 0,
                'wallet_amount' => $data['wallet_amount'] ?? 0,
                'provider' => Invoice::PROVIDER_SADAD,
                'status' => Invoice::STATUS_PENDING,
            ]);

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $item['name'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                ]);
            }

            return $invoice->fresh('items');
        });

        $paymentUrl = $paymentService->generatePaymentLink($invoice);

        return response()->json([
            'success' => true,
            'invoice_id' => $invoice->id,
            'payment_url' => $paymentUrl,
            'status' => $invoice->status,
        ]);
    }

    public function showInvoice(Invoice $invoice): JsonResponse
    {
        $invoice->load('items');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $invoice->id,
                'provider' => $invoice->provider,
                'status' => $invoice->status,
                'paid_at' => $invoice->paid_at,
                'expires_at' => $invoice->expires_at,
                'payment_url' => $invoice->payment_url,
                'total_amount' => $invoice->total_amount,
                'currency_code' => $invoice->currency_code,
                'items' => $invoice->items,
            ],
        ]);
    }
}
