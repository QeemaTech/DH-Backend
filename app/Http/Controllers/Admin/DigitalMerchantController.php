<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DigitalMerchant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DigitalMerchantController extends Controller
{
    public function index(Request $request): View
    {
        $query = DigitalMerchant::query()->with('parent');

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('merchant_id', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"]);
            });
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', (string) $request->get('company_name'));
        }

        $digitalMerchants = $query->latest('id')->paginate(20);
        $companies = DigitalMerchant::query()->select('company_name')->distinct()->pluck('company_name');

        return view('admin.digital-merchants.index', compact('digitalMerchants', 'companies'));
    }

    public function show(DigitalMerchant $digitalMerchant): View
    {
        $digitalMerchant->load(['parent', 'children']);

        return view('admin.digital-merchants.show', compact('digitalMerchant'));
    }
}
