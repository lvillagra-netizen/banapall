@extends('layouts.app')

@section('title', 'Documentos')
@section('eyebrow', 'Administración · Documentos emitidos')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Notas de venta, boletas y facturas con folio, estado SII simulado y PDF asociado.</p>
        @can('folios.manage')
            <a href="{{ route('billing.folios') }}" class="btn btn-secondary">Gestionar folios</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('billing.documents') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
            <div class="xl:col-span-2">
                <label class="label">Buscar</label>
                <input name="search" value="{{ request('search') }}" class="input" placeholder="Folio o cliente…">
            </div>
            <div>
                <label class="label">Tipo</label>
                <select name="type" class="input">
                    <option value="">Todos</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
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
                <label class="label">Desde</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input">
            </div>
            <div>
                <label class="label">Hasta</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input">
            </div>
            <div class="flex items-end gap-2">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('billing.documents') }}" class="btn btn-ghost btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Documentos</h2>
            <span class="badge badge-neutral">{{ $documents->total() }} resultados</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Emisión</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th class="text-right">Total</th>
                        <th>SII</th>
                        <th>Estado</th>
                        <th class="text-right">PDF</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php($total = $document->totals_snapshot['total'] ?? 0)
                        @php($currency = $document->totals_snapshot['currency_code'] ?? 'CLP')
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $document->folio }}</p>
                                <p class="text-[11px] text-slate-400">{{ $document->type->label() }}</p>
                            </td>
                            <td class="whitespace-nowrap text-xs">{{ $document->issued_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ $document->sale ? route('sales.show', $document->sale) : '#' }}" class="font-medium text-avocado-800 hover:underline">{{ $document->sale?->customer?->name ?? ($document->customer_snapshot['name'] ?? '—') }}</a>
                                <p class="text-[11px] text-slate-400">{{ $document->customer_snapshot['tax_id'] ?? '' }}</p>
                            </td>
                            <td class="text-xs">{{ $document->sale?->seller?->name }}</td>
                            <td class="whitespace-nowrap text-right font-semibold text-ink">{{ \App\Support\Money::format($total, $currency) }}</td>
                            <td><span class="badge {{ $document->sii_status->badge() }}">{{ $document->sii_status->label() }}</span></td>
                            <td><span class="badge {{ $document->status->badge() }}">{{ $document->status->label() }}</span></td>
                            <td class="text-right">
                                @if ($document->pdf_path)
                                    <a href="{{ route('billing.documents.pdf', $document) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Descargar</a>
                                @else
                                    <span class="text-[11px] text-slate-400">No disponible</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-14 text-center text-sm text-slate-400">No hay documentos emitidos con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $documents->links() }}</div>
@endsection
