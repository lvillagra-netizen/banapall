<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TaxMode;
use App\Exceptions\SaleValidationException;
use App\Models\Bin;
use App\Models\Chamber;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\ExchangeRateService;
use App\Services\PriceService;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PosController extends Controller
{
    public function index(PriceService $prices, ExchangeRateService $exchangeRates, ?Sale $sale = null): View
    {
        return $this->renderPosView($prices, $exchangeRates, $sale);
    }

    public function edit(Sale $sale, PriceService $prices, ExchangeRateService $exchangeRates): View|RedirectResponse
    {
        if (! $sale->isDraft()) {
            return redirect()
                ->route('sales.show', $sale)
                ->with('error', 'Solo se pueden editar ventas en estado borrador.');
        }

        $sale->load(['items', 'customer', 'payments']);

        return $this->renderPosView($prices, $exchangeRates, $sale);
    }

    protected function renderPosView(PriceService $prices, ExchangeRateService $exchangeRates, ?Sale $sale = null): View
    {
        $warehouses = Warehouse::query()->active()->orderBy('name')->get();
        $products = Product::query()
            ->with(['category', 'prices', 'stocks'])
            ->active()
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($prices, $warehouses) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category?->name ?? 'Sin categoría',
                    'unit' => $product->unit,
                    'image' => $product->imageUrl(),
                    'prices' => $prices->allPrices($product),
                    'stock_by_warehouse' => $warehouses->mapWithKeys(fn (Warehouse $warehouse) => [
                        $warehouse->id => [
                            'quantity' => (string) $product->stocks
                                ->where('warehouse_id', $warehouse->id)
                                ->sum(fn ($stock) => (float) $stock->quantity),
                            'by_chamber' => $product->stocks
                                ->where('warehouse_id', $warehouse->id)
                                ->whereNotNull('chamber_id')
                                ->mapWithKeys(fn ($stock) => [
                                    $stock->chamber_id => (string) $stock->quantity,
                                ])->all(),
                        ],
                    ])->all(),
                ];
            })
            ->values();

        $customers = Customer::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'tax_id' => $customer->displayTaxId(),
                'email' => $customer->email,
                'country_code' => $customer->country_code,
                'currency_code' => $customer->currency_code,
                'tax_mode' => TaxMode::forCountry($customer->country_code)->value,
                'city' => $customer->city,
            ]);

        $defaultWarehouse = (int) Setting::get('pos.default_warehouse', $warehouses->first()?->id);
        $defaultCurrency = (string) Setting::get('pos.default_currency', config('banapal.base_currency', 'CLP'));

        return view('pos.index', [
            'warehouses' => $warehouses,
            'products' => $products,
            'customers' => $customers,
            'defaultWarehouse' => $warehouses->firstWhere('id', $defaultWarehouse)?->id ?? $warehouses->first()?->id,
            'defaultCurrency' => $defaultCurrency,
            'currencies' => config('banapal.currencies'),
            'rates' => $exchangeRates->latestRates(),
            'taxes' => collect(config('banapal.taxes'))->map(fn (array $tax, string $code) => [
                'value' => $code,
                'label' => $tax['label'],
                'rate' => $tax['rate'],
            ])->values(),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $method) => [
                'value' => $method->value,
                'label' => $method->label(),
                'currencies' => $method->allowedCurrencies(),
            ])->values(),
            'bins' => Bin::query()->active()->orderBy('code')->get()->map(fn (Bin $bin) => [
                'id' => $bin->id,
                'code' => $bin->code,
                'name' => $bin->name,
                'capacity_kg' => (float) $bin->capacity_kg,
                'cost_clp' => (float) $bin->cost_clp,
            ])->values(),
            'chambers' => Chamber::query()->with('warehouse')->active()->orderBy('name')->get()->map(fn (Chamber $chamber) => [
                'id' => $chamber->id,
                'code' => $chamber->code,
                'name' => $chamber->name,
                'warehouse_id' => $chamber->warehouse_id,
                'warehouse_name' => $chamber->warehouse?->name,
            ])->values(),
            'editingSale' => $sale ? [
                'id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'warehouse_id' => $sale->warehouse_id,
                'currency_code' => $sale->currency_code,
                'tax_mode' => $sale->tax_mode->value,
                'notes' => $sale->notes ?? '',
                'items' => $sale->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'chamber_id' => $item->chamber_id,
                    'bin_id' => $item->bin_id,
                    'name' => $item->product_name,
                    'sku' => $item->sku,
                    'unit' => $item->product?->unit ?? 'un',
                    'quantity' => (float) $item->quantity,
                    'discount' => (float) $item->discount_amount,
                ])->values()->all(),
                'payments' => $sale->payments
                    ->where('status', 'pendiente')
                    ->map(fn ($payment) => [
                        'method' => $payment->method->value,
                        'currency_code' => $payment->currency_code,
                        'amount' => (float) $payment->amount,
                        'reference' => (string) ($payment->reference ?? ''),
                    ])
                    ->values()
                    ->all(),
            ] : null,
        ]);
    }

    public function store(Request $request, SaleService $sales): RedirectResponse
    {
        if ($request->has('payments')) {
            $filtered = collect($request->input('payments', []))
                ->filter(fn ($p) => (isset($p['amount']) && (float) $p['amount'] > 0) || ! empty($p['method']))
                ->values()
                ->all();
            $request->merge(['payments' => ! empty($filtered) ? $filtered : null]);
        }

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'currency_code' => ['required', 'in:CLP,USD,PEN'],
            'tax_mode' => ['nullable', 'in:iva_19,igv_18,export_0,exempt'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'mode' => ['required', 'in:draft,finalize'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.chamber_id' => ['nullable', 'exists:chambers,id'],
            'items.*.bin_id' => ['nullable', 'exists:bins,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'in:efectivo,transferencia,tarjeta,credito,cheque_fecha,yape,plin,otro'],
            'payments.*.currency_code' => ['required_with:payments', 'in:CLP,USD,PEN'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payments = collect($data['payments'] ?? [])
            ->filter(fn (array $payment) => isset($payment['amount']) && (float) $payment['amount'] > 0)
            ->values()
            ->all();

        $sale = null;

        try {
            $sale = $sales->createDraft($data, $request->user());

            if ($data['mode'] === 'finalize') {
                $sale = $sales->finalize($sale, $request->user(), $payments, true);

                return redirect()
                    ->route('sales.show', $sale)
                    ->with('success', "Venta {$sale->number} validada correctamente.");
            }

            $sale = $sales->saveDraftPayments($sale, $payments, $request->user());

            return redirect()
                ->route('sales.show', $sale)
                ->with('success', 'Borrador de venta guardado. Puedes revisarlo y validarlo.');
        } catch (SaleValidationException $exception) {
            if ($sale && $sale->isDraft()) {
                $sale->update(['validation_errors' => $exception->errors()]);
            }

            $redirect = $sale
                ? redirect()->route('sales.show', $sale)
                : back()->withInput();

            return $redirect
                ->withErrors($exception->errors())
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function update(Request $request, Sale $sale, SaleService $sales): RedirectResponse
    {
        if (! $sale->isDraft()) {
            return redirect()
                ->route('sales.show', $sale)
                ->with('error', 'Solo se pueden editar ventas en estado borrador.');
        }

        if ($request->has('payments')) {
            $filtered = collect($request->input('payments', []))
                ->filter(fn ($p) => (isset($p['amount']) && (float) $p['amount'] > 0) || ! empty($p['method']))
                ->values()
                ->all();
            $request->merge(['payments' => ! empty($filtered) ? $filtered : null]);
        }

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'currency_code' => ['required', 'in:CLP,USD,PEN'],
            'tax_mode' => ['nullable', 'in:iva_19,igv_18,export_0,exempt'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'mode' => ['required', 'in:draft,finalize'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.chamber_id' => ['nullable', 'exists:chambers,id'],
            'items.*.bin_id' => ['nullable', 'exists:bins,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'in:efectivo,transferencia,tarjeta,credito,cheque_fecha,yape,plin,otro'],
            'payments.*.currency_code' => ['required_with:payments', 'in:CLP,USD,PEN'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payments = collect($data['payments'] ?? [])
            ->filter(fn (array $payment) => isset($payment['amount']) && (float) $payment['amount'] > 0)
            ->values()
            ->all();

        try {
            $sale = $sales->updateDraft($sale, $data);

            if ($data['mode'] === 'finalize') {
                $sale = $sales->finalize($sale, $request->user(), $payments, true);

                return redirect()
                    ->route('sales.show', $sale)
                    ->with('success', "Venta {$sale->number} validada correctamente.");
            }

            $sale = $sales->saveDraftPayments($sale, $payments, $request->user());

            return redirect()
                ->route('sales.show', $sale)
                ->with('success', 'Borrador de venta actualizado correctamente.');
        } catch (SaleValidationException $exception) {
            $sale->update(['validation_errors' => $exception->errors()]);

            return redirect()
                ->route('sales.show', $sale)
                ->withErrors($exception->errors())
                ->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }
}
