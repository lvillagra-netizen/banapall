@extends('layouts.app')

@section('title', 'Panel general')
@section('eyebrow', 'Dirección · Resumen operacional')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-2xl text-sm text-slate-500">Vista consolidada de la operación. Los montos multi-moneda se convierten a CLP usando el tipo de cambio registrado en cada venta.</p>
        <div class="flex gap-2">
            <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm">Nueva venta</a>
            <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Ver reportes</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="metric-label">Ventas de hoy</p>
                    <p class="mt-3 text-3xl font-semibold text-ink">{{ $todayCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Documentos y borradores de hoy</p>
                </div>
                <div class="stat-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6h2l2.2 9.2a2 2 0 0 0 2 1.5h6.8a2 2 0 0 0 2-1.6L20 8H7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="metric-label">Ingresos de hoy</p>
                    <p class="mt-3 text-3xl font-semibold text-ink">{{ $todayRevenue }}</p>
                    <p class="mt-1 text-xs text-slate-500">Equivalente en CLP</p>
                </div>
                <div class="stat-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3v18m5-14.5c0-1.9-2.2-3.5-5-3.5s-5 1.6-5 3.5S9.2 10 12 10s5 1.6 5 3.5S14.8 17 12 17s-5-1.6-5-3.5" stroke-linecap="round"/></svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="metric-label">Mes en curso</p>
                    <p class="mt-3 text-3xl font-semibold text-ink">{{ $monthRevenue }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $monthCount }} ventas registradas</p>
                </div>
                <div class="stat-icon">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 19V5m0 14h16" stroke-linecap="round"/><path d="M7 15l4-5 3 3 5-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="metric-label">Por cobrar</p>
                    <p class="mt-3 text-3xl font-semibold text-ink">{{ $pendingTotal }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $pendingSales->count() }} ventas con saldo</p>
                </div>
                <div class="stat-icon bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5" stroke-linecap="round"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Ingresos últimos 7 días</h2>
                    <p class="mt-1 text-xs text-slate-500">Equivalente CLP de ventas no anuladas</p>
                </div>
                <span class="badge badge-info">{{ $chart->sum('count') }} ventas</span>
            </div>
            <div class="card-body">
                <div class="h-72"><canvas id="salesChart"></canvas></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Estado de ventas</h2>
                    <p class="mt-1 text-xs text-slate-500">Distribución histórica</p>
                </div>
            </div>
            <div class="card-body space-y-4">
                @foreach (\App\Enums\SaleStatus::cases() as $status)
                    @php($count = (int) ($statusCounts[$status->value] ?? 0))
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $status->badge() }}">{{ $status->label() }}</span>
                        </div>
                        <span class="text-lg font-semibold text-ink">{{ $count }}</span>
                    </div>
                @endforeach
                <div class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">
                    <p><strong class="text-slate-700">{{ $customerCount }}</strong> clientes activos · <strong class="text-slate-700">{{ $productCount }}</strong> productos activos</p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    @foreach ($rates as $currency => $rate)
                        <div class="rounded-lg border border-slate-200 px-3 py-2">
                            <p class="font-semibold text-slate-500">{{ $currency }}</p>
                            <p class="mt-0.5 text-slate-800">{{ $rate ? '$'.number_format((float) $rate, 0, ',', '.') : '—' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Ventas recientes</h2>
                    <p class="mt-1 text-xs text-slate-500">Últimos movimientos del equipo</p>
                </div>
                <a href="{{ route('sales.index') }}" class="btn btn-ghost btn-sm">Ver todas</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr>
                                <td>
                                    <a href="{{ route('sales.show', $sale) }}" class="font-semibold text-avocado-800 hover:underline">{{ $sale->number ?: 'BORR-'.$sale->id }}</a>
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
                                </td>
                                <td>
                                    <p class="font-medium text-slate-700">{{ $sale->customer?->name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $sale->currency_code }}</p>
                                </td>
                                <td class="text-xs">{{ $sale->seller?->name }}</td>
                                <td class="text-right font-semibold text-ink">{{ \App\Support\Money::format($sale->total, $sale->currency_code) }}</td>
                                <td><span class="badge {{ $sale->status->badge() }}">{{ $sale->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">Aún no hay ventas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Alertas de stock</h2>
                    <p class="mt-1 text-xs text-slate-500">En o bajo el mínimo</p>
                </div>
                <a href="{{ route('inventory.index', ['low' => 1]) }}" class="btn btn-ghost btn-sm">Revisar</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($lowStock as $stock)
                    <div class="flex items-center gap-3 px-5 py-3.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v5m0 3h.01M10.3 4.3 2.8 17.2A1.7 1.7 0 0 0 4.3 20h15.4a1.7 1.7 0 0 0 1.5-2.8L13.7 4.3a2 2 0 0 0-3.4 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-700">{{ $stock->product->name }}</p>
                            <p class="text-[11px] text-slate-400">{{ $stock->warehouse->name }} · mínimo {{ $stock->product->min_stock }}</p>
                        </div>
                        <span class="text-sm font-semibold text-amber-700">{{ $stock->quantity }}</span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-400">Sin alertas de stock. Todo en orden.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Por cobrar</h2>
                    <p class="mt-1 text-xs text-slate-500">Ventas validadas con saldo pendiente</p>
                </div>
                <a href="{{ route('sales.index', ['status' => 'validada']) }}" class="btn btn-ghost btn-sm">Gestionar</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($pendingSales as $sale)
                    <a href="{{ route('sales.show', $sale) }}" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-avocado-50/50">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-700">{{ $sale->customer?->name }}</p>
                            <p class="text-[11px] text-slate-400">{{ $sale->number }} · {{ $sale->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-rose-600">{{ \App\Support\Money::format($sale->balance, $sale->currency_code) }}</p>
                            <p class="text-[10px] uppercase tracking-wide text-slate-400">saldo</p>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-400">Sin saldos pendientes.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Productos más vendidos</h2>
                    <p class="mt-1 text-xs text-slate-500">Últimos 30 días</p>
                </div>
            </div>
            <div class="card-body space-y-4">
                @php($max = max(1, (float) ($topProducts->max('quantity') ?? 1)))
                @forelse ($topProducts as $product)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <span class="truncate font-medium text-slate-700">{{ $product->product_name }}</span>
                            <span class="shrink-0 text-xs font-semibold text-slate-500">{{ rtrim(rtrim(number_format((float) $product->quantity, 2, ',', '.'), '0'), ',') }} u.</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-avocado-500" style="width: {{ min(100, ((float) $product->quantity / $max) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">Sin ventas en el período.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            const canvas = document.getElementById('salesChart');

            if (!canvas || !window.Chart) {
                return;
            }

            const labels = @json($chart->pluck('label'));
            const values = @json($chart->pluck('total_clp'));

            new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        borderColor: '#587f21',
                        backgroundColor: 'rgba(88, 127, 33, 0.10)',
                        borderWidth: 2,
                        pointBackgroundColor: '#587f21',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return '$ ' + Number(context.raw).toLocaleString('es-CL');
                                },
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                color: '#94a3b8',
                                callback: function (value) {
                                    return '$' + Number(value).toLocaleString('es-CL');
                                },
                            },
                        },
                    },
                },
            });
        });
    </script>
@endpush
