@extends('layouts.app')

@section('title', 'Clientes')
@section('eyebrow', 'Catálogo · Cartera de clientes')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Clientes nacionales e internacionales con moneda e impuesto sugerido para el punto de venta.</p>
        @can('customers.manage')
            <a href="{{ route('customers.create') }}" class="btn btn-primary">Nuevo cliente</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('customers.index') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label class="label">Buscar</label>
                <input name="search" value="{{ request('search') }}" class="input" placeholder="Nombre, RUT/RUC o email…">
            </div>
            <div>
                <label class="label">País</label>
                <select name="country" class="input">
                    <option value="">Todos</option>
                    @foreach ($countries as $code => $country)
                        <option value="{{ $code }}" @selected(request('country') === $code)>{{ $country['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <select name="active" class="input">
                    <option value="">Todos los estados</option>
                    <option value="1" @selected(request('active') === '1')>Activos</option>
                    <option value="0" @selected(request('active') === '0')>Inactivos</option>
                </select>
                <button class="btn btn-primary btn-sm">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Cartera</h2>
            <span class="badge badge-neutral">{{ $customers->total() }} clientes</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>País</th>
                        <th>Moneda</th>
                        <th class="text-right">Ventas</th>
                        <th class="text-right">Crédito</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $customer) }}" class="font-semibold text-avocado-800 hover:underline">{{ $customer->name }}</a>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $customer->taxIdLabel() }} {{ $customer->displayTaxId() }}</p>
                            </td>
                            <td class="text-xs">
                                <p>{{ $customer->email ?: '—' }}</p>
                                <p class="text-slate-400">{{ $customer->phone ?: '—' }}</p>
                            </td>
                            <td class="text-xs">{{ $customer->countryLabel() }}</td>
                            <td><span class="badge badge-neutral">{{ $customer->currency_code }}</span></td>
                            <td class="text-right font-semibold">{{ $customer->sales_count }}</td>
                            <td class="whitespace-nowrap text-right text-xs">{{ \App\Support\Money::format($customer->credit_limit, $customer->currency_code) }}</td>
                            <td>
                                <span class="badge {{ $customer->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost btn-sm">Ver</a>
                                    @can('customers.manage')
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-ghost btn-sm">Editar</a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-14 text-center text-sm text-slate-400">No hay clientes registrados con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $customers->links() }}</div>
@endsection
