<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DemaRedirectController extends Controller
{
    public function success(Request $request): View
    {
        return view('payments.dema.success', [
            'chargeId' => $this->resolveChargeIdFromRequest($request),
        ]);
    }

    public function cancel(Request $request): View
    {
        return view('payments.dema.cancel', [
            'chargeId' => $this->resolveChargeIdFromRequest($request),
        ]);
    }

    public function failure(Request $request): View
    {
        return view('payments.dema.failure', [
            'chargeId' => $this->resolveChargeIdFromRequest($request),
        ]);
    }

    private function resolveChargeIdFromRequest(Request $request): string
    {
        return (string) $request->query('tap_id', $request->query('charge_id', $request->query('payment_id', '')));
    }
}
