@extends('layouts.app')

@section('title', 'Reportes')
@section('eyebrow', 'Administración · Reporte de ventas')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Consolidado por rango de fechas. Los totales en CLP usan el tipo de cambio guardado en cada venta.</p>
        <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-primary">Exportar CSV</a>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div>
                <label class="label">Desde</label>
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input">
            </div>
            <div>
                <label class="label">Hasta</label>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input">
            </div>
            <div>
                <label class="label">Estado</label>
                <select name="status" class="input">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Moneda</label>
                <select name="currency" class="input">
                    <option value="">Todas</option>
                    @foreach (\App\Enums\Currency::cases() as $currency)
                        <option value="{{ $currency->value }}" @selected(request('currency') === $currency->value)>{{ $currency->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Vendedor</label>
                <select name="seller" class="input">
                    <option value="">Todos</option>
                    @foreach ($sellerOptions as $seller)
                        <option value="{{ $seller->id }}" @selected(request('seller') == $seller->id)>{{ $seller->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Producto</label>
                <select name="product" class="input">
                    <option value="">Todos</option>
                    @foreach ($productOptions as $product)
                        <option value="{{ $product->id }}" @selected(request('product') == $product->id)>{{ $product->sku }} · {{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2 sm:col-span-2">
                <button class="btn btn-primary btn-sm">Generar reporte</button>
                <a href="{{ route('reports.index') }}" class="btn btn-ghost btn-sm">Restablecer</a>
            </div>
        </div>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card">
            <p class="metric-label">Total equivalente</p>
            <p class="mt-2 text-2xl font-semibold text-avocado-800">{{ $totalClp }}</p>
            <p class="mt-1 text-xs text-slate-400">Ventas no anuladas</p>
        </div>
        <div class="stat-card">
            <p class="metric-label">Documentos</p>
            <p class="mt-2 text-2xl font-semibold text-ink">{{ $sales->count() }}</p>
            <p class="mt-1 text-xs text-slate-400">Incluye anuladas</p>
        </div>
        <div class="stat-card">
            <p class="metric-label">Monedas activas</p>
            <p class="mt-2 text-2xl font-semibold text-ink">{{ $byCurrency->count() }}</p>
            <p class="mt-1 text-xs text-slate-400">Con ventas en el período</p>
        </div>
        <div class="stat-card">
            <p class="metric-label">Período</p>
            <p class="mt-2 text-lg font-semibold text-ink">{{ $from->format('d/m/Y') }}</p>
            <p class="mt-1 text-xs text-slate-400">hasta {{ $to->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($byCurrency as $row)
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <p class="font-display text-lg font-semibold text-ink">{{ $row['currency'] }}</p>
                        <span class="badge badge-neutral">{{ $row['count'] }} ventas</span>
                    </div>
                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-400">Total</dt><dd class="font-semibold text-slate-800">{{ $row['total'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-400">Pagado</dt><dd class="text-slate-600">{{ $row['paid'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-400">Saldo</dt><dd class="font-medium text-rose-600">{{ $row['balance'] }}</dd></div>
                    </dl>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="section-title">Ventas por vendedor</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Vendedor</th><th class="text-right">Ventas</th><th class="text-right">Total CLP</th></tr></thead>
                    <tbody>
                        @forelse ($sellers as $seller)
                            <tr>
                                <td class="font-medium text-slate-700">{{ $seller['name'] }}</td>
                                <td class="text-right">{{ $seller['count'] }}</td>
                                <td class="text-right font-semibold text-ink">{{ $seller['total_clp'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 text-center text-sm text-slate-400">Sin ventas en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="section-title">Productos más vendidos</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>SKU</th><th>Producto</th><th class="text-right">Cantidad</th></tr></thead>
                    <tbody>
                        @forelse ($topProducts as $product)
                            <tr>
                                <td class="text-xs text-slate-400">{{ $product->sku }}</td>
                                <td class="font-medium text-slate-700">{{ $product->product_name }}</td>
                                <td class="text-right font-semibold">{{ rtrim(rtrim(number_format((float) $product->quantity, 2, ',', '.'), '0'), ',') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 text-center text-sm text-slate-400">Sin productos vendidos en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-header">
            <div>
                <h2 class="section-title">Detalle de ventas</h2>
                <p class="mt-1 text-xs text-slate-400">Primeras 50 filas. Exporta el CSV para el detalle completo.</p>
            </div>
            <span class="badge badge-neutral">{{ $sales->count() }} documentos</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Moneda</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Saldo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales->take(50) as $sale)
                        <tr>
                            <td><a href="{{ route('sales.show', $sale) }}" class="font-semibold text-avocado-800 hover:underline">{{ $sale->number ?: 'BORR-'.$sale->id }}</a></td>
                            <td class="whitespace-nowrap text-xs">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->customer?->name }}</td>
                            <td class="text-xs">{{ $sale->seller?->name }}</td>
                            <td><span class="badge badge-neutral">{{ $sale->currency_code }}</span></td>
                            <td class="whitespace-nowrap text-right font-medium">{{ \App\Support\Money::format($sale->total, $sale->currency_code) }}</td>
                            <td class="whitespace-nowrap text-right text-xs {{ (float) $sale->balance > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ \App\Support\Money::format($sale->balance, $sale->currency_code) }}</td>
                            <td><span class="badge {{ $sale->status->badge() }}">{{ $sale->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-sm text-slate-400">No hay ventas en el período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
