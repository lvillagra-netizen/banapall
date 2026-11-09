@extends('layouts.app')

@section('title', 'Ventas')
@section('eyebrow', 'Ventas · Historial de documentos')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Consulta ventas por folio, cliente, estado o moneda. Los borradores aún no descuentan inventario ni emiten documento.</p>
        <a href="{{ route('pos.index') }}" class="btn btn-primary">Nueva venta</a>
    </div>

    <form method="GET" action="{{ route('sales.index') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="label">Buscar</label>
                <input name="search" value="{{ request('search') }}" class="input" placeholder="Folio o cliente…">
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
                <label class="label">Desde</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input">
            </div>
            <div>
                <label class="label">Hasta</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input">
            </div>
            <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-6">
                <button class="btn btn-primary btn-sm">Aplicar filtros</button>
                <a href="{{ route('sales.index') }}" class="btn btn-ghost btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Documentos de venta</h2>
            <span class="badge badge-neutral">{{ $sales->total() }} resultados</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Folio / borrador</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Saldo</th>
                        <th>Estado</th>
                        <th class="text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="font-semibold text-avocado-800 hover:underline">{{ $sale->number ?: 'BORR-'.$sale->id }}</a>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $sale->currency_code }}</p>
                            </td>
                            <td class="whitespace-nowrap text-xs">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <p class="font-medium text-slate-700">{{ $sale->customer?->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $sale->customer?->displayTaxId() }}</p>
                            </td>
                            <td class="text-xs">{{ $sale->seller?->name }}</td>
                            <td class="whitespace-nowrap text-right font-semibold text-ink">{{ \App\Support\Money::format($sale->total, $sale->currency_code) }}</td>
                            <td class="whitespace-nowrap text-right {{ (float) $sale->balance > 0 ? 'font-medium text-rose-600' : 'text-slate-400' }}">{{ \App\Support\Money::format($sale->balance, $sale->currency_code) }}</td>
                            <td><span class="badge {{ $sale->status->badge() }}">{{ $sale->status->label() }}</span></td>
                            <td class="text-right">
    <a href="{{ route('sales.show', $sale) }}" class="btn btn-ghost btn-sm">Ver</a>
    @if ($sale->isDraft())
        <a href="{{ route('pos.edit', $sale) }}" class="btn btn-primary btn-sm ml-2">Editar</a>
    @endif
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-14 text-center">
                                <p class="text-sm text-slate-400">No se encontraron ventas con los filtros seleccionados.</p>
                                <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm mt-3">Registrar primera venta</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $sales->links() }}</div>
@endsection
