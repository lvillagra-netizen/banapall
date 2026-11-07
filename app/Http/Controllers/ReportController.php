<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->build($request);

        return view('reports.index', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->build($request);
        $filename = 'reporte-ventas-'.$data['from']->format('Ymd').'-'.$data['to']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Folio', 'Fecha', 'Cliente', 'Vendedor', 'Moneda', 'Tipo cambio', 'Neto', 'Descuento', 'Impuesto', 'Total', 'Pagado', 'Saldo', 'Estado']);

            foreach ($data['sales'] as $sale) {
                fputcsv($out, [
                    $sale->number ?: 'BORR-'.$sale->id,
                    $sale->created_at->format('Y-m-d H:i'),
                    $sale->customer?->name,
                    $sale->seller?->name,
                    $sale->currency_code,
                    $sale->exchange_rate,
                    $sale->subtotal,
                    $sale->discount_total,
                    $sale->tax_total,
                    $sale->total,
                    $sale->paid_total,
                    $sale->balance,
                    $sale->status->label(),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function build(Request $request): array
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        $query = Sale::query()
            ->with(['customer', 'seller'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($currency = $request->string('currency')->toString()) {
            $query->where('currency_code', $currency);
        }

        if ($seller = $request->integer('seller')) {
            $query->where('seller_id', $seller);
        }

        if ($product = $request->integer('product')) {
            $query->whereHas('items', fn ($q) => $q->where('product_id', $product));
        }

        $sales = $query->orderBy('created_at')->get();
        $activeSales = $sales->where('status', '!=', SaleStatus::Anulada);

        $byCurrency = $activeSales->groupBy('currency_code')->map(function ($group, $currency) {
            return [
                'currency' => $currency,
                'count' => $group->count(),
                'total' => Money::format($group->sum('total'), $currency),
                'paid' => Money::format($group->sum('paid_total'), $currency),
                'balance' => Money::format($group->sum('balance'), $currency),
            ];
        })->values();

        $totalClp = $activeSales->sum(fn (Sale $sale) => (float) $sale->total * (float) $sale->exchange_rate);

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', '>=', $from)
            ->whereDate('sales.created_at', '<=', $to)
            ->where('sales.status', '!=', SaleStatus::Anulada->value)
            ->select('sale_items.sku', 'sale_items.product_name', DB::raw('SUM(sale_items.quantity) as quantity'), DB::raw('SUM(sale_items.line_total) as total'))
            ->groupBy('sale_items.sku', 'sale_items.product_name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        $sellers = $activeSales->groupBy('seller_id')->map(function ($group) {
            $seller = $group->first()->seller;

            return [
                'name' => $seller?->name ?? 'Sin vendedor',
                'count' => $group->count(),
                'total_clp_raw' => $group->sum(fn (Sale $sale) => (float) $sale->total * (float) $sale->exchange_rate),
            ];
        })->sortByDesc('total_clp_raw')->values()->map(fn (array $seller) => [
            'name' => $seller['name'],
            'count' => $seller['count'],
            'total_clp' => Money::format($seller['total_clp_raw'], 'CLP'),
        ]);

        return [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'byCurrency' => $byCurrency,
            'totalClp' => Money::format($totalClp, 'CLP'),
            'topProducts' => $topProducts,
            'sellers' => $sellers,
            'statuses' => SaleStatus::cases(),
            'sellerOptions' => User::query()->orderBy('name')->get(['id', 'name']),
            'productOptions' => Product::query()->active()->orderBy('name')->get(['id', 'name', 'sku']),
        ];
    }
}
