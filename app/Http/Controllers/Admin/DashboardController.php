<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryRequest;
use App\Models\Order;
use App\Models\OrderRefundRequest;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorWithdrawal;
use App\Services\EezeePayService;
use App\Services\Like4AppService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index(): View
    {
        $now = CarbonImmutable::now();
        $today = $now->startOfDay();
        $thisMonth = $now->startOfMonth();
        $thisYear = $now->startOfYear();

        // Overall Stats
        $totalVendors = Vendor::query()->count();
        $activeVendors = Vendor::query()->where('is_active', true)->count();
        $totalProducts = Product::query()->count();
        $activeProducts = Product::query()->where('is_active', true)->where('is_approved', true)->count();
        $totalCustomers = User::query()->where('role', 'user')->count();
        $activeCustomers = User::query()->where('role', 'user')->where('is_active', true)->count();

        // Today Stats
        $todayOrders = Order::query()->whereDate('created_at', $today)->count();
        $todayRevenue = (float) Order::query()->whereDate('created_at', $today)->where('payment_status', 'paid')->sum('total');
        $todayCommission = (float) Order::query()->whereDate('created_at', $today)->where('payment_status', 'paid')->sum('total_commission');

        // This Month Stats
        $monthOrders = Order::query()->where('created_at', '>=', $thisMonth)->count();
        $monthRevenue = (float) Order::query()->where('created_at', '>=', $thisMonth)->where('payment_status', 'paid')->sum('total');
        $monthCommission = (float) Order::query()->where('created_at', '>=', $thisMonth)->where('payment_status', 'paid')->sum('total_commission');
        $monthDelivered = Order::query()->where('created_at', '>=', $thisMonth)->where('status', 'delivered')->where('payment_status', 'paid')->count();

        // This Year Stats
        $yearRevenue = (float) Order::query()->where('created_at', '>=', $thisYear)->where('payment_status', 'paid')->sum('total');
        $yearCommission = (float) Order::query()->where('created_at', '>=', $thisYear)->where('payment_status', 'paid')->sum('total_commission');

        // Pending Items
        $pendingCategoryRequests = CategoryRequest::query()->pending()->count();
        $pendingVariantRequests = \App\Models\VariantRequest::query()->pending()->count();
        $pendingRefundRequests = OrderRefundRequest::query()->where('status', 'pending')->count();
        $pendingWithdrawals = VendorWithdrawal::query()->where('status', 'pending')->count();
        $pendingOrders = Order::query()->where('status', 'pending')->count();
        $pendingProducts = Product::query()->where('is_approved', false)->where('is_active', true)->count();

        // Recent Orders
        $recentOrders = Order::query()
            ->with(['user', 'vendorOrders.vendor'])
            ->latest()
            ->take(10)
            ->get();

        // Top Vendors (This Month)
        $topVendors = VendorOrder::query()
            ->with('vendor')
            ->whereHas('order', function ($q) use ($thisMonth) {
                $q->where('created_at', '>=', $thisMonth)
                    ->where('payment_status', 'paid');
            })
            ->selectRaw('vendor_id, SUM(total) as total_sales, SUM(commission) as total_commission, COUNT(*) as orders_count')
            ->groupBy('vendor_id')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get()
            ->map(function ($vo) {
                return [
                    'vendor' => $vo->vendor,
                    'total_sales' => (float) $vo->total_sales,
                    'total_commission' => (float) $vo->total_commission,
                    'orders_count' => (int) $vo->orders_count,
                ];
            });

        // Category Requests
        $pendingRequests = CategoryRequest::with(['vendor', 'reviewer'])
            ->pending()
            ->latest()
            ->take(10)
            ->get();

        $recentRequests = CategoryRequest::with(['vendor', 'reviewer'])
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->take(5)
            ->get();

        $like4appBalance = null;
        $like4appCurrency = null;
        $like4appBalanceError = null;

        $eezeePayBalance = null;
        $eezeePayCurrency = null;
        $eezeePayBalanceError = null;

        try {
            $like4app = Cache::remember('like4app:balance', now()->addMinutes(5), function () {
                /** @var Like4AppService $service */
                $service = app(Like4AppService::class);

                return $service->checkBalance();
            });

            $like4appBalance = data_get($like4app, 'balance')
                ?? data_get($like4app, 'data.balance')
                ?? data_get($like4app, 'data.0.balance');
            $like4appCurrency = data_get($like4app, 'currency')
                ?? data_get($like4app, 'data.currency')
                ?? data_get($like4app, 'data.0.currency');
        } catch (\Throwable $e) {
            $like4appBalanceError = $e->getMessage();
            Log::warning('Like4App balance unavailable on dashboard', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $eezee = Cache::remember('eezee_pay:balance', now()->addMinutes(5), function () {
                /** @var EezeePayService $service */
                $service = app(EezeePayService::class);

                return $service->balance();
            });

            $eezeePayBalance = data_get($eezee, 'balance')
                ?? data_get($eezee, 'data.balance')
                ?? data_get($eezee, 'data.0.balance')
                ?? data_get($eezee, 'data.amount');

            $eezeePayCurrency = data_get($eezee, 'currency')
                ?? data_get($eezee, 'data.currency')
                ?? data_get($eezee, 'data.0.currency')
                ?? data_get($eezee, 'data.currency_code');
        } catch (\Throwable $e) {
            $eezeePayBalanceError = $e->getMessage();
            Log::warning('Eezee Pay balance unavailable on dashboard', [
                'error' => $e->getMessage(),
            ]);
        }

        return view('admin.dashboard', compact(
            'totalVendors',
            'activeVendors',
            'totalProducts',
            'activeProducts',
            'totalCustomers',
            'activeCustomers',
            'todayOrders',
            'todayRevenue',
            'todayCommission',
            'monthOrders',
            'monthRevenue',
            'monthCommission',
            'monthDelivered',
            'yearRevenue',
            'yearCommission',
            'pendingCategoryRequests',
            'pendingVariantRequests',
            'pendingRefundRequests',
            'pendingWithdrawals',
            'pendingOrders',
            'pendingProducts',
            'recentOrders',
            'topVendors',
            'pendingRequests',
            'recentRequests',
            'like4appBalance',
            'like4appCurrency',
            'like4appBalanceError',
            'eezeePayBalance',
            'eezeePayCurrency',
            'eezeePayBalanceError',
        ));
    }
}
