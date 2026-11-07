@extends('layouts.app')

@section('title', $customer->name)
@section('eyebrow', 'Catálogo · Ficha de cliente')

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-avocado-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Volver a clientes
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="font-display text-3xl font-semibold text-ink">{{ $customer->name }}</h1>
                <span class="badge {{ $customer->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</span>
                <span class="badge badge-neutral">{{ $customer->countryLabel() }}</span>
                <span class="badge badge-info">{{ $customer->currency_code }}</span>
            </div>
            <p class="mt-1.5 text-sm text-slate-500">{{ $customer->taxIdLabel() }} {{ $customer->displayTaxId() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('pos.access')
                <a href="{{ route('pos.index') }}" class="btn btn-secondary">Nueva venta</a>
            @endcan
            @can('customers.manage')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">Editar cliente</a>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_1.7fr]">
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="section-title">Contacto</h2></div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Email</span><span class="text-right font-medium text-slate-700">{{ $customer->email ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Teléfono</span><span class="text-right font-medium text-slate-700">{{ $customer->phone ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Dirección</span><span class="text-right font-medium text-slate-700">{{ collect([$customer->address, $customer->city, $customer->region])->filter()->implode(', ') ?: '—' }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">País</span><span class="text-right font-medium text-slate-700">{{ $customer->countryLabel() }}</span></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="section-title">Condiciones comerciales</h2></div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Moneda</span><span class="font-medium text-slate-700">{{ $customer->currency_code }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Impuesto sugerido</span><span class="font-medium text-slate-700">{{ \App\Enums\TaxMode::forCountry($customer->country_code)->label() }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Límite de crédito</span><span class="font-medium text-slate-700">{{ \App\Support\Money::format($customer->credit_limit, $customer->currency_code) }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-slate-400">Razón social</span><span class="text-right font-medium text-slate-700">{{ $customer->legal_name ?: '—' }}</span></div>
                </div>
            </div>

            @if ($customer->notes)
                <div class="card">
                    <div class="card-header"><h2 class="section-title">Notas</h2></div>
                    <div class="card-body text-sm leading-relaxed text-slate-600">{{ $customer->notes }}</div>
                </div>
            @endif

            @can('customers.manage')
                <div class="card border-rose-200">
                    <div class="card-body">
                        <p class="text-sm font-semibold text-rose-700">Eliminar cliente</p>
                        <p class="mt-1 text-xs text-slate-500">Sus ventas históricas se conservan. La ficha quedará fuera de las nuevas operaciones.</p>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="mt-3" onsubmit="return confirm('¿Eliminar este cliente?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Eliminar cliente</button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="section-title">Últimas ventas</h2>
                    <p class="mt-1 text-xs text-slate-500">Máximo 10 documentos recientes</p>
                </div>
                @can('sales.view')
                    <a href="{{ route('sales.index', ['search' => $customer->name]) }}" class="btn btn-ghost btn-sm">Ver historial</a>
                @endcan
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Folio</th><th>Fecha</th><th class="text-right">Total</th><th class="text-right">Saldo</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->sales as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale) }}" class="font-semibold text-avocado-800 hover:underline">{{ $sale->number ?: 'BORR-'.$sale->id }}</a></td>
                                <td class="whitespace-nowrap text-xs">{{ $sale->created_at->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap text-right font-medium">{{ \App\Support\Money::format($sale->total, $sale->currency_code) }}</td>
                                <td class="whitespace-nowrap text-right text-xs {{ (float) $sale->balance > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ \App\Support\Money::format($sale->balance, $sale->currency_code) }}</td>
                                <td><span class="badge {{ $sale->status->badge() }}">{{ $sale->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-12 text-center text-sm text-slate-400">Este cliente aún no tiene ventas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
