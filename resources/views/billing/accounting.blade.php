@extends('layouts.app')

@section('title', 'Contabilidad')
@section('eyebrow', 'Administración · Libro contable simulado')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Asientos generados por el conector simulado a partir de notas de venta, pagos y anulaciones.</p>
        <span class="badge badge-info">Contabilidad simulada</span>
    </div>

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <div class="stat-card">
            <p class="metric-label">Debe</p>
            <p class="mt-2 text-2xl font-semibold text-ink">{{ \App\Support\Money::format($debit, 'CLP') }}</p>
        </div>
        <div class="stat-card">
            <p class="metric-label">Haber</p>
            <p class="mt-2 text-2xl font-semibold text-ink">{{ \App\Support\Money::format($credit, 'CLP') }}</p>
        </div>
        <div class="stat-card">
            <p class="metric-label">Movimiento CLP</p>
            <p class="mt-2 text-2xl font-semibold text-avocado-800">{{ \App\Support\Money::format($clp, 'CLP') }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('billing.accounting') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <label class="label">Cuenta</label>
                <input name="account" value="{{ request('account') }}" class="input" placeholder="Código o nombre de cuenta…">
            </div>
            <div>
                <label class="label">Desde</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input">
            </div>
            <div>
                <label class="label">Hasta</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input">
            </div>
            <div class="flex items-end gap-2">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('billing.accounting') }}" class="btn btn-ghost btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Libro diario</h2>
            <span class="badge badge-neutral">{{ $entries->total() }} asientos</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cuenta</th>
                        <th>Descripción</th>
                        <th>Documento</th>
                        <th class="text-right">Debe</th>
                        <th class="text-right">Haber</th>
                        <th class="text-right">CLP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $entry->entry_date?->format('d/m/Y') }}</td>
                            <td class="text-xs">
                                <p class="font-semibold text-slate-700">{{ $entry->account_code }}</p>
                                <p class="text-slate-400">{{ $entry->account_name }}</p>
                            </td>
                            <td class="max-w-xs text-xs text-slate-500">{{ $entry->description }}</td>
                            <td class="text-xs">
                                @if ($entry->document)
                                    <span class="font-medium text-slate-700">{{ $entry->document->folio }}</span>
                                @else
                                    —
                                @endif
                                @if ($entry->sale)
                                    <a href="{{ route('sales.show', $entry->sale) }}" class="ml-1 text-avocado-700 hover:underline">ver venta</a>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right text-xs">{{ (float) $entry->debit > 0 ? \App\Support\Money::format($entry->debit, $entry->currency_code) : '—' }}</td>
                            <td class="whitespace-nowrap text-right text-xs">{{ (float) $entry->credit > 0 ? \App\Support\Money::format($entry->credit, $entry->currency_code) : '—' }}</td>
                            <td class="whitespace-nowrap text-right text-xs text-slate-500">{{ \App\Support\Money::format($entry->amount_clp, 'CLP') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-14 text-center text-sm text-slate-400">No hay asientos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $entries->links() }}</div>
@endsection
