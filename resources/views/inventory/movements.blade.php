@extends('layouts.app')

@section('title', 'Movimientos de stock')
@section('eyebrow', 'Inventario · Trazabilidad de movimientos')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="max-w-xl text-sm text-slate-500">Cada entrada, salida, ajuste o traslado conserva cantidad anterior y posterior.</p>
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Volver a existencias</a>
    </div>

    <form method="GET" action="{{ route('inventory.movements') }}" class="card mb-5">
        <div class="card-body grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
            <div>
                <label class="label">Bodega</label>
                <select name="warehouse" class="input">
                    <option value="">Todas</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((int) request('warehouse') === $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label class="label">Producto</label>
                <select name="product" class="input">
                    <option value="">Todos</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((int) request('product') === $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
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
                <label class="label">Desde</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input">
            </div>
            <div>
                <label class="label">Hasta</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input">
            </div>
            <div class="flex items-end gap-2">
                <button class="btn btn-primary btn-sm">Filtrar</button>
                @can('inventory.approve')
                    <a href="{{ route('inventory.movements', array_merge(request()->query(), ['pending' => true])) }}" class="btn btn-outline btn-sm">Pendientes de aprobación</a>
                @endcan
                <a href="{{ route('inventory.movements') }}" class="btn btn-ghost btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="section-title">Historial</h2>
            <span class="badge badge-neutral">{{ $movements->total() }} movimientos</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Bodega</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Antes → Después</th>
                        <th>Usuario</th>
                        <th class="text-center">Aprobado</th>
                        <th>Referencia / nota</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php($isOut = in_array($movement->type->value, ['salida', 'ajuste_negativo', 'traslado_salida'], true))
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                            <td><span class="badge {{ $movement->type->badge() }}">{{ $movement->type->label() }}</span></td>
                            <td>
                                <p class="font-medium text-slate-700">{{ $movement->product?->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $movement->product?->sku }}</p>
                            </td>
                            <td class="text-xs">{{ $movement->warehouse?->name }}</td>
                            <td class="whitespace-nowrap text-right font-semibold {{ $isOut ? 'text-rose-600' : 'text-emerald-700' }}">{{ $isOut ? '−' : '+' }}{{ rtrim(rtrim(number_format((float) $movement->quantity, 3, ',', '.'), '0'), ',') }}</td>
                            <td class="whitespace-nowrap text-right text-xs text-slate-500">{{ $movement->quantity_before }} → {{ $movement->quantity_after }}</td>
                            <td class="text-xs">{{ $movement->user?->name ?? 'Sistema' }}</td>
                            <td class="text-center text-xs">
                                @if ($movement->approved_by)
                                    <span class="badge badge-success">{{ $movement->approver?->name ?? '—' }}</span>
                                @else
                                    <span class="badge badge-neutral">Pendiente</span>
                                    @can('inventory.approve')
                                        <form method="POST" action="{{ route('inventory.movements.approve', $movement) }}" onsubmit="return confirm('¿Aprobar este movimiento?');" class="mt-1">
                                            @csrf
                                            <button class="btn btn-success btn-xs">Aprobar</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                            <td class="max-w-xs text-xs text-slate-500">
                                @if ($movement->reference_type)
                                    <span class="font-medium text-slate-600">{{ class_basename($movement->reference_type) }} #{{ $movement->reference_id }}</span>
                                @endif
                                <p class="truncate">{{ $movement->note ?: '—' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-14 text-center text-sm text-slate-400">Sin movimientos para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $movements->links() }}</div>
@endsection
