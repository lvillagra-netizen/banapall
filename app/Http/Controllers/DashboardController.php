<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\ExchangeRateService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ExchangeRateService $exchangeRates): View
    {
        $todaySales = Sale::query()
            ->whereDate('created_at', today())
            ->where('status', '!=', SaleStatus::Anulada->value)
            ->get();

        $monthSales = Sale::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', '!=', SaleStatus::Anulada->value)
            ->get();

        $pendingSales = Sale::query()
            ->where('status', SaleStatus::Validada->value)
            ->where('balance', '>', 0)
            ->with('customer')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $recentSales = Sale::query()
            ->with(['customer', 'seller'])
            ->latest()
            ->take(8)
            ->get();

        $lowStock = Stock::query()
            ->with(['product', 'warehouse'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->get()
            ->filter(fn (Stock $stock) => (float) $stock->quantity <= (float) $stock->product->min_stock)
            ->sortBy(fn (Stock $stock) => (float) $stock->quantity)
            ->take(6)
            ->values();

        $chart = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = today()->subDays($daysAgo);
            $sales = Sale::query()
                ->whereDate('created_at', $date)
                ->where('status', '!=', SaleStatus::Anulada->value)
                ->get();

            return [
                'label' => $date->locale('es')->isoFormat('ddd D'),
                'count' => $sales->count(),
                'total_clp' => round($sales->sum(fn (Sale $sale) => (float) $sale->total * (float) $sale->exchange_rate)),
            ];
        });

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', '!=', SaleStatus::Anulada->value)
            ->where('sales.created_at', '>=', now()->subDays(30))
            ->select('sale_items.sku', 'sale_items.product_name', DB::raw('SUM(sale_items.quantity) as quantity'), DB::raw('SUM(sale_items.line_total) as total'))
            ->groupBy('sale_items.sku', 'sale_items.product_name')
            ->orderByDesc('quantity')
            ->limit(6)
            ->get();

        $statusCounts = Sale::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'todayCount' => $todaySales->count(),
            'todayRevenue' => Money::format($todaySales->sum(fn (Sale $sale) => (float) $sale->total * (float) $sale->exchange_rate), 'CLP'),
            'monthRevenue' => Money::format($monthSales->sum(fn (Sale $sale) => (float) $sale->total * (float) $sale->exchange_rate), 'CLP'),
            'monthCount' => $monthSales->count(),
            'pendingSales' => $pendingSales,
            'pendingTotal' => Money::format($pendingSales->sum(fn (Sale $sale) => (float) $sale->balance * (float) $sale->exchange_rate), 'CLP'),
            'recentSales' => $recentSales,
            'lowStock' => $lowStock,
            'chart' => $chart,
            'topProducts' => $topProducts,
            'statusCounts' => $statusCounts,
            'customerCount' => Customer::query()->active()->count(),
            'productCount' => Product::query()->active()->count(),
            'rates' => $exchangeRates->latestRates(),
        ]);
    }
}
