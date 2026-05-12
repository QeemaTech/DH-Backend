<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TabbyRedirectController extends Controller
{
    public function success(Request $request): View
    {
        return view('payments.tabby.success', [
            'paymentId' => (string) $request->query('payment_id', ''),
        ]);
    }

    public function cancel(Request $request): View
    {
        return view('payments.tabby.cancel', [
            'paymentId' => (string) $request->query('payment_id', ''),
        ]);
    }

    public function failure(Request $request): View
    {
        return view('payments.tabby.failure', [
            'paymentId' => (string) $request->query('payment_id', ''),
        ]);
    }
}
